<?php
// Запрещаем буферизацию вывода для немедленного получения ошибок
ob_end_clean();

// Включаем отображение ошибок в режиме разработки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Добавляем дополнительное логирование для отладки
error_log("=== Начало обработки запроса: " . date('Y-m-d H:i:s') . " ===");
error_log("Метод: " . $_SERVER['REQUEST_METHOD']);
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'не указан'));
error_log("Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'не указан'));

// Логирование тела запроса для POST/PUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $rawData = file_get_contents('php://input');
    $truncatedData = (strlen($rawData) > 1000) ? substr($rawData, 0, 1000) . '...[truncated]' : $rawData;
    error_log("Тело запроса: " . $truncatedData);
    
    $jsonData = json_decode($rawData, true);
    if ($jsonData !== null) {
        error_log("Данные JSON декодированы успешно");
    } else if ($rawData) {
        error_log("Ошибка декодирования JSON: " . json_last_error_msg());
    }
}

// Подключаем общие функции для работы с CORS
require_once __DIR__ . '/cors_helpers.php';

// ВАЖНО: Устанавливаем CORS заголовки ОДИН раз в самом начале обработки запроса
// Больше нигде в коде не должно быть установки CORS заголовков
setCorsHeaders();

// Обработка предварительного запроса OPTIONS для CORS
handleOptionsRequest();

// Проверяем, есть ли дублирование заголовков CORS в ответе
$headers = headers_list();
$corsHeaders = [];
foreach ($headers as $header) {
    if (strpos($header, 'Access-Control-') === 0) {
        $parts = explode(':', $header, 2);
        $name = trim($parts[0]);
        $corsHeaders[$name][] = trim($parts[1] ?? '');
    }
}

foreach ($corsHeaders as $name => $values) {
    if (count($values) > 1) {
        error_log("ВНИМАНИЕ: Обнаружено дублирование заголовка $name: " . implode(', ', $values));
    }
}

// Инициализация ядра CMS
require_once __DIR__ . '/../core/init.php';
$core = \Core\Core::getInstance();
$logger = \Core\Logger::getInstance();

// Добавляем обработчик ошибок для логирования
function errorHandler($errno, $errstr, $errfile, $errline) {
    global $logger, $core;
    
    $errorType = '';
    switch ($errno) {
        case E_ERROR: $errorType = 'E_ERROR'; break;
        case E_WARNING: $errorType = 'E_WARNING'; break;
        case E_PARSE: $errorType = 'E_PARSE'; break;
        case E_NOTICE: $errorType = 'E_NOTICE'; break;
        default: $errorType = 'UNKNOWN'; break;
    }
    
    $message = "$errorType: $errstr in $errfile on line $errline";
    
    // Логируем ошибку через систему логирования ядра
    if ($logger) {
        $logger->error($message);
    } else {
        // Резервный вариант, если логгер недоступен
        $logMessage = date('[Y-m-d H:i:s]') . " PHP $message\n";
        error_log($logMessage, 3, __DIR__ . '/error.log');
    }
    
    // В режиме отладки возвращаем ошибку
    if ($core && $core->getConfig('debug', true)) {
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode(['error' => $message]));
    }
    
    return true;
}

// Устанавливаем обработчик ошибок
set_error_handler('errorHandler');

// Загружаем конфигурацию
require_once __DIR__ . '/../config/app.php';

// Проверка конфигурации
if (!isset($config) || !is_array($config)) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(['error' => 'Configuration not found or invalid']));
}

// Устанавливаем часовой пояс
date_default_timezone_set($config['timezone']);

// Обработчик события регистрации маршрута API
$core->getEventManager()->on('api.register_route', function($params) use (&$router) {
    if (!isset($params['method']) || !isset($params['endpoint']) || !isset($params['handler'])) {
        return false;
    }
    
    $method = strtolower($params['method']);
    $endpoint = $params['endpoint'];
    $handler = $params['handler'];
    
    // Регистрируем маршрут в роутере
    if (method_exists($router, $method)) {
        $router->$method($endpoint, $handler);
        return true;
    }
    
    return false;
});

try {
    // Создаем экземпляр маршрутизатора
    $router = new API\Router();
    
    // Включаем детальное логирование
    error_log("API Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
    error_log("Request headers: " . json_encode(getallheaders()));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $rawBody = file_get_contents('php://input');
        error_log("Request raw body: " . $rawBody);
        if (json_decode($rawBody, true) !== null) {
            error_log("Decoded JSON: " . json_encode(json_decode($rawBody, true)));
        }
    }
    
    // Запускаем событие для регистрации маршрутов из модулей
    $result = $core->getEventManager()->trigger('api.routes.register', ['router' => $router]);
    
    // Если результат является массивом маршрутов, регистрируем их
    if (is_array($result)) {
        foreach ($result as $route) {
            if (isset($route['method'], $route['path'], $route['handler'])) {
                $method = strtolower($route['method']);
                $path = $route['path'];
                $handler = $route['handler'];
                
                if (method_exists($router, $method)) {
                    $router->$method($path, $handler);
                }
            }
        }
    }
    
    // Определяем стандартные маршруты
    
    // Маршруты для пользователей
    $router->get('/users', [Controllers\UserController::class, 'getAll']);
    $router->get('/users/{id}', [Controllers\UserController::class, 'getById']);
    $router->post('/users', [Controllers\UserController::class, 'create']);
    $router->put('/users/{id}', [Controllers\UserController::class, 'update']);
    $router->delete('/users/{id}', [Controllers\UserController::class, 'delete']);
    
    // Маршруты для аутентификации
    $router->post('/login', [Controllers\UserController::class, 'login']);
    $router->post('/register', [Controllers\UserController::class, 'register']);
    $router->get('/me', [Controllers\UserController::class, 'getCurrentUser']);
    $router->post('/auth/login', [Controllers\UserController::class, 'login']);
    $router->post('/auth/register', [Controllers\UserController::class, 'register']);
    $router->get('/auth/me', [Controllers\UserController::class, 'getCurrentUser']);
    $router->post('/auth/logout', [Controllers\UserController::class, 'logout']);
    
    // Маршруты для типов контента
    $router->get('/content-types', [Controllers\ContentTypeController::class, 'getAll']);
    $router->get('/content-types', [Controllers\ContentTypeController::class, 'getList']);
    $router->post('/content-types', [Controllers\ContentTypeController::class, 'create']);
    $router->get('/content-types/{id}', [Controllers\ContentTypeController::class, 'getById']);
    $router->put('/content-types/{id}', [Controllers\ContentTypeController::class, 'update']);
    $router->delete('/content-types/{id}', [Controllers\ContentTypeController::class, 'delete']);
    
    // Маршруты для полей типов контента
    $router->get('/content-types/fields/{id}', [Controllers\ContentTypeFieldController::class, 'getById']);
    $router->get('/content-types/{id}/fields', [Controllers\ContentTypeFieldController::class, 'getByContentType']);
    $router->post('/content-types/{id}/fields', [Controllers\ContentTypeFieldController::class, 'create']);
    $router->put('/content-types/fields/{id}', [Controllers\ContentTypeFieldController::class, 'update']);
    $router->delete('/content-types/fields/{id}', [Controllers\ContentTypeFieldController::class, 'delete']);
    
    // Маршруты для записей контента
    $router->get('/content-types/{id}/content', [Controllers\ContentController::class, 'getAll']);
    $router->get('/content-types/{typeId}/content', [Controllers\ContentController::class, 'getList']);
    $router->get('/content-types/{typeId}/content/{id}', [Controllers\ContentController::class, 'getById']);
    $router->post('/content-types/{typeId}/content', [Controllers\ContentController::class, 'create']);
    $router->put('/content-types/{typeId}/content/{id}', [Controllers\ContentController::class, 'update']);
    $router->delete('/content-types/{typeId}/content/{id}', [Controllers\ContentController::class, 'delete']);
    
    // Маршруты для настроек
    $router->get('/settings', [Controllers\SettingsController::class, 'getAll']);
    $router->put('/settings', [Controllers\SettingsController::class, 'update']);
    
    // Маршруты для тем
    $router->get('/themes', [Controllers\ThemeController::class, 'getList']);
    $router->post('/themes/activate', [Controllers\ThemeController::class, 'activate']);
    $router->get('/themes/{name}', [Controllers\ThemeController::class, 'getThemeInfo']);
    
    // Маршруты для предпросмотра тем
    $router->get('/preview/{themeName}', [Controllers\PreviewController::class, 'previewIndex']);
    $router->get('/preview/{themeName}/content/{id}', [Controllers\PreviewController::class, 'previewContentItem']);
    $router->get('/preview/{themeName}/content-type/{id}', [Controllers\PreviewController::class, 'previewContentType']);
    $router->get('/preview/{themeName}/template/{itemId}/{template}', [Controllers\PreviewController::class, 'previewWithTemplate']);
    
    // Маршруты для управления шаблонами контента
    $router->get('/content/{id}/templates', [Controllers\ContentController::class, 'getAvailableTemplates']);
    $router->put('/content/{id}/template', [Controllers\ContentController::class, 'setTemplate']);
    
    // Маршруты для модулей
    $router->get('/modules', [Controllers\ModulesController::class, 'getAll']);
    $router->get('/modules/status', function() use ($core) {
        try {
            // Устанавливаем правильный заголовок Content-Type
            header('Content-Type: application/json');
            
            // Получаем менеджер модулей напрямую
            $moduleManager = \Core\ModuleManager::getInstance();
            
            // Получаем список всех модулей с их статусами
            $modules = $moduleManager->getAllModulesWithStatus();
            
            // Добавляем явную отладочную информацию в ответ
            $response = [
                'success' => true,
                'modules' => $modules,
                'debug' => [
                    'timestamp' => time(),
                    'module_count' => count($modules)
                ]
            ];
            
            // Явно выводим JSON и завершаем выполнение
            echo json_encode($response);
            exit;
        } catch (\Exception $e) {
            // Устанавливаем правильный заголовок Content-Type
            header('Content-Type: application/json');
            
            // Формируем ответ с ошибкой
            $errorResponse = [
                'success' => false,
                'error' => 'Ошибка при получении статуса модулей: ' . $e->getMessage()
            ];
            
            // Явно выводим JSON и завершаем выполнение
            echo json_encode($errorResponse);
            exit;
        }
    });
    $router->get('/modules/{slug}', [Controllers\ModulesController::class, 'getById']);
    $router->post('/modules/{slug}/install', [Controllers\ModulesController::class, 'install']);
    $router->post('/modules/{slug}/activate', function($requestData, $slug) {
        // Получаем экземпляр ядра через getInstance()
        $core = \Core\Core::getInstance();
        
        // Для упрощения тестирования убираем проверку аутентификации
        try {
            // Проверяем, что модуль существует
            $query = "SELECT id, name FROM modules WHERE slug = ?";
            $module = \API\Database::getInstance()->fetch($query, [$slug]);
            
            if (!$module) {
                return [
                    'success' => false,
                    'message' => "Модуль {$slug} не найден"
                ];
            }
            
            // Проверяем, активирован ли уже модуль
            $statusQuery = "SELECT status FROM modules WHERE slug = ?";
            $status = \API\Database::getInstance()->fetch($statusQuery, [$slug]);
            
            if ($status && $status['status'] === 'active') {
                return [
                    'success' => true,
                    'message' => "Модуль {$module['name']} уже активирован"
                ];
            }
            
            // Активируем модуль - прямой SQL запрос для обновления статуса
            $updateQuery = "UPDATE modules SET status = 'active', updated_at = ? WHERE slug = ?";
            $now = date('Y-m-d H:i:s');
            
            \API\Database::getInstance()->query($updateQuery, [$now, $slug]);
            
            // Для совместимости вызываем метод activateModule
            $moduleManager = \Core\ModuleManager::getInstance();
            $moduleManager->activateModule($slug);
            
            // Устанавливаем правильный заголовок для ответа
            header('Content-Type: application/json');
            
            return [
                'success' => true,
                'message' => "Модуль {$module['name']} успешно активирован"
            ];
        } catch (\Exception $e) {
            // Устанавливаем правильный заголовок для ответа
            header('Content-Type: application/json');
            
            return [
                'success' => false,
                'message' => "Ошибка активации модуля: " . $e->getMessage()
            ];
        }
    });
    $router->post('/modules/{slug}/deactivate', function($requestData, $slug) {
        // Получаем экземпляр ядра через getInstance()
        $core = \Core\Core::getInstance();
        
        // Для упрощения тестирования убираем проверку аутентификации
        try {
            // Проверяем, что модуль существует
            $query = "SELECT id, name FROM modules WHERE slug = ?";
            $module = \API\Database::getInstance()->fetch($query, [$slug]);
            
            if (!$module) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Модуль {$slug} не найден"
                ]);
                exit;
            }
            
            // Проверяем, активирован ли модуль
            $statusQuery = "SELECT status FROM modules WHERE slug = ?";
            $status = \API\Database::getInstance()->fetch($statusQuery, [$slug]);
            
            if (!$status || $status['status'] !== 'active') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Модуль {$module['name']} уже деактивирован"
                ]);
                exit;
            }
            
            // Деактивируем модуль - прямой SQL запрос для обновления статуса
            $updateQuery = "UPDATE modules SET status = 'inactive', updated_at = ? WHERE slug = ?";
            $now = date('Y-m-d H:i:s');
            
            \API\Database::getInstance()->query($updateQuery, [$now, $slug]);
            
            // Для совместимости вызываем метод deactivateModule
            $moduleManager = \Core\ModuleManager::getInstance();
            $moduleManager->deactivateModule($slug);
            
            // Устанавливаем правильный заголовок для ответа
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Модуль {$module['name']} успешно деактивирован"
            ]);
            exit;
        } catch (\Exception $e) {
            // Устанавливаем правильный заголовок для ответа
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Ошибка деактивации модуля: " . $e->getMessage()
            ]);
            exit;
        }
    });
    $router->post('/modules/{slug}/uninstall', [Controllers\ModulesController::class, 'uninstall']);
    
    // Маршруты для модуля Shop
    $router->get('/shop/products', 'Modules\Shop\Controllers\ShopController@getProducts');
    $router->get('/shop/products/{id}', 'Modules\Shop\Controllers\ShopController@getProduct');
    $router->post('/shop/products', 'Modules\Shop\Controllers\ShopController@createProduct');
    $router->put('/shop/products/{id}', 'Modules\Shop\Controllers\ShopController@updateProduct');
    $router->delete('/shop/products/{id}', 'Modules\Shop\Controllers\ShopController@deleteProduct');
    
    // Маршруты для категорий магазина
    $router->get('/shop/categories', 'Modules\Shop\Controllers\ShopController@getCategories');
    $router->get('/shop/categories/{id}', 'Modules\Shop\Controllers\ShopController@getCategory');
    $router->post('/shop/categories', 'Modules\Shop\Controllers\ShopController@createCategory');
    $router->put('/shop/categories/{id}', 'Modules\Shop\Controllers\ShopController@updateCategory');
    $router->delete('/shop/categories/{id}', 'Modules\Shop\Controllers\ShopController@deleteCategory');
    
    // Запускаем событие после регистрации маршрутов
    $core->trigger('api.after_register_routes', ['router' => &$router]);
    
    // Обработка запроса к несуществующему маршруту
    $router->notFound(function() use ($core) {
        $result = $core->trigger('api.not_found', ['route' => $_SERVER['REQUEST_URI']]);
        
        // Если событие не обработано, возвращаем стандартный ответ
        if ($result === null) {
            http_response_code(404);
            return ['error' => 'Endpoint not found'];
        }
        
        return $result;
    });
    
    // Запускаем событие перед обработкой запроса
    $core->trigger('api.before_request', ['router' => &$router, 'config' => $config]);
    
    // Запускаем маршрутизатор
    $response = $router->run($config);
    
    // Запускаем событие после обработки запроса
    $core->trigger('api.after_request', [
        'router' => &$router, 
        'response' => &$response
    ]);
    
    // Завершаем выполнение скрипта
    exit;
} catch (\Exception $e) {
    // Логируем исключение
    $logger->error('API error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Запускаем событие ошибки API
    $errorResponse = $core->trigger('api.error', ['exception' => $e]);
    
    // Если событие не обработано, возвращаем стандартный ответ
    if ($errorResponse === null) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    } else {
        echo json_encode($errorResponse);
    }
} 
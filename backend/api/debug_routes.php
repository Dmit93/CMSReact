<?php
header('Content-Type: application/json');

// Включаем режим отображения всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Загружаем необходимые файлы
require_once __DIR__ . '/Router.php';

// Расширяем класс Router для доступа к маршрутам
class DebugRouter extends \API\Router {
    public function getRoutes() {
        return $this->routes;
    }
    
    public function convertRouteToRegex($route) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return '/^' . str_replace('/', '\/', $pattern) . '$/';
    }
}

// Создаем экземпляр маршрутизатора
$router = new DebugRouter();

// Регистрируем тестовые маршруты
$router->get('/api/test', function() { return ['success' => true]; });
$router->post('/shop/products', 'Modules\Shop\Controllers\ShopController@createProduct');
$router->get('/shop/products', 'Modules\Shop\Controllers\ShopController@getProducts');

// Получаем все зарегистрированные маршруты
$routes = $router->getRoutes();

// Формируем информацию для отображения
$routeInfo = [];

foreach ($routes as $route) {
    $routeInfo[] = [
        'method' => $route['method'],
        'path' => $route['path'],
        'handler' => is_callable($route['handler']) ? 'Callable function' : json_encode($route['handler'])
    ];
}

// Затем загружаем actual маршруты из index.php
require_once __DIR__ . '/../core/init.php';

// Создаем экземпляр маршрутизатора для реальных маршрутов
$actualRouter = new DebugRouter();

// Напрямую добавляем маршрут для Shop
$actualRouter->post('/shop/products', 'Modules\Shop\Controllers\ShopController@createProduct');

// Тестируем сопоставление URL с маршрутом
$testUrl = '/shop/products';
$testMethod = 'POST';

$pattern = $actualRouter->convertRouteToRegex('/shop/products');
$matches = [];
$isMatch = preg_match($pattern, $testUrl, $matches);

// Выводим результаты
echo json_encode([
    'registered_routes' => $routeInfo,
    'test_url' => $testUrl,
    'test_method' => $testMethod,
    'pattern' => $pattern,
    'is_match' => $isMatch ? true : false,
    'matches' => $matches,
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'debug_note' => 'Проверка маршрутизации. Если is_match=true, то URL соответствует шаблону маршрута.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 
<?php
namespace API;

class Router {
    private $routes = [];
    private $notFoundHandler;
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    public function options($path, $handler) {
        $this->addRoute('OPTIONS', $path, $handler);
        return $this;
    }
    
    public function notFound($handler) {
        $this->notFoundHandler = $handler;
        return $this;
    }
    
    public function run($config) {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Для отладки
            error_log("Request: $method $uri");
            
            // Применяем базовый путь, если он есть
            $baseUrl = parse_url($config['base_url'], PHP_URL_PATH);
            
            if (!empty($baseUrl) && strpos($uri, $baseUrl) === 0) {
                $uri = substr($uri, strlen($baseUrl));
                error_log("URI after base URL processing: $uri");
            }
            
            // Добавляем слеш в начало, если его нет
            if (empty($uri)) {
                $uri = '/';
            } else if ($uri[0] !== '/') {
                $uri = '/' . $uri;
            }
            error_log("Final URI for routing: $uri");
            
            // CORS для предварительных запросов OPTIONS
            if ($method === 'OPTIONS') {
                exit; // Это обрабатывается в index.php
            }
            
            // Получаем данные из тела запроса для POST, PUT и DELETE запросов
            $requestData = null;
            if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
                $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
                
                if (strpos($contentType, 'application/json') !== false) {
                    $requestBody = file_get_contents('php://input');
                    error_log("Raw request body: " . $requestBody);
                    $requestData = json_decode($requestBody, true);
                    
                    // Проверка на ошибки при декодировании JSON
                    if ($requestData === null && json_last_error() !== JSON_ERROR_NONE) {
                        error_log("JSON decode error: " . json_last_error_msg());
                    }
                    
                    error_log("Decoded JSON data: " . json_encode($requestData));
                    
                    // Для PUT-запросов с content-types, добавим специальное логирование
                    if ($method === 'PUT' && strpos($uri, '/content-types/') === 0) {
                        error_log("PUT REQUEST DETECTED FOR CONTENT UPDATE!");
                        error_log("PUT REQUEST URI: " . $uri);
                        
                        // Проверяем обязательные поля
                        $requiredFields = ['title', 'slug'];
                        foreach ($requiredFields as $field) {
                            if (isset($requestData[$field])) {
                                error_log("PUT REQUEST: Field {$field} is present with value: " . $requestData[$field]);
                            } else {
                                error_log("PUT REQUEST: Field {$field} is MISSING!");
                            }
                        }
                        
                        // Логируем все поля в запросе
                        error_log("PUT REQUEST: All fields in request: " . json_encode(array_keys($requestData ?? [])));
                    }
                } else {
                    // Для других типов контента используем $_POST
                    $requestData = $_POST;
                    error_log("Using POST data: " . json_encode($requestData));
                    
                    // Для PUT-запросов без JSON
                    if ($method === 'PUT') {
                        error_log("PUT request with non-JSON content type: " . $contentType);
                        
                        // Пробуем получить данные напрямую
                        $rawData = file_get_contents('php://input');
                        error_log("Raw PUT data: " . $rawData);
                        
                        // Пробуем парсить как form-data
                        parse_str($rawData, $parsedData);
                        if (!empty($parsedData)) {
                            error_log("Parsed form data from PUT: " . json_encode($parsedData));
                            $requestData = $parsedData;
                        }
                    }
                }
            }
            
            // Ищем маршрут
            error_log("Looking for route match for: $method $uri");
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }
                
                $pattern = $this->convertRouteToRegex($route['path']);
                error_log("Checking route: " . $route['path'] . " with pattern: " . $pattern);
                
                if (preg_match($pattern, $uri, $matches)) {
                    error_log("Route matched: " . $route['path'] . " with matches: " . json_encode($matches));
                    array_shift($matches); // Удаляем полное совпадение
                    
                    // Для запросов к API с параметрами в URL
                    if (strpos($route['path'], '/content-types/{typeId}/content') === 0) {
                        error_log("Content creation route detected with typeId: " . json_encode($matches[0] ?? 'none'));
                        error_log("All matches for content route: " . json_encode($matches));
                        
                        // Проверяем и логируем первый параметр (typeId)
                        if (isset($matches[0])) {
                            error_log("typeId value: " . json_encode($matches[0]) . " (" . gettype($matches[0]) . ")");
                            
                            // Преобразуем в число для диагностики
                            if (is_numeric($matches[0])) {
                                $numericValue = (int)$matches[0];
                                error_log("typeId as number: " . json_encode($numericValue));
                            } else {
                                error_log("typeId is NOT numeric!");
                            }
                        } else {
                            error_log("typeId parameter is missing!");
                        }
                    }
                    
                    // Для PUT-запросов на обновление контента, обеспечиваем правильный порядок параметров
                    if ($method === 'PUT' && strpos($route['path'], '/content-types/{typeId}/content/{id}') === 0) {
                        error_log("PUT request for content update detected");
                        
                        // Параметры в правильном порядке: typeId, id, data
                        $typeId = isset($matches[0]) ? $matches[0] : null;
                        $id = isset($matches[1]) ? $matches[1] : null;
                        
                        error_log("Reorganizing parameters for content update - typeId: {$typeId}, id: {$id}");
                        
                        // Очищаем массив совпадений и восстанавливаем в правильном порядке
                        $matches = [];
                        $matches[] = $typeId;  // Первый параметр: typeId
                        $matches[] = $id;      // Второй параметр: id
                        $matches[] = $requestData; // Третий параметр: данные
                        
                        error_log("Final parameters for content update handler: " . json_encode($matches));
                    }
                    // Для DELETE-запросов на удаление контента, тоже обеспечиваем правильный порядок параметров
                    else if ($method === 'DELETE' && strpos($route['path'], '/content-types/{typeId}/content/{id}') === 0) {
                        error_log("DELETE request for content deletion detected");
                        
                        // Параметры в правильном порядке: typeId, id
                        $typeId = isset($matches[0]) ? $matches[0] : null;
                        $id = isset($matches[1]) ? $matches[1] : null;
                        
                        error_log("Reorganizing parameters for content deletion - typeId: {$typeId}, id: {$id}");
                        
                        // Очищаем массив совпадений и восстанавливаем в правильном порядке
                        $matches = [];
                        $matches[] = $typeId;  // Первый параметр: typeId
                        $matches[] = $id;      // Второй параметр: id
                        
                        error_log("Final parameters for content deletion handler: " . json_encode($matches));
                    }                    
                    // Для обычных POST, PUT, DELETE добавляем данные из тела запроса как первый параметр
                    else if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
                        // Для более четкого понимания типа запроса
                        if ($method === 'PUT') {
                            error_log("Router: Processing general PUT request for " . $route['path']);
                        } else if ($method === 'POST') {
                            error_log("Router: Processing general POST request for " . $route['path']);
                        }
                        
                        // Специальная обработка для создания товаров (POST /shop/products)
                        if ($method === 'POST' && $route['path'] === '/shop/products') {
                            error_log("Router: Обнаружен POST-запрос на создание товара");
                            
                            // Создаем специальный объект Request
                            $request = new Request();
                            
                            // Получаем данные из тела запроса
                            $rawBody = file_get_contents('php://input');
                            $jsonData = json_decode($rawBody, true);
                            
                            error_log("Router: Данные товара: " . ($jsonData ? json_encode($jsonData) : 'null'));
                            
                            // Устанавливаем данные в объект Request
                            $request->setJson($jsonData ?: []);
                            
                            // Устанавливаем параметры, которые будут переданы в handler
                            $params = [$request];
                            
                            error_log("Router: Подготовлены параметры для создания товара: " . json_encode($params));
                            
                            // Выполняем обработчик с подготовленными параметрами
                            $response = $this->executeHandler($route['handler'], $params);
                            return $response;
                        }
                        // Для всех остальных PUT/POST запросов используем стандартную логику обработки
                        else if ($method === 'PUT') {
                            $urlParams = $matches; // Сохраняем параметры из URL
                            error_log("Router: URL parameters from PUT request: " . json_encode($urlParams));
                            
                            // Сначала очищаем массив параметров и создаем Request объект
                            $matches = [];
                            $request = new Request();
                            $matches[] = $request;  // Первым параметром всегда идет Request объект
                            
                            // Затем добавляем все параметры из URL
                            foreach ($urlParams as $param) {
                                $matches[] = $param;
                            }
                            
                            // Затем добавляем данные из тела запроса как последний параметр
                            $matches[] = $requestData;
                            
                            error_log("Router: Final parameters for PUT handler: " . json_encode($matches));
                        }
                        // Специальная обработка для POST запросов к Shop API
                        else if ($method === 'POST' && (strpos($route['path'], '/shop/products') === 0 || strpos($route['path'], '/shop/categories') === 0)) {
                            error_log("Router: Processing POST request for Shop API: " . $route['path']);
                            
                            // Очищаем массив параметров и создаем Request объект
                            $matches = [];
                            $request = new Request();
                            
                            // Устанавливаем данные из тела запроса в объект Request
                            if ($requestData !== null) {
                                $request->setJson($requestData);
                                error_log("Router: JSON data set for Shop API request");
                            } else {
                                error_log("Router: Warning - no data in POST request body");
                            }
                            
                            // Добавляем Request объект как первый и единственный параметр
                            $matches[] = $request;
                            
                            error_log("Router: Final parameters for Shop POST handler: " . json_encode($matches));
                        } 
                        else {
                            error_log("Adding request data to parameters for $method request");
                            array_unshift($matches, $requestData);
                        }
                    }
                    
                    // Специальная обработка для маршрутов Shop
                    if (strpos($route['path'], '/shop/products/{id}') === 0) {
                        error_log("Shop product route detected with ID: " . json_encode($matches[0] ?? 'none'));
                        
                        // Для GET запроса к конкретному товару, добавляем объект Request
                        if ($method === 'GET') {
                            // Создаем объект Request
                            $request = new Request();
                            array_unshift($matches, $request);
                            error_log("Added Request object for shop/products/{id} GET");
                        }
                        // Для PUT запроса к товару правильно форматируем параметры
                        else if ($method === 'PUT') {
                            error_log("Processing PUT request for shop/products/{id}");
                            
                            // Сохраняем id товара
                            $productId = $matches[0] ?? null;
                            error_log("Product ID from URL: " . json_encode($productId));
                            
                            // Очищаем массив параметров
                            $matches = [];
                            
                            // Создаем объект Request
                            $request = new Request();
                            
                            // Устанавливаем данные JSON в Request
                            if ($requestData !== null) {
                                $request->setJson($requestData);
                            }
                            
                            // Добавляем параметры в правильном порядке
                            $matches[] = $request;      // Первым параметром Request объект
                            $matches[] = $productId;    // Вторым параметром ID товара
                            
                            error_log("Reordered parameters for shop/products/{id} PUT: Request, " . json_encode($productId));
                        }
                        // Для DELETE запроса к товару тоже правильно форматируем параметры
                        else if ($method === 'DELETE') {
                            error_log("Processing DELETE request for shop/products/{id}");
                            
                            // Сохраняем id товара
                            $productId = $matches[0] ?? null;
                            error_log("Product ID from URL: " . json_encode($productId));
                            
                            // Очищаем массив параметров
                            $matches = [];
                            
                            // Создаем объект Request
                            $request = new Request();
                            
                            // Добавляем параметры в правильном порядке
                            $matches[] = $request;      // Первым параметром Request объект
                            $matches[] = $productId;    // Вторым параметром ID товара
                            
                            error_log("Reordered parameters for shop/products/{id} DELETE: Request, " . json_encode($productId));
                        }
                    }
                    
                    error_log("Final parameters being passed to handler: " . json_encode($matches));
                    
                    // Вызываем обработчик
                    $handler = $route['handler'];
                    $response = $this->executeHandler($handler, $matches);
                    $this->sendResponse($response);
                    return;
                }
            }
            
            // Маршрут не найден
            error_log("No route found for: $method $uri");
            if ($this->notFoundHandler) {
                $response = $this->executeHandler($this->notFoundHandler);
                $this->sendResponse($response);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not Found']);
            }
        } catch (\Exception $e) {
            error_log("Exception in Router::run: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    private function convertRouteToRegex($route) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return '/^' . str_replace('/', '\/', $pattern) . '$/';
    }
    
    private function sendResponse($response) {
        // Определяем заголовок ответа
        header('Content-Type: application/json');
        
        // Кодируем и выводим ответ
        echo json_encode($response);
    }
    
    private function executeHandler($handler, $parameters = []) {
        // Добавляем отладочный вывод для проверки
        error_log("Router: Executing handler: " . json_encode($handler) . " with parameters: " . json_encode($parameters));
        
        if (is_callable($handler)) {
            // Если обработчик - функция, вызываем ее с параметрами
            return call_user_func_array($handler, $parameters);
        } else if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            // Если обработчик - массив [Class, method]
            error_log("Router: Executing class method: {$handler[0]}::{$handler[1]}");
            
            // Создаем экземпляр класса
            $class = $handler[0];
            $method = $handler[1];
            
            try {
                $instance = new $class();
                
                // Вызываем метод класса
                return call_user_func_array([$instance, $method], $parameters);
            } catch (\Exception $e) {
                error_log("Router: Error creating class instance: " . $e->getMessage());
                return ['error' => 'Internal server error: ' . $e->getMessage()];
            }
        } else if (is_string($handler) && strpos($handler, '@') !== false) {
            // Если обработчик - строка вида "Class@method"
            error_log("Router: Executing string handler: " . json_encode($handler));
            
            list($class, $method) = explode('@', $handler, 2);
            error_log("Router: Class: " . json_encode($class) . ", Method: " . json_encode($method));
            
            try {
                if (!class_exists($class)) {
                    error_log("Router: Class not found: " . json_encode($class));
                    return ['error' => "Controller class not found: $class"];
                }
                
                // Создаем экземпляр класса
                $instance = new $class();
                
                if (!method_exists($instance, $method)) {
                    error_log("Router: Method not found: " . json_encode($method) . " in class " . json_encode($class));
                    return ['error' => "Controller method not found: $method"];
                }
                
                // Вызываем метод класса
                return call_user_func_array([$instance, $method], $parameters);
            } catch (\Exception $e) {
                error_log("Router: Error executing handler: " . $e->getMessage() . ", trace: " . $e->getTraceAsString());
                return ['error' => 'Internal server error: ' . $e->getMessage()];
            }
        }
        
        // Если неизвестный тип обработчика
        error_log("Router: Unknown handler type: " . gettype($handler));
        return ['error' => 'Invalid handler'];
    }
    
    private function handleCors($config) {
        // Пустой метод, так как CORS заголовки должны обрабатываться только в index.php
        return;
    }
} 
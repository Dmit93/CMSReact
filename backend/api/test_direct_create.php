<?php
header('Content-Type: application/json');

// Включаем режим отображения всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Загружаем необходимые файлы
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../modules/Shop/models/ProductModel.php';
require_once __DIR__ . '/../modules/Shop/controllers/ShopController.php';

// Создаем тестовые данные товара
$productData = [
    'title' => 'Прямой тест товара ' . date('Y-m-d H:i:s'),
    'sku' => 'DIRECT-' . rand(1000, 9999),
    'price' => 199.99,
    'stock' => 15,
    'status' => 'published',
    'description' => 'Товар создан напрямую через PHP скрипт, в обход API'
];

try {
    // Создаем объект Request и устанавливаем в него тестовые данные
    $request = new \API\Request();
    $request->setJson($productData);
    
    // Создаем контроллер и вызываем метод создания товара
    $controller = new \Modules\Shop\Controllers\ShopController();
    $response = $controller->createProduct($request);
    
    // Выводим результат
    echo "Результат создания товара напрямую через контроллер:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (\Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Произошла ошибка: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// Проверяем маршрутизацию
echo "\n\nПроверка маршрутизации:\n";

// Получаем все маршруты из Router
$router = new \API\Router();

// Добавляем тестовый маршрут для проверки
$router->post('/test', function() {
    return ['success' => true, 'message' => 'Тестовый маршрут работает'];
});

// Регистрируем маршрут магазина
$router->post('/shop/products', '\Modules\Shop\Controllers\ShopController@createProduct');

// Симулируем запрос к API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/shop/products';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Получаем тело запроса
$requestBody = json_encode($productData);
file_put_contents('php://input', $requestBody);

// Запускаем маршрутизатор
$config = [
    'base_url' => '',
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
    ]
];

try {
    echo "Запуск маршрутизатора для POST /shop/products...\n";
    $router->run($config);
} catch (\Exception $e) {
    echo "Ошибка при запуске маршрутизатора: " . $e->getMessage() . "\n";
    echo "Трейс: " . $e->getTraceAsString();
} 
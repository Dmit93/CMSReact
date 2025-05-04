<?php
/**
 * Тестовый скрипт для инициализации таблиц модуля Shop
 */

header('Content-Type: application/json');

// Базовые настройки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Включаем необходимые файлы
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/Request.php";
require_once __DIR__ . "/Response.php"; 
require_once __DIR__ . "/Database.php";
require_once __DIR__ . "/../modules/Shop/models/ProductModel.php";
require_once __DIR__ . "/../modules/Shop/controllers/ShopController.php";

// Создаем экземпляр контроллера
$controller = new \Modules\Shop\Controllers\ShopController();

// Данные для теста
$testProduct = [
    'title' => 'Тестовый товар ' . date('Y-m-d H:i:s'),
    'sku' => 'TEST-' . rand(1000, 9999),
    'price' => 99.99,
    'stock' => 10,
    'status' => 'published',
    'description' => 'Это тестовый товар для проверки API'
];

// Логируем тестовые данные
error_log("Тестовые данные для создания товара: " . json_encode($testProduct));

try {
    // Создаем запрос
    $request = new \API\Request();
    $request->setJson($testProduct);
    
    // Вызываем метод создания товара
    $response = $controller->createProduct($request);
    
    // Выводим результат
    echo json_encode([
        'status' => 'success',
        'message' => 'Тест создания товара выполнен',
        'request_data' => $testProduct,
        'response' => $response
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    // В случае ошибки выводим информацию
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при выполнении теста',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString() 
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} 
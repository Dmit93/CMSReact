<?php
/**
 * Тестовый скрипт для отладки запросов товаров
 */

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Устанавливаем заголовки для JSON
header('Content-Type: application/json');

// Подключаем необходимые файлы
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Core.php';
require_once __DIR__ . '/../core/ModuleManager.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../modules/Shop/models/ProductModel.php';

try {
    // Инициализируем модель ProductModel
    $productModel = new \Modules\Shop\Models\ProductModel();
    
    // Вызываем метод отладки
    $debugResult = $productModel->debugQuery();
    
    // Возвращаем результат в формате JSON
    echo json_encode([
        'success' => true,
        'debug' => $debugResult
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // Возвращаем ошибку в формате JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} 
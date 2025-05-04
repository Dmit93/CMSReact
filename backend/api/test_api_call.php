<?php
header('Content-Type: application/json');

// Включаем режим отображения всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Функция для отправки запроса к API
function callApi($method, $endpoint, $data = null) {
    // Базовый URL API
    $baseUrl = "http://localhost/cms/backend/api";
    $url = $baseUrl . $endpoint;
    
    // Инициализация cURL сессии
    $ch = curl_init($url);
    
    // Опции cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Заголовки запроса
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Если есть данные, добавляем их в запрос
    if ($data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        echo "Отправляемые данные: " . $jsonData . "\n";
    }
    
    // Выполнение запроса
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Закрыть сессию cURL
    curl_close($ch);
    
    echo "HTTP статус: " . $httpCode . "\n";
    
    if ($error) {
        echo "Ошибка cURL: " . $error . "\n";
        return null;
    }
    
    return $response;
}

// Данные для создания товара
$productData = [
    'title' => 'API Test Product ' . date('Y-m-d H:i:s'),
    'sku' => 'API-' . rand(1000, 9999),
    'price' => 123.45,
    'stock' => 5,
    'status' => 'published',
    'description' => 'Тестовый товар созданный через API test'
];

// Выполняем POST запрос для создания товара
echo "Отправка POST запроса на /shop/products\n";
$response = callApi('POST', '/shop/products', $productData);

// Выводим результат
echo "\nРезультат:\n";
echo $response; 
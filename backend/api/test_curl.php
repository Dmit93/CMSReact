<?php
header('Content-Type: text/plain');

// Включаем режим отображения всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Генерируем уникальный SKU
$sku = 'TEST-' . time();

// Создаем данные товара
$data = [
    'title' => 'PHP CURL Test Product ' . date('Y-m-d H:i:s'),
    'sku' => $sku,
    'price' => 99.99,
    'stock' => 10,
    'status' => 'published',
    'description' => 'Товар создан через PHP CURL-запрос'
];

// Конвертируем в JSON
$jsonData = json_encode($data);

echo "Отправляем данные:\n";
echo $jsonData . "\n\n";

// Настраиваем и выполняем CURL запрос
$ch = curl_init('http://localhost/cms/backend/api/shop/products');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Выполняем запрос
echo "Отправка запроса на http://localhost/cms/backend/api/shop/products...\n";
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);

// Закрываем соединение
curl_close($ch);

// Выводим результаты
echo "\nСтатус: " . $info['http_code'] . "\n";
if ($error) {
    echo "Ошибка: " . $error . "\n";
}

echo "\nОтвет сервера:\n";
echo $response; 
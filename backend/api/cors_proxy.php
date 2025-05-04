<?php
// Включаем отображение ошибок в режиме разработки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Разрешаем запросы с любого источника (для разработки)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Проверка pre-flight запросов OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// Получаем путь запроса
$requestPath = $_SERVER['REQUEST_URI'];
$basePath = '/cms/backend/api/cors_proxy.php';

// Извлекаем реальный путь API (удаляем путь прокси из URL)
$apiPath = str_replace($basePath, '', $requestPath);
if (empty($apiPath)) {
    $apiPath = '/';
}

// Получаем тело запроса
$requestBody = file_get_contents('php://input');

// Устанавливаем базовый URL API (может быть изменен для разных сред)
$apiBaseUrl = 'http://localhost/cms/backend/api';
$apiUrl = $apiBaseUrl . $apiPath;

// Логируем запрос
error_log("CORS Proxy: Forwarding " . $_SERVER['REQUEST_METHOD'] . " request to: " . $apiUrl);
if (!empty($requestBody)) {
    error_log("Request body: " . $requestBody);
}

// Инициализируем cURL
$ch = curl_init($apiUrl);

// Устанавливаем опции cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Передаем заголовки запроса
$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) != 'host' && strtolower($name) != 'origin' && strtolower($name) != 'referer') {
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Передаем тело запроса для методов POST, PUT, PATCH
if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'PATCH') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
}

// Выполняем запрос
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Проверка на ошибки
if ($response === false) {
    error_log("CORS Proxy error: " . curl_error($ch));
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Proxy error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Закрываем cURL
curl_close($ch);

// Устанавливаем HTTP-код ответа
http_response_code($httpCode);

// Устанавливаем заголовок Content-Type, если он есть
if (!empty($contentType)) {
    header('Content-Type: ' . $contentType);
} else {
    header('Content-Type: application/json');
}

// Выводим тело ответа
echo $response; 
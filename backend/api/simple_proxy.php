<?php
// Отключаем вывод ошибок для предотвращения добавления лишнего контента
error_reporting(0);
ini_set('display_errors', 0);

// Очищаем все существующие CORS заголовки
foreach (headers_list() as $header) {
    if (strpos($header, 'Access-Control-') === 0) {
        $header_name = strstr($header, ':', true);
        if ($header_name) {
            header_remove($header_name);
        }
    }
}

// Устанавливаем только один CORS заголовок
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// Получаем целевой путь
$target_path = str_replace('/cms/backend/api/simple_proxy.php', '', $_SERVER['REQUEST_URI']);
$api_url = 'http://localhost/cms/backend/api' . $target_path;

// Получаем содержимое запроса
$request_body = file_get_contents('php://input');

// Инициализируем cURL
$ch = curl_init($api_url);

// Устанавливаем параметры запроса
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Если это POST или PUT, добавляем данные
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
}

// Выполняем запрос
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Закрываем соединение
curl_close($ch);

// Отправляем статус и ответ
http_response_code($status);
header('Content-Type: application/json');
echo $response; 
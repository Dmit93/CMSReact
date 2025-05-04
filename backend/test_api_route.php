<?php
// Отключаем буферизацию вывода
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Устанавливаем заголовки для текстового вывода
header('Content-Type: text/plain; charset=utf-8');

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Создаем файл для логирования
$logFile = __DIR__ . '/logs/api_route_test.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Начало теста API маршрутизации\n");

// Функция для логирования
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "{$timestamp} - {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    // Получаем ID записи для обновления и ID типа контента из параметров
    $contentId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
    $typeId = isset($_GET['type']) ? (int)$_GET['type'] : 2;
    
    logMessage("Тестирование API маршрутизации для обновления записи. TypeID: {$typeId}, ContentID: {$contentId}");
    
    // Загружаем файлы и классы, необходимые для теста
    require_once __DIR__ . '/config/bootstrap.php';
    
    // Создаем экземпляр модели контента для проверки текущих данных
    $contentModel = new \Models\ContentModel();
    $content = $contentModel->getById($contentId);
    
    if (!$content) {
        logMessage("Ошибка: Запись с ID {$contentId} не найдена");
        exit;
    }
    
    logMessage("Текущие данные записи: " . json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Подготавливаем данные для обновления
    $updateData = [
        'title' => 'Тест API маршрутизации - ' . date('H:i:s'),
        'slug' => 'api-route-test-' . time(),
        'content_type_id' => $typeId,
        'description' => 'Тестовое описание от ' . date('Y-m-d H:i:s'),
        'test' => 'Тестовое значение ' . date('H:i:s')
    ];
    
    logMessage("Данные для обновления: " . json_encode($updateData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Тестируем прямой вызов контроллера
    logMessage("\n=== ТЕСТ 1: Прямой вызов контроллера ===");
    $controller = new \Controllers\ContentController();
    $controllerResult = $controller->update($typeId, $contentId, $updateData);
    
    logMessage("Результат прямого вызова контроллера: " . json_encode($controllerResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Проверяем обновление данных
    $updatedContent = $contentModel->getById($contentId);
    logMessage("Обновленные данные после прямого вызова: " . json_encode($updatedContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Тестируем симуляцию HTTP PUT-запроса через cURL
    logMessage("\n=== ТЕСТ 2: Симуляция HTTP PUT-запроса ===");
    
    // Формируем URL для запроса
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    $apiUrl = $baseUrl . dirname($_SERVER['PHP_SELF']) . '/api/content-types/' . $typeId . '/content/' . $contentId;
    
    logMessage("URL для отправки запроса: " . $apiUrl);
    
    // Подготавливаем новые данные для PUT-запроса
    $putData = [
        'title' => 'Тест PUT запроса - ' . date('H:i:s'),
        'slug' => 'put-request-test-' . time(),
        'content_type_id' => $typeId,
        'description' => 'Описание через PUT от ' . date('Y-m-d H:i:s'),
        'test' => 'Значение через PUT ' . date('H:i:s')
    ];
    
    logMessage("Данные для PUT-запроса: " . json_encode($putData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Отправляем PUT-запрос через cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($putData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($putData))
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logMessage("Ошибка cURL: " . $error);
    } else {
        logMessage("HTTP код ответа: " . $httpCode);
        logMessage("Ответ сервера: " . $response);
        
        // Проверяем результат из ответа
        $responseData = json_decode($response, true);
        if (isset($responseData['success']) && $responseData['success']) {
            logMessage("PUT-запрос успешно выполнен!");
        } else {
            logMessage("PUT-запрос не удался: " . ($responseData['message'] ?? 'Неизвестная ошибка'));
        }
    }
    
    // Проверяем данные после PUT-запроса
    $contentAfterPut = $contentModel->getById($contentId);
    logMessage("Данные после PUT-запроса: " . json_encode($contentAfterPut, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Проверяем обновление полей после PUT-запроса
    $testUpdatedFields = ['title', 'slug', 'description', 'test'];
    $fieldsUpdated = [];
    $fieldsNotUpdated = [];
    
    foreach ($testUpdatedFields as $field) {
        if ($field === 'title' || $field === 'slug') {
            // Проверяем стандартные поля
            if (isset($contentAfterPut[$field]) && strpos($contentAfterPut[$field], substr($putData[$field], 0, 10)) !== false) {
                $fieldsUpdated[] = $field;
            } else {
                $fieldsNotUpdated[] = $field;
            }
        } else {
            // Проверяем пользовательские поля
            if (isset($contentAfterPut['fields'][$field]) && strpos($contentAfterPut['fields'][$field]['value'], substr($putData[$field], 0, 10)) !== false) {
                $fieldsUpdated[] = $field;
            } else {
                $fieldsNotUpdated[] = $field;
            }
        }
    }
    
    logMessage("Обновленные поля после PUT-запроса: " . implode(', ', $fieldsUpdated));
    
    if (!empty($fieldsNotUpdated)) {
        logMessage("Не обновились поля после PUT-запроса: " . implode(', ', $fieldsNotUpdated));
    } else {
        logMessage("Все поля успешно обновлены через PUT-запрос!");
    }
    
    logMessage("Тест завершен успешно");
    
} catch (Exception $e) {
    logMessage("Ошибка: " . $e->getMessage());
    logMessage("Стек вызовов: " . $e->getTraceAsString());
} 
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
$logFile = __DIR__ . '/logs/delete_test.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Начало теста удаления\n");

// Функция для логирования
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "{$timestamp} - {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    // Функция для создания тестовой записи
    function createTestContent($typeId) {
        global $logFile;
        
        logMessage("Создаем тестовую запись для удаления");
        
        // Загружаем классы
        require_once __DIR__ . '/config/bootstrap.php';
        
        // Создаем экземпляр контроллера
        $controller = new \Controllers\ContentController();
        
        // Данные для тестовой записи
        $data = [
            'title' => 'Тестовая запись для удаления - ' . date('H:i:s'),
            'slug' => 'test-delete-' . time(),
            'content_type_id' => $typeId,
            'description' => 'Тестовое описание записи для удаления',
            'status' => 'draft'
        ];
        
        logMessage("Данные тестовой записи: " . json_encode($data, JSON_UNESCAPED_UNICODE));
        
        // Создаем запись через контроллер
        $result = $controller->create($data, $typeId);
        
        if (!$result['success']) {
            logMessage("Ошибка при создании тестовой записи: " . $result['message']);
            return null;
        }
        
        logMessage("Тестовая запись успешно создана, ID: " . $result['data']['id']);
        return $result['data'];
    }
    
    // Получаем ID типа контента из параметра или используем значение по умолчанию
    $typeId = isset($_GET['type']) ? (int)$_GET['id'] : 2;
    
    // Создаем тестовую запись для удаления
    $testContent = createTestContent($typeId);
    
    if (!$testContent) {
        logMessage("Не удалось создать тестовую запись. Тест прерван.");
        exit;
    }
    
    $contentId = $testContent['id'];
    
    logMessage("=== ТЕСТ 1: Прямой вызов контроллера для удаления ===");
    
    // Создаем экземпляр контроллера
    $controller = new \Controllers\ContentController();
    
    // Проверяем наличие записи перед удалением
    $contentModel = new \Models\ContentModel();
    $contentBeforeDelete = $contentModel->getById($contentId);
    
    if (!$contentBeforeDelete) {
        logMessage("Ошибка: Запись с ID {$contentId} не найдена перед удалением");
        exit;
    }
    
    logMessage("Запись перед удалением: " . json_encode($contentBeforeDelete, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Удаляем запись через прямой вызов контроллера
    $deleteResult = $controller->delete($typeId, $contentId);
    
    logMessage("Результат удаления через контроллер: " . json_encode($deleteResult, JSON_UNESCAPED_UNICODE));
    
    // Проверяем, что запись действительно удалена
    $contentAfterDelete = $contentModel->getById($contentId);
    
    if ($contentAfterDelete) {
        logMessage("ОШИБКА: Запись не была удалена, всё ещё доступна: " . json_encode($contentAfterDelete, JSON_UNESCAPED_UNICODE));
    } else {
        logMessage("Запись успешно удалена через прямой вызов контроллера");
    }
    
    logMessage("\n=== ТЕСТ 2: Удаление через API (DELETE-запрос) ===");
    
    // Создаем новую тестовую запись для удаления через API
    $testContent2 = createTestContent($typeId);
    
    if (!$testContent2) {
        logMessage("Не удалось создать вторую тестовую запись. Тест API пропущен.");
        exit;
    }
    
    $contentId2 = $testContent2['id'];
    
    // Формируем URL для DELETE-запроса
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    $apiUrl = $baseUrl . dirname($_SERVER['PHP_SELF']) . '/api/content-types/' . $typeId . '/content/' . $contentId2;
    
    logMessage("URL для DELETE-запроса: " . $apiUrl);
    
    // Отправляем DELETE-запрос через cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
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
            logMessage("DELETE-запрос успешно выполнен!");
        } else {
            logMessage("DELETE-запрос не удался: " . ($responseData['message'] ?? 'Неизвестная ошибка'));
        }
    }
    
    // Проверяем, что запись действительно удалена через API
    $contentAfterApiDelete = $contentModel->getById($contentId2);
    
    if ($contentAfterApiDelete) {
        logMessage("ОШИБКА: Запись не была удалена через API, всё ещё доступна: " . json_encode($contentAfterApiDelete, JSON_UNESCAPED_UNICODE));
    } else {
        logMessage("Запись успешно удалена через API");
    }
    
    logMessage("Тест удаления завершен успешно");
    
} catch (Exception $e) {
    logMessage("Ошибка: " . $e->getMessage());
    logMessage("Стек вызовов: " . $e->getTraceAsString());
} 
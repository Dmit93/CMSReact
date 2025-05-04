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
$logFile = __DIR__ . '/logs/test_update.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Начало теста обновления\n");

// Функция для логирования
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "{$timestamp} - {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    // Загружаем необходимые классы
    require_once __DIR__ . '/config/bootstrap.php';
    
    logMessage("Классы успешно загружены");
    
    // Создаем экземпляр ContentModel
    $contentModel = new \Models\ContentModel();
    
    // Получаем ID записи из параметра или используем тестовый ID
    $contentId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
    
    logMessage("Тестируем обновление записи с ID: {$contentId}");
    
    // Получаем текущие данные записи
    $currentContent = $contentModel->getById($contentId);
    
    if (!$currentContent) {
        logMessage("Ошибка: Запись с ID {$contentId} не найдена");
        exit;
    }
    
    logMessage("Текущие данные записи: " . json_encode($currentContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Подготавливаем данные для обновления
    $updateData = [
        'title' => 'Обновленный заголовок - ' . date('H:i:s'),
        'slug' => 'updated-slug-' . time(),
        'status' => 'published',
        'description' => 'Тестовое описание ' . date('Y-m-d H:i:s'),
        'test' => 'Тестовое значение ' . date('H:i:s')
    ];
    
    logMessage("Данные для обновления: " . json_encode($updateData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Выполняем обновление
    $result = $contentModel->update($contentId, $updateData);
    
    logMessage("Результат обновления: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Получаем обновленные данные записи
    $updatedContent = $contentModel->getById($contentId);
    
    logMessage("Обновленные данные записи: " . json_encode($updatedContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Проверяем, обновились ли поля
    $fieldsUpdated = [];
    $fieldsNotUpdated = [];
    
    foreach ($updateData as $field => $value) {
        if ($field === 'title' || $field === 'slug' || $field === 'status') {
            // Для стандартных полей проверяем прямо в данных
            if (isset($updatedContent[$field])) {
                if (strpos($updatedContent[$field], substr($value, 0, 10)) !== false) {
                    $fieldsUpdated[] = $field;
                } else {
                    $fieldsNotUpdated[] = $field;
                }
            } else {
                $fieldsNotUpdated[] = $field;
            }
        } else {
            // Для пользовательских полей проверяем в fields
            if (isset($updatedContent['fields'][$field])) {
                if (strpos($updatedContent['fields'][$field]['value'], substr($value, 0, 10)) !== false) {
                    $fieldsUpdated[] = $field;
                } else {
                    $fieldsNotUpdated[] = $field;
                }
            } else {
                $fieldsNotUpdated[] = $field;
            }
        }
    }
    
    logMessage("Обновленные поля: " . implode(', ', $fieldsUpdated));
    
    if (!empty($fieldsNotUpdated)) {
        logMessage("Не обновились поля: " . implode(', ', $fieldsNotUpdated));
    } else {
        logMessage("Все поля успешно обновлены!");
    }
    
    logMessage("Тест завершен успешно");
    
} catch (Exception $e) {
    logMessage("Ошибка: " . $e->getMessage());
    logMessage("Стек вызовов: " . $e->getTraceAsString());
} 
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
$logFile = __DIR__ . '/logs/test_fields.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Начало теста полей\n");

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
    
    logMessage("Тестируем обновление полей для записи с ID: {$contentId}");
    
    // Получаем текущие данные записи
    $currentContent = $contentModel->getById($contentId);
    
    if (!$currentContent) {
        logMessage("Ошибка: Запись с ID {$contentId} не найдена");
        exit;
    }
    
    logMessage("Текущие поля записи:");
    if (isset($currentContent['fields'])) {
        foreach ($currentContent['fields'] as $fieldName => $fieldData) {
            logMessage("  {$fieldName}: " . $fieldData['value']);
        }
    } else {
        logMessage("  У записи нет пользовательских полей");
    }
    
    // Создаем тестовые поля
    $testFields = [
        'description' => 'Описание записи - ' . date('H:i:s'),
        'test' => 'Тестовое значение ' . date('H:i:s'),
        'custom_field' => 'Пользовательское поле ' . date('H:i:s'),
        'numeric_field' => mt_rand(1000, 9999)
    ];
    
    logMessage("Данные для обновления полей:");
    foreach ($testFields as $fieldName => $fieldValue) {
        logMessage("  {$fieldName}: {$fieldValue}");
    }
    
    // Вызываем напрямую метод saveFieldValues через рефлексию
    $reflectionClass = new ReflectionClass('\Models\ContentModel');
    $method = $reflectionClass->getMethod('saveFieldValues');
    $method->setAccessible(true);
    
    $result = $method->invoke($contentModel, $contentId, $testFields);
    
    logMessage("Результат обновления полей: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Получаем обновленные данные записи
    $updatedContent = $contentModel->getById($contentId);
    
    logMessage("Обновленные поля записи:");
    if (isset($updatedContent['fields'])) {
        foreach ($updatedContent['fields'] as $fieldName => $fieldData) {
            logMessage("  {$fieldName}: " . $fieldData['value']);
        }
    } else {
        logMessage("  У записи нет пользовательских полей после обновления");
    }
    
    // Проверяем, обновились ли поля
    $fieldsUpdated = [];
    $fieldsNotUpdated = [];
    
    foreach ($testFields as $field => $value) {
        if (isset($updatedContent['fields'][$field])) {
            if ($updatedContent['fields'][$field]['value'] == $value) {
                $fieldsUpdated[] = $field;
            } else {
                $fieldsNotUpdated[] = $field;
                logMessage("  Ошибка поля {$field}: ожидалось '{$value}', получено '{$updatedContent['fields'][$field]['value']}'");
            }
        } else {
            $fieldsNotUpdated[] = $field;
            logMessage("  Ошибка поля {$field}: поле отсутствует в обновленных данных");
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
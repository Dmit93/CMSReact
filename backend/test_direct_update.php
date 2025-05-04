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
$logFile = __DIR__ . '/logs/direct_update.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Начало прямого теста обновления\n");

// Функция для логирования
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "{$timestamp} - {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    // Загружаем конфигурацию базы данных
    $config = include __DIR__ . '/config/database.php';
    
    logMessage("Загружена конфигурация базы данных");
    
    // Создаем PDO-соединение
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    logMessage("Соединение с базой данных установлено");
    
    // Получаем ID записи для обновления
    $contentId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
    logMessage("Тестирование прямого обновления записи ID: {$contentId}");
    
    // Проверяем существование записи
    $checkStmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $checkStmt->execute([$contentId]);
    $currentContent = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentContent) {
        logMessage("Ошибка: Запись с ID {$contentId} не найдена");
        exit;
    }
    
    logMessage("Текущие данные записи: " . json_encode($currentContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Прямое обновление через SQL
    $title = "Прямое обновление - " . date('H:i:s');
    $slug = "direct-update-" . time();
    $timestamp = date('Y-m-d H:i:s');
    
    $sql = "UPDATE content SET 
            title = ?, 
            slug = ?, 
            updated_at = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$title, $slug, $timestamp, $contentId]);
    $rowCount = $stmt->rowCount();
    
    logMessage("SQL запрос: {$sql}");
    logMessage("Параметры: " . json_encode([$title, $slug, $timestamp, $contentId]));
    logMessage("Результат выполнения: " . ($result ? "Успешно" : "Ошибка"));
    logMessage("Затронуто строк: {$rowCount}");
    
    // Проверяем результат обновления
    $checkStmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $checkStmt->execute([$contentId]);
    $updatedContent = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    logMessage("Обновленные данные записи: " . json_encode($updatedContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Проверяем обновление полей
    $fieldsUpdated = [];
    $fieldsNotUpdated = [];
    
    if ($updatedContent['title'] == $title) {
        $fieldsUpdated[] = 'title';
    } else {
        $fieldsNotUpdated[] = 'title';
        logMessage("Поле title не обновилось. Ожидалось: {$title}, получено: {$updatedContent['title']}");
    }
    
    if ($updatedContent['slug'] == $slug) {
        $fieldsUpdated[] = 'slug';
    } else {
        $fieldsNotUpdated[] = 'slug';
        logMessage("Поле slug не обновилось. Ожидалось: {$slug}, получено: {$updatedContent['slug']}");
    }
    
    if (strtotime($updatedContent['updated_at']) >= strtotime($timestamp)) {
        $fieldsUpdated[] = 'updated_at';
    } else {
        $fieldsNotUpdated[] = 'updated_at';
        logMessage("Поле updated_at не обновилось. Ожидалось: {$timestamp}, получено: {$updatedContent['updated_at']}");
    }
    
    logMessage("Обновленные поля: " . implode(', ', $fieldsUpdated));
    
    if (!empty($fieldsNotUpdated)) {
        logMessage("Не обновились поля: " . implode(', ', $fieldsNotUpdated));
    } else {
        logMessage("Все поля успешно обновлены!");
    }
    
    // Теперь обновляем пользовательские поля
    logMessage("Тестирование обновления пользовательских полей");
    
    // Получаем поля для content_id
    $fieldsStmt = $pdo->prepare("
        SELECT cfv.*, ctf.name FROM content_field_values cfv
        JOIN content_type_fields ctf ON cfv.field_id = ctf.id
        WHERE cfv.content_id = ?
    ");
    $fieldsStmt->execute([$contentId]);
    $currentFields = $fieldsStmt->fetchAll();
    
    logMessage("Текущие пользовательские поля: " . json_encode($currentFields, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Обновляем первое поле, если оно существует
    if (!empty($currentFields)) {
        $fieldToUpdate = $currentFields[0];
        $newValue = "Прямое обновление поля " . date('H:i:s');
        
        $updateFieldSql = "UPDATE content_field_values SET value = ?, updated_at = ? WHERE id = ?";
        $updateFieldStmt = $pdo->prepare($updateFieldSql);
        $updateFieldResult = $updateFieldStmt->execute([$newValue, date('Y-m-d H:i:s'), $fieldToUpdate['id']]);
        $fieldRowCount = $updateFieldStmt->rowCount();
        
        logMessage("Обновление поля '{$fieldToUpdate['name']}' (ID: {$fieldToUpdate['id']})");
        logMessage("SQL запрос: {$updateFieldSql}");
        logMessage("Параметры: " . json_encode([$newValue, date('Y-m-d H:i:s'), $fieldToUpdate['id']]));
        logMessage("Результат выполнения: " . ($updateFieldResult ? "Успешно" : "Ошибка"));
        logMessage("Затронуто строк: {$fieldRowCount}");
        
        // Проверяем обновление поля
        $checkFieldStmt = $pdo->prepare("SELECT * FROM content_field_values WHERE id = ?");
        $checkFieldStmt->execute([$fieldToUpdate['id']]);
        $updatedField = $checkFieldStmt->fetch();
        
        logMessage("Обновленное поле: " . json_encode($updatedField, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        if ($updatedField['value'] == $newValue) {
            logMessage("Поле успешно обновлено!");
        } else {
            logMessage("Поле не обновилось. Ожидалось: {$newValue}, получено: {$updatedField['value']}");
        }
    } else {
        logMessage("У записи нет пользовательских полей для обновления");
    }
    
    logMessage("Тест завершен успешно");
    
} catch (Exception $e) {
    logMessage("Ошибка: " . $e->getMessage());
    logMessage("Стек вызовов: " . $e->getTraceAsString());
} 
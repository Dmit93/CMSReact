<?php
/**
 * Тестовый скрипт для проверки обновления записей и логирования
 */

// Устанавливаем кодировку UTF-8
header('Content-Type: text/html; charset=utf-8');

// Включаем вывод всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Создаем лог-файл для этого теста
$logFile = __DIR__ . '/../backend/logs/test_update.log';
file_put_contents($logFile, "=== НАЧАЛО ТЕСТИРОВАНИЯ (" . date('Y-m-d H:i:s') . ") ===\n", FILE_APPEND);

// Функция для записи в лог с корректной кодировкой
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    
    // Нормализуем сообщение
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    // Проверяем кодировку и конвертируем при необходимости
    if (!mb_check_encoding($message, 'UTF-8')) {
        $message = mb_convert_encoding($message, 'UTF-8', 'auto');
        $message .= ' [сконвертировано в UTF-8]';
    }
    
    // Записываем в лог
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    
    // Выводим на экран
    echo "[{$timestamp}] {$message}<br>";
}

// 1. Загружаем необходимые файлы
try {
    require_once __DIR__ . '/../backend/api/Database.php';
    require_once __DIR__ . '/../backend/models/ContentModel.php';
    
    writeLog("Файлы успешно загружены");
} catch (Exception $e) {
    writeLog("Ошибка при загрузке файлов: " . $e->getMessage());
    die("Ошибка при загрузке необходимых файлов");
}

// 2. Проверяем подключение к базе данных
try {
    $db = \API\Database::getInstance();
    $connection = $db->getConnection();
    
    if ($connection) {
        writeLog("Подключение к базе данных успешно установлено");
    } else {
        writeLog("Не удалось подключиться к базе данных");
        die("Ошибка подключения к базе данных");
    }
} catch (Exception $e) {
    writeLog("Ошибка при подключении к БД: " . $e->getMessage());
    die("Ошибка при подключении к базе данных");
}

// 3. Тестируем обновление записи
try {
    $contentModel = new \Models\ContentModel();
    
    // Проверяем наличие существующей тестовой записи
    $testContentId = $_GET['id'] ?? null;
    $testContent = null;
    
    if ($testContentId) {
        $testContent = $contentModel->getById($testContentId);
        writeLog("Проверка наличия записи с ID {$testContentId}: " . ($testContent ? "Найдена" : "Не найдена"));
    }
    
    // Если не указан ID или запись не найдена, создаем новую тестовую запись
    if (!$testContent) {
        writeLog("Создаем новую тестовую запись...");
        
        // Получаем первый доступный тип контента
        $contentTypes = $db->fetchAll("SELECT id FROM content_types LIMIT 1");
        
        if (empty($contentTypes)) {
            writeLog("Ошибка: не найдены типы контента в базе данных");
            die("Ошибка: не найдены типы контента");
        }
        
        $contentTypeId = $contentTypes[0]['id'];
        writeLog("Используем тип контента с ID: {$contentTypeId}");
        
        $createData = [
            'content_type_id' => $contentTypeId,
            'title' => 'Тестовая запись ' . date('Y-m-d H:i:s'),
            'slug' => 'test-'.time(),
            'author_id' => 1,
            'status' => 'draft',
            'test_field' => 'Значение для тестового поля',
            'test_field2' => 'Второе тестовое поле',
            'description' => 'Тестовое описание записи ' . date('H:i:s')
        ];
        
        writeLog("Данные для создания: " . print_r($createData, true));
        
        $result = $contentModel->create($createData);
        
        if ($result['success']) {
            $testContentId = $result['data']['id'];
            $testContent = $result['data'];
            writeLog("Запись успешно создана, ID: {$testContentId}");
        } else {
            writeLog("Ошибка при создании записи: " . $result['message']);
            die("Не удалось создать тестовую запись");
        }
    }
    
    // Теперь обновляем запись
    writeLog("Тестируем обновление записи с ID: {$testContentId}");
    
    $updateData = [
        'title' => 'Обновленная запись ' . date('Y-m-d H:i:s'),
        'test_field' => 'Обновленное значение для тестового поля ' . date('H:i:s'),
        'description' => 'Обновленное описание ' . date('H:i:s'),
        'test_field2' => 'Обновленное второе тестовое поле',
        'new_field' => 'Совершенно новое поле ' . date('H:i:s')
    ];
    
    writeLog("Данные для обновления: " . print_r($updateData, true));
    
    // Сохраняем оригинальные данные для сравнения
    $originalData = $testContent;
    
    // Обновляем запись
    $updateResult = $contentModel->update($testContentId, $updateData);
    
    if ($updateResult['success']) {
        writeLog("Запись успешно обновлена");
        
        // Получаем обновленные данные
        $updatedContent = $contentModel->getById($testContentId);
        
        writeLog("Сравниваем данные:");
        writeLog("Оригинальный заголовок: " . $originalData['title']);
        writeLog("Новый заголовок: " . $updatedContent['title']);
        writeLog("Обновление заголовка: " . ($originalData['title'] !== $updatedContent['title'] ? "Успешно" : "Не изменился"));
        
        // Проверяем поля
        if (!empty($updatedContent['fields'])) {
            writeLog("Поля в обновленной записи:");
            foreach ($updatedContent['fields'] as $fieldName => $fieldData) {
                writeLog(" - {$fieldName}: " . (is_array($fieldData['value']) ? json_encode($fieldData['value']) : $fieldData['value']));
            }
        } else {
            writeLog("В обновленной записи нет полей!");
        }
        
        // Проверяем, какие поля были успешно обновлены
        foreach ($updateData as $key => $value) {
            if ($key === 'title') {
                $success = $updatedContent['title'] === $value;
                writeLog("Проверка поля '{$key}': " . ($success ? "Обновлено" : "Не обновлено!"));
            } else {
                $success = isset($updatedContent['fields'][$key]) && (
                    $updatedContent['fields'][$key]['value'] === $value || 
                    json_encode($updatedContent['fields'][$key]['value']) === json_encode($value)
                );
                writeLog("Проверка поля '{$key}': " . ($success ? "Обновлено" : "Не обновлено!"));
            }
        }
    } else {
        writeLog("Ошибка при обновлении записи: " . $updateResult['message']);
        if (isset($updateResult['error_fields'])) {
            writeLog("Поля с ошибками: " . implode(', ', $updateResult['error_fields']));
        }
    }
    
    // Проверка PDO ошибок
    $pdo = $db->getConnection();
    if ($pdo->errorCode() !== '00000') {
        writeLog("PDO ошибки: " . print_r($pdo->errorInfo(), true));
    }
    
    // Проверка таблицы content_field_values
    $fieldValues = $db->fetchAll(
        "SELECT cfv.*, ctf.name FROM content_field_values cfv 
        JOIN content_type_fields ctf ON cfv.field_id = ctf.id 
        WHERE cfv.content_id = ?",
        [$testContentId]
    );
    
    writeLog("Значения полей в базе данных:");
    foreach ($fieldValues as $fieldValue) {
        writeLog(" - {$fieldValue['name']} (ID:{$fieldValue['id']}): {$fieldValue['value']}");
    }
    
    // Выводим ссылку для повторного тестирования
    echo "<p>Тестовая запись с ID: {$testContentId}</p>";
    echo "<p><a href='?id={$testContentId}'>Повторно обновить эту запись</a></p>";
    echo "<p><a href='?'>Создать новую тестовую запись</a></p>";
    
} catch (Exception $e) {
    writeLog("Критическая ошибка: " . $e->getMessage());
    writeLog("Стек вызовов: " . $e->getTraceAsString());
}

writeLog("=== ЗАВЕРШЕНИЕ ТЕСТИРОВАНИЯ (" . date('Y-m-d H:i:s') . ") ==="); 
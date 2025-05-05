<?php
/**
 * Общие функции для работы с CORS-заголовками
 * Переписано для решения проблемы дублирования
 */

// Глобальные переменные для отслеживания состояния
$GLOBALS['cors_headers_set'] = false;
$GLOBALS['cors_debug'] = true; // Включаем режим отладки

/**
 * Установка заголовков CORS без дублирования
 */
function setCorsHeaders() {
    // Используем глобальные переменные для отслеживания состояния
    global $cors_headers_set, $cors_debug;
    
    // Если заголовки уже были установлены, не устанавливаем их снова
    if ($cors_headers_set) {
        if ($cors_debug) error_log("[CORS] Заголовки уже были установлены, пропускаем");
        return;
    }
    
    // Проверяем, можно ли установить заголовки
    if (headers_sent($file, $line)) {
        if ($cors_debug) error_log("[CORS] Заголовки уже отправлены в $file на строке $line");
        return;
    }
    
    // Проверяем, есть ли уже CORS-заголовки в ответе
    $existingCorsHeaders = [];
    foreach (headers_list() as $header) {
        if (strpos($header, 'Access-Control-') === 0) {
            $parts = explode(':', $header, 2);
            $headerName = trim($parts[0]);
            $existingCorsHeaders[$headerName] = true;
            if ($cors_debug) error_log("[CORS] Обнаружен существующий заголовок: $header");
        }
    }
    
    if (!empty($existingCorsHeaders)) {
        error_log("[CORS ПРЕДУПРЕЖДЕНИЕ] Обнаружены существующие CORS-заголовки перед вызовом setCorsHeaders:");
        error_log("[CORS ПРЕДУПРЕЖДЕНИЕ] Возможно, они устанавливаются в .htaccess или других файлах");
        error_log("[CORS ПРЕДУПРЕЖДЕНИЕ] Заголовки: " . implode(', ', array_keys($existingCorsHeaders)));
    }
    
    // Отмечаем, что заголовки установлены
    $cors_headers_set = true;
    
    // Удаляем все существующие CORS заголовки для избежания дублирования
    header_remove('Access-Control-Allow-Origin');
    header_remove('Access-Control-Allow-Methods');
    header_remove('Access-Control-Allow-Headers');
    header_remove('Access-Control-Allow-Credentials');
    header_remove('Access-Control-Max-Age');
    
    // Добавляем единые CORS заголовки
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 1 день
    
    // Дополнительная проверка на дублирование после установки заголовков
    $corsHeaders = [];
    foreach (headers_list() as $header) {
        if (strpos($header, 'Access-Control-') === 0) {
            $parts = explode(':', $header, 2);
            $name = trim($parts[0]);
            $corsHeaders[$name][] = trim($parts[1] ?? '');
        }
    }
    
    $hasDuplicates = false;
    foreach ($corsHeaders as $name => $values) {
        if (count($values) > 1) {
            $hasDuplicates = true;
            error_log("[CORS ОШИБКА] Обнаружено дублирование заголовка $name: " . implode(', ', $values));
        }
    }
    
    if (!$hasDuplicates && $cors_debug) {
        error_log("[CORS] Успешно установлены новые заголовки без дублирования");
    }
}

/**
 * Обработка предварительного запроса OPTIONS для CORS
 */
function handleOptionsRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        global $cors_debug;
        
        if ($cors_debug) error_log("[CORS] Обрабатываем предварительный запрос OPTIONS");
        
        // Устанавливаем CORS-заголовки
        setCorsHeaders();
        
        // Отправляем успешный ответ и завершаем выполнение
        http_response_code(200);
        exit;
    }
} 
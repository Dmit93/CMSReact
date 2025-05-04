<?php
/**
 * Инициализация модуля Core
 */

namespace Modules\Core;

// Подключаем класс модуля напрямую с полным путем
$moduleClassFile = __DIR__ . '/Modules.php';

if (!file_exists($moduleClassFile)) {
    die("ОШИБКА: Файл модуля Core не найден: {$moduleClassFile}");
}

require_once $moduleClassFile;

// Проверяем, существует ли класс Module
if (!class_exists('Modules\\Core\\Module')) {
    die("ОШИБКА: Класс Modules\\Core\\Module не найден после подключения файла");
}

// Создаем экземпляр модуля
$module = new Module();

return $module; 
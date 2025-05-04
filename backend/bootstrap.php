<?php
/**
 * Bootstrap для backend части системы
 * 
 * Этот файл загружает все необходимые компоненты ядра системы
 */

// Определение корневого пути
define('ROOT_PATH', dirname(__DIR__));
define('BACKEND_PATH', __DIR__);

// Автозагрузчик классов
spl_autoload_register(function($className) {
    // Преобразуем имя класса в путь к файлу
    $namespace = str_replace('\\', '/', $className);
    $filePath = BACKEND_PATH . '/' . strtolower($namespace) . '.php';
    
    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    
    // Проверяем Core классы
    $filePath = BACKEND_PATH . '/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    
    return false;
});

// Инициализируем базовые классы
require_once BACKEND_PATH . '/core/init.php'; 
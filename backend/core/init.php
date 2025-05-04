<?php
/**
 * Файл инициализации ядра CMS
 */

// Автозагрузка классов
require_once __DIR__ . '/Autoloader.php';

// Инициализация автозагрузчика
$autoloader = \Core\Autoloader::init();

// Инициализация ядра
$core = \Core\Core::getInstance();
$core->init();

// Возвращаем экземпляр ядра для использования в других файлах
return $core; 
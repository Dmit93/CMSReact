<?php
/**
 * Инициализация модуля DefaultFields
 */

// Подключаем основной класс модуля
require_once __DIR__ . '/DefaultFieldsModule.php';

// Создаем экземпляр модуля
$module = new \Modules\DefaultFields\DefaultFieldsModule();

// Инициализируем модуль
$module->init();

// Возвращаем экземпляр модуля
return $module; 
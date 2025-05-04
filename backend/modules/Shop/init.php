<?php
/**
 * Инициализация модуля Shop
 */

namespace Modules\Shop;

// Подключаем класс модуля
require_once __DIR__ . '/ShopModule.php';

// Создаем экземпляр модуля
$module = new Module();

return $module; 
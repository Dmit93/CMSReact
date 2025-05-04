<?php
/**
 * Скрипт для установки и активации базового модуля Core
 */

require_once __DIR__ . '/bootstrap.php';

use Core\ModuleManager;
use API\Database;

// Создаем экземпляр менеджера модулей
$moduleManager = ModuleManager::getInstance();

// Сканируем доступные модули
$moduleManager->scanModules();

// Проверяем, установлен ли модуль Core
$query = "SELECT id FROM modules WHERE slug = 'core'";
$existingModule = Database::getInstance()->fetch($query);

if ($existingModule) {
    echo "Модуль Core уже установлен.\n";
    
    // Проверяем, активирован ли модуль
    $query = "SELECT status FROM modules WHERE slug = 'core'";
    $status = Database::getInstance()->fetch($query);
    
    if ($status && $status['status'] === 'active') {
        echo "Модуль Core уже активирован.\n";
    } else {
        // Активируем модуль
        $result = $moduleManager->activateModule('core');
        
        if ($result['success']) {
            echo "Модуль Core успешно активирован.\n";
        } else {
            echo "Ошибка при активации модуля Core: {$result['message']}\n";
        }
    }
} else {
    // Устанавливаем модуль
    $result = $moduleManager->installModule('core');
    
    if ($result['success']) {
        echo "Модуль Core успешно установлен.\n";
        
        // Активируем модуль
        $activateResult = $moduleManager->activateModule('core');
        
        if ($activateResult['success']) {
            echo "Модуль Core успешно активирован.\n";
        } else {
            echo "Ошибка при активации модуля Core: {$activateResult['message']}\n";
        }
    } else {
        echo "Ошибка при установке модуля Core: {$result['message']}\n";
    }
}

echo "Готово.\n"; 
<?php
/**
 * Скрипт для установки Core модуля через API
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\ModuleManager;
use API\Database;

// Устанавливаем заголовки для JSON
header('Content-Type: application/json');

try {
    // Создаем экземпляр менеджера модулей
    $moduleManager = ModuleManager::getInstance();

    // Сканируем доступные модули
    $moduleManager->scanModules();

    // Проверяем, установлен ли модуль Core
    $query = "SELECT id FROM modules WHERE slug = 'core'";
    $existingModule = Database::getInstance()->fetch($query);

    $result = ['success' => false, 'message' => ''];

    if ($existingModule) {
        $result['message'] = "Модуль Core уже установлен";
        
        // Проверяем, активирован ли модуль
        $query = "SELECT status FROM modules WHERE slug = 'core'";
        $status = Database::getInstance()->fetch($query);
        
        if ($status && $status['status'] === 'active') {
            $result['message'] .= ". Модуль Core уже активирован.";
            $result['success'] = true;
        } else {
            // Активируем модуль
            $activateResult = $moduleManager->activateModule('core');
            
            if ($activateResult['success']) {
                $result['message'] .= ". Модуль Core успешно активирован.";
                $result['success'] = true;
            } else {
                $result['message'] .= ". Ошибка при активации модуля Core: {$activateResult['message']}.";
            }
        }
    } else {
        // Устанавливаем модуль
        $installResult = $moduleManager->installModule('core');
        
        if ($installResult['success']) {
            $result['message'] = "Модуль Core успешно установлен.";
            
            // Активируем модуль
            $activateResult = $moduleManager->activateModule('core');
            
            if ($activateResult['success']) {
                $result['message'] .= " Модуль Core успешно активирован.";
                $result['success'] = true;
            } else {
                $result['message'] .= " Ошибка при активации модуля Core: {$activateResult['message']}.";
            }
        } else {
            $result['message'] = "Ошибка при установке модуля Core: {$installResult['message']}";
        }
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Произошла ошибка: " . $e->getMessage()
    ]);
} 
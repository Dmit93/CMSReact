<?php
/**
 * API для работы с модулями
 */

// Получение списка всех модулей
$app->get('/modules', function($request, $response) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user || $user['role'] !== 'admin') {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Получаем список установленных модулей
    $installedModules = $moduleManager->getInstalledModules();
    
    // Получаем список доступных для установки модулей
    $availableModules = $moduleManager->getAvailableModules();
    
    return $response->withJson([
        'success' => true,
        'installed' => $installedModules,
        'available' => $availableModules
    ]);
});

// Получение информации о конкретном модуле
$app->get('/modules/{id}', function($request, $response, $args) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user || $user['role'] !== 'admin') {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    $moduleId = $args['id'];
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Получаем информацию о модуле
    $module = $moduleManager->getModuleInfo($moduleId);
    
    if (!$module) {
        return $response->withJson([
            'success' => false,
            'message' => 'Модуль не найден'
        ], 404);
    }
    
    return $response->withJson([
        'success' => true,
        'module' => $module
    ]);
});

// Активация модуля
$app->post('/modules/{id}/activate', function($request, $response, $args) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user || $user['role'] !== 'admin') {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    $moduleId = $args['id'];
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Активируем модуль
    $result = $moduleManager->activateModule($moduleId);
    
    if (!$result) {
        return $response->withJson([
            'success' => false,
            'message' => 'Не удалось активировать модуль'
        ], 500);
    }
    
    return $response->withJson([
        'success' => true,
        'message' => 'Модуль успешно активирован'
    ]);
});

// Деактивация модуля
$app->post('/modules/{id}/deactivate', function($request, $response, $args) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user || $user['role'] !== 'admin') {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    $moduleId = $args['id'];
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Деактивируем модуль
    $result = $moduleManager->deactivateModule($moduleId);
    
    if (!$result) {
        return $response->withJson([
            'success' => false,
            'message' => 'Не удалось деактивировать модуль'
        ], 500);
    }
    
    return $response->withJson([
        'success' => true,
        'message' => 'Модуль успешно деактивирован'
    ]);
});

// Установка модуля
$app->post('/modules/{id}/install', function($request, $response, $args) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user || $user['role'] !== 'admin') {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    $moduleId = $args['id'];
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Устанавливаем модуль
    $result = $moduleManager->installModule($moduleId);
    
    if (!$result) {
        return $response->withJson([
            'success' => false,
            'message' => 'Не удалось установить модуль'
        ], 500);
    }
    
    return $response->withJson([
        'success' => true,
        'message' => 'Модуль успешно установлен'
    ]);
});

// Удаление модуля
$app->post('/modules/{id}/uninstall', function($request, $response, $args) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user || $user['role'] !== 'admin') {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    $moduleId = $args['id'];
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Удаляем модуль
    $result = $moduleManager->uninstallModule($moduleId);
    
    if (!$result) {
        return $response->withJson([
            'success' => false,
            'message' => 'Не удалось удалить модуль'
        ], 500);
    }
    
    return $response->withJson([
        'success' => true,
        'message' => 'Модуль успешно удален'
    ]);
});

// Получение статуса модулей (активные/неактивные)
$app->get('/modules/status', function($request, $response) {
    // Проверка авторизации
    $user = $this->authenticator->getUser();
    if (!$user) {
        return $response->withJson([
            'success' => false,
            'message' => 'Доступ запрещен'
        ], 403);
    }
    
    // Получаем менеджер модулей
    $moduleManager = \Core\ModuleManager::getInstance();
    
    // Получаем список всех модулей с их статусами
    $modules = $moduleManager->getAllModulesWithStatus();
    
    return $response->withJson([
        'success' => true,
        'modules' => $modules
    ]);
}); 
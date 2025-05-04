<?php
/**
 * Конфигурация ядра CMS
 */
return [
    // Режим отладки
    'debug' => true,
    
    // Базовый путь для URL
    'base_path' => '/cms/',
    
    // Пути для поиска модулей
    'modules_paths' => [
        dirname(__DIR__) . '/modules'
    ],
    
    // Список стандартных событий системы
    'events' => [
        // События ядра
        'core.before_init',
        'core.after_init',
        
        // События API
        'api.register_route',
        'api.before_request',
        'api.after_request',
        'api.error',
        
        // События авторизации
        'auth.before_login',
        'auth.after_login',
        'auth.login_failed',
        'auth.logout',
        
        // События контента
        'content_type.before_save',
        'content_type.after_save',
        'content_type.before_delete',
        'content_type.after_delete',
        
        'content.before_save',
        'content.after_save',
        'content.before_delete',
        'content.after_delete',
        
        // События пользователей
        'user.before_save',
        'user.after_save',
        'user.before_delete',
        'user.after_delete'
    ],
    
    // Настройки логирования
    'logging' => [
        'enabled' => true,
        'path' => dirname(__DIR__) . '/logs',
        'level' => 'debug' // debug, info, warning, error
    ]
]; 
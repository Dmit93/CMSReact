<?php
/**
 * Основные настройки приложения
 */
$config =  [
    'name' => 'Universal CMS',
    'version' => '1.0.0',
    'base_url' => 'http://localhost/cms/backend/api/',
    'upload_dir' => __DIR__ . '/../uploads',
    'jwt_secret' => 'your_jwt_secret_key',
    'jwt_expiration' => 604800, // 7 дней (было 3600 - 1 час)
    'debug' => true,
    'timezone' => 'Europe/Moscow',
    'cors' => [
        'allowed_origins' => [
            'http://localhost:5173', 
            'http://localhost:5174', 
            'http://localhost:5175', 
            'http://localhost:5176', 
            'http://localhost:5177'
        ], // Разрешаем локальный frontend на разных портах
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    ]
]; 

return $config; 
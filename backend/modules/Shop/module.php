<?php
/**
 * Описание модуля Shop
 */
return [
    'id' => 'shop',
    'name' => 'Интернет-магазин',
    'description' => 'Модуль для создания интернет-магазина',
    'version' => '1.0.0',
    'author' => 'CMS Team',
    'author_url' => 'https://universal-cms.com',
    'main_class' => 'Modules\\Shop\\ShopModule',
    'requires' => ['core'],
    'config' => [
        'products_per_page' => 10,
        'enable_reviews' => true,
        'currency' => 'RUB',
        'currency_symbol' => '₽'
    ]
]; 
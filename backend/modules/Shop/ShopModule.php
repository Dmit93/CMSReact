<?php
namespace Modules\Shop;

use Core\BaseModule;
use Core\Logger;

/**
 * Основной класс модуля интернет-магазина
 */
class Module extends BaseModule {
    /**
     * Инициализация модуля
     */
    public function init() {
        // Загружаем информацию о модуле
        $moduleInfo = include __DIR__ . '/module.php';
        $this->setInfo($moduleInfo);
        
        // Регистрируем обработчики событий
        $this->registerEventHandlers();
        
        // Подключаем модели и контроллеры
        $this->includeModels();
        
        // Логируем загрузку
        Logger::getInstance()->info('Shop module initialized', [
            'version' => $this->moduleInfo['version']
        ]);
    }
    
    /**
     * Регистрация обработчиков событий
     */
    private function registerEventHandlers() {
        // Обработка контента в темах
        $this->registerEventHandler('theme.render.content', [$this, 'onRenderContent']);
        
        // Хуки для интеграции с админ-панелью
        $this->registerEventHandler('admin.menu.build', [$this, 'onAdminMenuBuild']);
        
        // Хуки для API
        $this->registerEventHandler('api.routes.register', [$this, 'onApiRoutesRegister']);
    }
    
    /**
     * Подключение моделей модуля
     */
    private function includeModels() {
        require_once __DIR__ . '/models/ProductModel.php';
        // require_once __DIR__ . '/models/CategoryModel.php';
        // require_once __DIR__ . '/models/OrderModel.php';
        // require_once __DIR__ . '/models/CartModel.php';
    }
    
    /**
     * Обработчик события для вывода контента в теме
     */
    public function onRenderContent($params) {
        // Добавление функциональности в рендеринг темы
        if (isset($params['renderer'])) {
            $renderer = $params['renderer'];
            
            // Добавляем глобальную переменную для проверки наличия модуля магазина
            $renderer->addData('shop_enabled', true);
            
            // Добавляем данные с настройками магазина
            $renderer->addData('shop_config', $this->moduleInfo['config']);
        }
        
        return $params;
    }
    
    /**
     * Обработчик события для добавления пунктов в меню админ-панели
     */
    public function onAdminMenuBuild($menu) {
        $menu['items'][] = [
            'id' => 'shop',
            'title' => 'Магазин',
            'icon' => 'shopping-cart',
            'order' => 30,
            'submenu' => [
                [
                    'id' => 'products',
                    'title' => 'Товары',
                    'link' => '/admin/shop/products'
                ],
                [
                    'id' => 'categories',
                    'title' => 'Категории',
                    'link' => '/admin/shop/categories'
                ],
                [
                    'id' => 'orders',
                    'title' => 'Заказы',
                    'link' => '/admin/shop/orders'
                ],
                [
                    'id' => 'settings',
                    'title' => 'Настройки магазина',
                    'link' => '/admin/shop/settings'
                ]
            ]
        ];
        
        return $menu;
    }
    
    /**
     * Обработчик события для регистрации маршрутов API
     */
    public function onApiRoutesRegister($routes) {
        $routes[] = [
            'method' => 'GET',
            'path' => '/api/shop/products',
            'handler' => 'Modules\Shop\Controllers\ApiController@getProducts'
        ];
        
        $routes[] = [
            'method' => 'GET',
            'path' => '/api/shop/products/{id}',
            'handler' => 'Modules\Shop\Controllers\ApiController@getProduct'
        ];
        
        $routes[] = [
            'method' => 'GET',
            'path' => '/api/shop/categories',
            'handler' => 'Modules\Shop\Controllers\ApiController@getCategories'
        ];
        
        $routes[] = [
            'method' => 'POST',
            'path' => '/api/shop/cart/add',
            'handler' => 'Modules\Shop\Controllers\ApiController@addToCart'
        ];
        
        return $routes;
    }
} 
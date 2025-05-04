<?php
namespace Modules\Core;

use Core\BaseModule;
use Core\Logger;

/**
 * Основной класс базового модуля Core
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
        
        // Логируем загрузку
        Logger::getInstance()->info('Core module initialized', [
            'version' => $this->moduleInfo['version']
        ]);
    }
    
    /**
     * Регистрация обработчиков событий
     */
    private function registerEventHandlers() {
        // Регистрируем базовые хуки
        $this->registerEventHandler('core.init', [$this, 'onCoreInit']);
        $this->registerEventHandler('core.shutdown', [$this, 'onCoreShutdown']);
        
        // Хуки для API
        $this->registerEventHandler('api.before_register_routes', [$this, 'onApiBeforeRegisterRoutes']);
    }
    
    /**
     * Обработчик события инициализации ядра
     */
    public function onCoreInit($params) {
        // Логика при инициализации ядра
        return $params;
    }
    
    /**
     * Обработчик события завершения работы ядра
     */
    public function onCoreShutdown($params) {
        // Логика при завершении работы ядра
        return $params;
    }
    
    /**
     * Обработчик события перед регистрацией маршрутов API
     */
    public function onApiBeforeRegisterRoutes($params) {
        // Логика перед регистрацией маршрутов API
        return $params;
    }
    
    /**
     * Получение общих утилит
     * 
     * @return array Массив с утилитами
     */
    public function getUtils() {
        return [
            'formatDate' => function($date, $format = 'Y-m-d H:i:s') {
                return date($format, strtotime($date));
            },
            'sanitize' => function($input) {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            },
            'slugify' => function($text) {
                $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
                $text = preg_replace('/[^a-z0-9-]/', '-', $text);
                $text = preg_replace('/-+/', '-', $text);
                return trim($text, '-');
            }
        ];
    }
}
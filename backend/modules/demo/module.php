<?php
namespace Modules\Demo;

use Core\BaseModule;
use Core\Logger;

/**
 * Демонстрационный модуль
 */
// class Module extends BaseModule {
//     /**
//      * Инициализация модуля
//      */
//     public function init() {
//         // Загружаем информацию о модуле
//         $this->setInfo(require __DIR__ . '/module.php');
        
//         // Регистрируем обработчики событий
//         $this->registerEventHandlers();
        
//         // Регистрируем маршруты API
//         $this->registerApiRoutes();
        
//         // Логируем инициализацию модуля
//         Logger::getInstance()->info('Модуль демонстрации инициализирован', [
//             'module' => $this->getId()
//         ]);
//     }
    
//     /**
//      * Регистрация обработчиков событий
//      */
//     private function registerEventHandlers() {
//         // Регистрируем обработчик события инициализации ядра
//         $this->registerEventHandler('core.after_init', [$this, 'onCoreInit']);
        
//         // Регистрируем обработчики событий для контента
//         $this->registerEventHandler('content.after_save', [$this, 'onContentSave']);
//         $this->registerEventHandler('content.after_delete', [$this, 'onContentDelete']);
//     }
    
//     /**
//      * Регистрация маршрутов API
//      */
//     private function registerApiRoutes() {
//         // Регистрируем маршрут для получения информации о модуле
//         $this->registerApiRoute('GET', '/demo/info', [$this, 'getModuleInfo']);
        
//         // Регистрируем маршрут для тестирования событий
//         $this->registerApiRoute('POST', '/demo/test-event', [$this, 'testEvent']);
//     }
    
//     /**
//      * Обработчик события инициализации ядра
//      */
//     public function onCoreInit($core) {
//         Logger::getInstance()->debug('Демонстрационный модуль: обработка события core.after_init');
//     }
    
//     /**
//      * Обработчик события сохранения контента
//      */
//     public function onContentSave($content) {
//         Logger::getInstance()->debug('Демонстрационный модуль: контент сохранен', [
//             'content' => $content
//         ]);
//     }
    
//     /**
//      * Обработчик события удаления контента
//      */
//     public function onContentDelete($contentId) {
//         Logger::getInstance()->debug('Демонстрационный модуль: контент удален', [
//             'contentId' => $contentId
//         ]);
//     }
    
//     /**
//      * API метод для получения информации о модуле
//      */
//     public function getModuleInfo() {
//         return [
//             'success' => true,
//             'data' => $this->getInfo()
//         ];
//     }
    
//     /**
//      * API метод для тестирования событий
//      */
//     public function testEvent($request) {
//         $eventName = isset($request['event']) ? $request['event'] : 'test.event';
//         $params = isset($request['params']) ? $request['params'] : [];
        
//         $result = $this->triggerEvent($eventName, $params);
        
//         return [
//             'success' => true,
//             'data' => [
//                 'event' => $eventName,
//                 'params' => $params,
//                 'result' => $result
//             ]
//         ];
//     }
// } 
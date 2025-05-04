<?php
namespace Core;

/**
 * Базовый класс для всех модулей системы
 */
abstract class BaseModule {
    protected $core;
    protected $moduleInfo;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->core = Core::getInstance();
    }
    
    /**
     * Инициализация модуля
     * Должна быть переопределена в дочерних классах
     */
    abstract public function init();
    
    /**
     * Получение информации о модуле
     * 
     * @return array Информация о модуле
     */
    public function getInfo() {
        return $this->moduleInfo;
    }
    
    /**
     * Установка информации о модуле
     * 
     * @param array $info Информация о модуле
     * @return $this Текущий экземпляр для цепочки вызовов
     */
    public function setInfo($info) {
        $this->moduleInfo = $info;
        return $this;
    }
    
    /**
     * Получение ID модуля
     * 
     * @return string ID модуля
     */
    public function getId() {
        return isset($this->moduleInfo['id']) ? $this->moduleInfo['id'] : '';
    }
    
    /**
     * Получение имени модуля
     * 
     * @return string Имя модуля
     */
    public function getName() {
        return isset($this->moduleInfo['name']) ? $this->moduleInfo['name'] : '';
    }
    
    /**
     * Регистрация обработчика события
     * 
     * @param string $eventName Имя события
     * @param callable $callback Функция обработчик
     * @param int $priority Приоритет обработчика
     * @return bool Успешность регистрации
     */
    protected function registerEventHandler($eventName, $callback, $priority = 10) {
        return $this->core->getEventManager()->on($eventName, $callback, $priority);
    }
    
    /**
     * Вызов события
     * 
     * @param string $eventName Имя события
     * @param array $params Параметры события
     * @return mixed Результат обработки события
     */
    protected function triggerEvent($eventName, $params = []) {
        return $this->core->trigger($eventName, $params);
    }
    
    /**
     * Получение пути к директории модуля
     * 
     * @return string Путь к директории модуля
     */
    public function getPath() {
        return isset($this->moduleInfo['path']) ? $this->moduleInfo['path'] : '';
    }
    
    /**
     * Получение пути к файлу в директории модуля
     * 
     * @param string $file Имя файла
     * @return string Полный путь к файлу
     */
    public function getFilePath($file) {
        return $this->getPath() . '/' . $file;
    }
    
    /**
     * Получение URL к файлу в директории модуля
     * 
     * @param string $file Имя файла
     * @return string URL к файлу
     */
    public function getFileUrl($file) {
        // TODO: Реализовать получение URL с учетом базового пути
        $basePath = $this->core->getConfig('base_path', '');
        $moduleRelativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $this->getPath());
        
        return $basePath . $moduleRelativePath . '/' . $file;
    }
    
    /**
     * Регистрация маршрута API для модуля
     * 
     * @param string $method HTTP метод (GET, POST, PUT, DELETE)
     * @param string $endpoint Конечная точка API
     * @param callable $handler Обработчик маршрута
     * @return bool Успешность регистрации
     */
    protected function registerApiRoute($method, $endpoint, $handler) {
        // TODO: Интеграция с системой маршрутизации API
        // Временное решение - использование события для регистрации маршрута
        return $this->triggerEvent('api.register_route', [
            'method' => $method,
            'endpoint' => $endpoint,
            'handler' => $handler
        ]);
    }
} 
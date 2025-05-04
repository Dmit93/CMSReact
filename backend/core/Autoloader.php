<?php
namespace Core;

/**
 * Автозагрузчик классов ядра и модулей
 */
class Autoloader {
    private static $instance = null;
    private $namespaces = [];
    
    /**
     * Получение singleton экземпляра класса
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Конструктор
     */
    private function __construct() {
        // Регистрация автозагрузчика
        spl_autoload_register([$this, 'loadClass']);
        
        // Регистрация основных пространств имен
        $this->registerNamespace('Core', dirname(__FILE__));
        $this->registerNamespace('API', dirname(__DIR__) . '/api');
        $this->registerNamespace('Models', dirname(__DIR__) . '/models');
        $this->registerNamespace('Controllers', dirname(__DIR__) . '/controllers');
        
        // Регистрация пространства имен для модулей
        $this->registerNamespace('Modules', dirname(__DIR__) . '/modules');
        
        // Добавляем отладочное логирование
        error_log("Autoloader initialized: " . json_encode($this->namespaces));
    }
    
    /**
     * Регистрация пространства имен
     * 
     * @param string $namespace Пространство имен
     * @param string $path Путь к директории с классами
     * @return $this Текущий экземпляр для цепочки вызовов
     */
    public function registerNamespace($namespace, $path) {
        $this->namespaces[$namespace] = $path;
        return $this;
    }
    
    /**
     * Загрузка класса
     * 
     * @param string $class Полное имя класса с пространством имен
     * @return bool Успешность загрузки
     */
    public function loadClass($class) {
        // Добавляем отладочное логирование
        error_log("Autoloader: Trying to load class: " . $class);
        
        // Получаем пространство имен и имя класса
        $parts = explode('\\', $class);
        
        if (count($parts) < 2) {
            error_log("Autoloader: Invalid class format: " . $class);
            return false;
        }
        
        $namespace = $parts[0];
        $className = end($parts);
        
        // Если пространство имен не зарегистрировано
        if (!isset($this->namespaces[$namespace])) {
            error_log("Autoloader: Namespace not registered: " . $namespace);
            return false;
        }
        
        // Формируем путь к файлу
        $path = $this->namespaces[$namespace];
        
        // Обрабатываем подпространства имен
        if (count($parts) > 2) {
            $subNamespace = array_slice($parts, 1, -1);
            $path .= '/' . implode('/', $subNamespace);
        }
        
        $file = $path . '/' . $className . '.php';
        
        // Выводим отладочную информацию о пути
        error_log("Autoloader: Looking for file: " . $file);
        
        // Загружаем файл, если он существует
        if (file_exists($file)) {
            require_once $file;
            error_log("Autoloader: Successfully loaded: " . $file);
            return true;
        }
        
        error_log("Autoloader: File not found: " . $file);
        return false;
    }
    
    /**
     * Инициализация автозагрузчика
     * 
     * @return Autoloader Экземпляр автозагрузчика
     */
    public static function init() {
        return self::getInstance();
    }
    
    /**
     * Получение списка зарегистрированных пространств имен
     * 
     * @return array Массив пространств имен и путей
     */
    public function getNamespaces() {
        return $this->namespaces;
    }
} 
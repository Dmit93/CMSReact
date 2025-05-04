<?php
namespace Core;

/**
 * Ядро CMS - основной класс системы
 */
class Core {
    private static $instance = null;
    private $eventManager;
    private $moduleManager;
    private $config = [];
    private $initialized = false;
    
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
        // Инициализация компонентов ядра
        $this->eventManager = EventManager::getInstance();
        $this->moduleManager = ModuleManager::getInstance();
    }
    
    /**
     * Инициализация ядра
     * 
     * @return bool Успешность инициализации
     */
    public function init() {
        if ($this->initialized) {
            return true;
        }
        
        try {
            // Загрузка конфигурации
            $this->loadConfig();
            
            // Запуск события перед инициализацией
            $this->eventManager->trigger('core.before_init', [&$this]);
            
            // Инициализация модулей
            $this->moduleManager->loadModules();
            
            // Запуск события после инициализации
            $this->eventManager->trigger('core.after_init', [&$this]);
            
            $this->initialized = true;
            
            return true;
        } catch (\Exception $e) {
            // Логирование ошибки
            error_log("Core initialization error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Загрузка конфигурации системы
     */
    private function loadConfig() {
        $configFile = dirname(__DIR__) . '/config/core.php';
        
        if (file_exists($configFile)) {
            $this->config = include $configFile;
        } else {
            // Конфигурация по умолчанию
            $this->config = [
                'debug' => false,
                'modules_path' => dirname(__DIR__) . '/modules',
                'events' => [
                    // Список стандартных событий системы
                    'core.before_init',
                    'core.after_init',
                    'content.before_save',
                    'content.after_save',
                    'content.before_delete',
                    'content.after_delete',
                    'user.before_login',
                    'user.after_login',
                    'user.logout'
                ]
            ];
        }
        
        // Регистрация дополнительных путей для модулей, если они указаны в конфиге
        if (isset($this->config['modules_paths']) && is_array($this->config['modules_paths'])) {
            foreach ($this->config['modules_paths'] as $path) {
                $this->moduleManager->addModulePath($path);
            }
        }
    }
    
    /**
     * Получение менеджера событий
     * 
     * @return EventManager Экземпляр менеджера событий
     */
    public function getEventManager() {
        return $this->eventManager;
    }
    
    /**
     * Получение менеджера модулей
     * 
     * @return ModuleManager Экземпляр менеджера модулей
     */
    public function getModuleManager() {
        return $this->moduleManager;
    }
    
    /**
     * Получение значения из конфигурации
     * 
     * @param string $key Ключ конфигурации
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение из конфигурации или значение по умолчанию
     */
    public function getConfig($key = null, $default = null) {
        if ($key === null) {
            return $this->config;
        }
        
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        
        return $default;
    }
    
    /**
     * Установка значения в конфигурацию
     * 
     * @param string $key Ключ конфигурации
     * @param mixed $value Значение
     * @return $this Текущий экземпляр для цепочки вызовов
     */
    public function setConfig($key, $value) {
        $this->config[$key] = $value;
        return $this;
    }
    
    /**
     * Проверка, инициализировано ли ядро
     * 
     * @return bool Статус инициализации
     */
    public function isInitialized() {
        return $this->initialized;
    }
    
    /**
     * Вызов обработчика события с автоматическим добавлением контекста ядра
     * 
     * @param string $eventName Имя события
     * @param array $params Массив параметров
     * @return mixed Результат обработки события
     */
    public function trigger($eventName, $params = []) {
        // Проверяем, является ли массив параметров ассоциативным
        $isAssoc = !empty($params) && array_keys($params) !== range(0, count($params) - 1);
        
        if ($isAssoc) {
            // Для ассоциативного массива добавляем ядро как 'core'
            $params['core'] = $this;
        } else {
            // Для обычного массива добавляем ядро как первый элемент, если его там нет
            if (empty($params) || $params[0] !== $this) {
                array_unshift($params, $this);
            }
        }
        
        return $this->eventManager->trigger($eventName, $params);
    }
} 
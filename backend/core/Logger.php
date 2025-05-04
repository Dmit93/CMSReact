<?php
namespace Core;

/**
 * Логгер для системы
 */
class Logger {
    private static $instance = null;
    private $core;
    private $logPath;
    private $isEnabled;
    private $logLevel;
    
    // Уровни логирования
    const LEVEL_DEBUG = 1;
    const LEVEL_INFO = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR = 4;
    
    // Соответствие названий уровней и констант
    private $levelMap = [
        'debug' => self::LEVEL_DEBUG,
        'info' => self::LEVEL_INFO,
        'warning' => self::LEVEL_WARNING,
        'error' => self::LEVEL_ERROR
    ];
    
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
        $this->core = Core::getInstance();
        
        // Получаем настройки логирования из конфигурации
        $loggingConfig = $this->core->getConfig('logging', []);
        
        $this->isEnabled = isset($loggingConfig['enabled']) ? $loggingConfig['enabled'] : false;
        $this->logPath = isset($loggingConfig['path']) ? $loggingConfig['path'] : dirname(__DIR__) . '/logs';
        
        // Устанавливаем уровень логирования
        $levelName = isset($loggingConfig['level']) ? $loggingConfig['level'] : 'debug';
        $this->logLevel = isset($this->levelMap[$levelName]) ? $this->levelMap[$levelName] : self::LEVEL_DEBUG;
        
        // Создаем директорию для логов, если она не существует
        if ($this->isEnabled && !is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Логирование сообщения с уровнем DEBUG
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     * @return bool Успешность записи в лог
     */
    public function debug($message, $context = []) {
        return $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Логирование сообщения с уровнем INFO
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     * @return bool Успешность записи в лог
     */
    public function info($message, $context = []) {
        return $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Логирование сообщения с уровнем WARNING
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     * @return bool Успешность записи в лог
     */
    public function warning($message, $context = []) {
        return $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Логирование сообщения с уровнем ERROR
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     * @return bool Успешность записи в лог
     */
    public function error($message, $context = []) {
        return $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Запись сообщения в лог
     * 
     * @param int $level Уровень сообщения
     * @param string $message Сообщение для логирования
     * @param array $context Контекст сообщения
     * @return bool Успешность записи в лог
     */
    private function log($level, $message, $context = []) {
        // Если логирование отключено или уровень сообщения ниже установленного
        if (!$this->isEnabled || $level < $this->logLevel) {
            return false;
        }
        
        // Получаем название уровня
        $levelName = $this->getLevelName($level);
        
        // Форматируем дату
        $date = date('Y-m-d H:i:s');
        
        // Форматируем сообщение
        $formattedMessage = $this->formatMessage($message, $context);
        
        // Формируем строку для записи в лог
        $logMessage = "[$date] [$levelName] $formattedMessage" . PHP_EOL;
        
        // Формируем имя файла для логирования
        $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';
        
        // Записываем сообщение в файл
        return file_put_contents($logFile, $logMessage, FILE_APPEND) !== false;
    }
    
    /**
     * Получение названия уровня логирования
     * 
     * @param int $level Уровень логирования
     * @return string Название уровня
     */
    private function getLevelName($level) {
        $levels = array_flip($this->levelMap);
        return isset($levels[$level]) ? strtoupper($levels[$level]) : 'UNKNOWN';
    }
    
    /**
     * Форматирование сообщения с подстановкой данных из контекста
     * 
     * @param string $message Сообщение для форматирования
     * @param array $context Контекст сообщения
     * @return string Отформатированное сообщение
     */
    private function formatMessage($message, $context = []) {
        // Если контекст пустой, возвращаем исходное сообщение
        if (empty($context)) {
            return $message;
        }
        
        // Заменяем плейсхолдеры в сообщении на значения из контекста
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val)) {
                $replace['{' . $key . '}'] = $val;
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                $replace['{' . $key . '}'] = $val;
            } elseif (is_array($val) || is_object($val)) {
                $replace['{' . $key . '}'] = json_encode($val);
            } else {
                $replace['{' . $key . '}'] = '[' . gettype($val) . ']';
            }
        }
        
        return strtr($message, $replace);
    }
} 
<?php
namespace Core;

/**
 * Менеджер событий - основной класс для регистрации и вызова событий в системе
 */
class EventManager {
    private static $events = [];
    private static $instance = null;
    
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
     * Регистрация обработчика события
     * 
     * @param string $eventName Имя события
     * @param callable $callback Функция обработчик
     * @param int $priority Приоритет (меньшее значение = более высокий приоритет)
     * @return bool Успешность регистрации
     */
    public function on($eventName, $callback, $priority = 10) {
        if (!isset(self::$events[$eventName])) {
            self::$events[$eventName] = [];
        }
        
        self::$events[$eventName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        // Сортировка по приоритету
        usort(self::$events[$eventName], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return true;
    }
    
    /**
     * Запуск события с передачей параметров
     * 
     * @param string $eventName Имя события
     * @param array $params Массив параметров для передачи в обработчики
     * @return mixed Результат выполнения последнего обработчика
     */
    public function trigger($eventName, $params = []) {
        $result = null;
        
        // Отладочный вывод
        error_log("EventManager::trigger - Событие: $eventName, параметры: " . json_encode($params));
        
        if (isset(self::$events[$eventName])) {
            error_log("EventManager::trigger - Найдено " . count(self::$events[$eventName]) . " обработчиков для события $eventName");
            
            foreach (self::$events[$eventName] as $event) {
                $callback = $event['callback'];
                
                if (is_array($callback)) {
                    if (is_object($callback[0])) {
                        $callbackName = get_class($callback[0]) . "->" . $callback[1];
                    } else {
                        $callbackName = $callback[0] . "::" . $callback[1];
                    }
                } elseif (is_string($callback)) {
                    $callbackName = $callback;
                } else {
                    $callbackName = 'Анонимная функция';
                }
                
                error_log("EventManager::trigger - Вызов обработчика: $callbackName");

                // Проверяем, является ли массив параметров ассоциативным
                $isAssoc = !empty($params) && array_keys($params) !== range(0, count($params) - 1);

                // Получаем информацию о функции обработчика
                $reflection = null;
                $callbackParams = [];
                
                if (is_array($callback) && count($callback) === 2) {
                    // Метод класса
                    if (is_object($callback[0])) {
                        $reflection = new \ReflectionMethod(get_class($callback[0]), $callback[1]);
                    } else {
                        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
                    }
                } elseif (is_callable($callback)) {
                    // Обычная функция
                    $reflection = new \ReflectionFunction($callback);
                }
                
                if ($reflection) {
                    $reflectionParams = $reflection->getParameters();
                    
                    if ($isAssoc) {
                        // Для ассоциативного массива сопоставляем параметры по имени
                        foreach ($reflectionParams as $param) {
                            $paramName = $param->getName();
                            if (array_key_exists($paramName, $params)) {
                                $callbackParams[] = $params[$paramName];
                            } elseif ($param->isDefaultValueAvailable()) {
                                $callbackParams[] = $param->getDefaultValue();
                            } else {
                                $callbackParams[] = null;
                            }
                        }
                    } else {
                        // Для обычного массива просто передаем параметры по порядку
                        $callbackParams = $params;
                    }
                } else {
                    // Если не удалось получить отражение, используем параметры как есть
                    $callbackParams = $isAssoc ? array_values($params) : $params;
                }
                
                $callbackResult = call_user_func_array($callback, $callbackParams);
                
                // Если обработчик возвращает false, прерываем цепочку
                if ($callbackResult === false) {
                    return false;
                }
                
                // Возвращаем результат последнего обработчика
                if ($callbackResult !== null) {
                    $result = $callbackResult;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Проверка наличия обработчиков для события
     * 
     * @param string $eventName Имя события
     * @return bool Есть ли обработчики
     */
    public function hasHandlers($eventName) {
        return isset(self::$events[$eventName]) && !empty(self::$events[$eventName]);
    }
    
    /**
     * Удаление обработчика события
     * 
     * @param string $eventName Имя события
     * @param callable $callback Функция обработчик для удаления
     * @return bool Успешность удаления
     */
    public function off($eventName, $callback = null) {
        if (!isset(self::$events[$eventName])) {
            return false;
        }
        
        if ($callback === null) {
            // Удаляем все обработчики для данного события
            unset(self::$events[$eventName]);
            return true;
        }
        
        // Находим и удаляем конкретный обработчик
        foreach (self::$events[$eventName] as $key => $event) {
            if ($event['callback'] === $callback) {
                unset(self::$events[$eventName][$key]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получение списка всех зарегистрированных событий
     * 
     * @return array Массив имен событий
     */
    public function getRegisteredEvents() {
        return array_keys(self::$events);
    }
} 
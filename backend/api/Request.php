<?php
namespace API;

/**
 * Класс для работы с HTTP-запросами
 */
class Request {
    private $params;
    private $body;
    private $method;
    private $headers;
    
    /**
     * Конструктор
     */
    public function __construct() {
        // Получаем метод запроса
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // Парсим параметры запроса
        $this->params = $_GET;
        
        // Получаем заголовки
        $this->headers = $this->getRequestHeaders();
        
        // Получаем тело запроса
        $this->body = $this->parseRequestBody();
        
        // Для отладки
        error_log("Request initialized with method: {$this->method}");
        error_log("Request params: " . json_encode($this->params));
        error_log("Request body: " . json_encode($this->body));
    }
    
    /**
     * Получение значения параметра из запроса
     * 
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение параметра или значение по умолчанию
     */
    public function get($key, $default = null) {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        
        return $default;
    }
    
    /**
     * Получение всех параметров запроса
     * 
     * @return array Параметры запроса
     */
    public function getParams() {
        return $this->params;
    }
    
    /**
     * Установка параметра
     * 
     * @param string $key Ключ параметра
     * @param mixed $value Значение параметра
     */
    public function setParam($key, $value) {
        $this->params[$key] = $value;
    }
    
    /**
     * Получение JSON-данных из тела запроса
     * 
     * @return array|null Данные в формате ассоциативного массива или null в случае ошибки
     */
    public function getJson() {
        return $this->body;
    }
    
    /**
     * Установка JSON-данных в тело запроса
     * 
     * @param array $data Данные для установки
     * @return $this Текущий объект для цепочки вызовов
     */
    public function setJson($data) {
        $this->body = $data;
        error_log("Request: JSON data set manually: " . json_encode($data));
        return $this;
    }
    
    /**
     * Получение заголовка запроса
     * 
     * @param string $name Имя заголовка
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение заголовка или значение по умолчанию
     */
    public function getHeader($name, $default = null) {
        $normalizedName = strtolower($name);
        return isset($this->headers[$normalizedName]) ? $this->headers[$normalizedName] : $default;
    }
    
    /**
     * Получение метода запроса
     * 
     * @return string Метод запроса (GET, POST, PUT, DELETE и т.д.)
     */
    public function getMethod() {
        return $this->method;
    }
    
    /**
     * Получение заголовков запроса
     * 
     * @return array Заголовки запроса
     */
    private function getRequestHeaders() {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = strtolower(str_replace('HTTP_', '', $key));
                $headerName = str_replace('_', '-', $headerName);
                $headers[$headerName] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Парсинг тела запроса
     * 
     * @return array|null Данные из тела запроса или null в случае ошибки
     */
    private function parseRequestBody() {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        $method = $this->method;
        
        // Для отладки
        error_log("Content-Type: {$contentType}");
        
        // Обработка JSON
        if (strpos($contentType, 'application/json') !== false) {
            $body = file_get_contents('php://input');
            
            if (!empty($body)) {
                error_log("Raw request body: {$body}");
                $data = json_decode($body, true);
                
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                }
                
                return $data;
            }
        } 
        // Обработка form-data
        else if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            if ($method === 'POST') {
                return $_POST;
            } else if ($method === 'PUT' || $method === 'DELETE') {
                // Для PUT и DELETE запросов
                $body = file_get_contents('php://input');
                parse_str($body, $data);
                return $data;
            }
        }
        // Если метод POST и не указан Content-Type, используем $_POST
        else if ($method === 'POST') {
            return $_POST;
        }
        // Для других методов пытаемся обработать тело запроса
        else if ($method === 'PUT' || $method === 'DELETE') {
            $body = file_get_contents('php://input');
            
            if (!empty($body)) {
                // Пробуем парсить как JSON
                $data = json_decode($body, true);
                if ($data !== null || json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
                
                // Пробуем парсить как form-data
                parse_str($body, $data);
                if (!empty($data)) {
                    return $data;
                }
            }
        }
        
        return [];
    }
} 
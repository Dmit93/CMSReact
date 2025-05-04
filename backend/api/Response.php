<?php
namespace API;

/**
 * Класс для работы с ответами API
 */
class Response {
    /**
     * Отправка JSON-ответа
     * 
     * @param mixed $data Данные для отправки
     * @param int $status HTTP-статус ответа
     * @param array $headers Дополнительные заголовки
     * @return array Данные ответа
     */
    public static function json($data, $status = 200, $headers = []) {
        // Устанавливаем статус ответа
        http_response_code($status);
        
        // Устанавливаем заголовок Content-Type
        header('Content-Type: application/json');
        
        // Устанавливаем дополнительные заголовки
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
        
        // Возвращаем данные
        return $data;
    }
    
    /**
     * Отправка ответа с ошибкой
     * 
     * @param string $message Сообщение об ошибке
     * @param int $status HTTP-статус ответа
     * @param array $headers Дополнительные заголовки
     * @return array Данные ответа
     */
    public static function error($message, $status = 400, $headers = []) {
        return self::json([
            'success' => false,
            'error' => $message
        ], $status, $headers);
    }
    
    /**
     * Отправка ответа с успешным результатом
     * 
     * @param mixed $data Данные для отправки
     * @param string|null $message Сообщение об успехе
     * @param int $status HTTP-статус ответа
     * @param array $headers Дополнительные заголовки
     * @return array Данные ответа
     */
    public static function success($data = null, $message = null, $status = 200, $headers = []) {
        $response = [
            'success' => true
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return self::json($response, $status, $headers);
    }
} 
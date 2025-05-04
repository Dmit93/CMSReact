<?php
namespace Controllers;

use Models\User;
use API\Auth;

class UserController {
    private $userModel;
    private $auth;
    
    public function __construct() {
        $this->userModel = new User();
        $this->auth = new Auth();
    }
    
    public function getAll() {
        // Проверяем авторизацию
        $currentUser = $this->auth->authenticateRequest();
        
        if (!$currentUser) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        // Проверяем права доступа
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Forbidden'];
        }
        
        // Получаем параметры запроса
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id';
        $sortOrder = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'ASC';
        
        // Формируем фильтры
        $filters = [
            'search' => $_GET['search'] ?? '',
            'role' => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        // Получаем пользователей
        $result = $this->userModel->getAll($page, $limit, $sortBy, $sortOrder, $filters);
        
        return $result;
    }
    
    public function getById($id) {
        // Проверяем авторизацию
        $currentUser = $this->auth->authenticateRequest();
        
        if (!$currentUser) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        // Проверяем права доступа (админ или текущий пользователь)
        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $id) {
            http_response_code(403);
            return ['error' => 'Forbidden'];
        }
        
        // Получаем пользователя
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            http_response_code(404);
            return ['error' => 'User not found'];
        }
        
        return $user;
    }
    
    public function create() {
        // Проверяем авторизацию
        $currentUser = $this->auth->authenticateRequest();
        
        if (!$currentUser) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        // Проверяем права доступа
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Forbidden'];
        }
        
        // Получаем данные из запроса
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            return ['error' => 'Invalid request data'];
        }
        
        // Создаем пользователя
        $result = $this->userModel->create($data);
        
        if (!$result['success']) {
            http_response_code(400);
            return ['error' => $result['message']];
        }
        
        http_response_code(201);
        return $result['data'];
    }
    
    public function update($id) {
        // Проверяем авторизацию
        $currentUser = $this->auth->authenticateRequest();
        
        if (!$currentUser) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        // Проверяем права доступа (админ или текущий пользователь)
        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $id) {
            http_response_code(403);
            return ['error' => 'Forbidden'];
        }
        
        // Получаем данные из запроса
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            return ['error' => 'Invalid request data'];
        }
        
        // Если пользователь не админ, запрещаем менять роль
        if ($currentUser['role'] !== 'admin' && isset($data['role'])) {
            unset($data['role']);
        }
        
        // Обновляем пользователя
        $result = $this->userModel->update($id, $data);
        
        if (!$result['success']) {
            http_response_code(400);
            return ['error' => $result['message']];
        }
        
        return $result['data'];
    }
    
    public function delete($id) {
        // Проверяем авторизацию
        $currentUser = $this->auth->authenticateRequest();
        
        if (!$currentUser) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        // Проверяем права доступа
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Forbidden'];
        }
        
        // Запрещаем удалять себя
        if ($currentUser['id'] == $id) {
            http_response_code(400);
            return ['error' => 'You cannot delete your own account'];
        }
        
        // Удаляем пользователя
        $result = $this->userModel->delete($id);
        
        if (!$result['success']) {
            http_response_code(400);
            return ['error' => $result['message']];
        }
        
        return $result;
    }
    
    public function login() {
        try {
            // Получаем данные из запроса
            $inputData = file_get_contents('php://input');
            if (empty($inputData)) {
                error_log("No input data received in login method");
                http_response_code(400);
                return ['error' => 'No input data received'];
            }
            
            $data = json_decode($inputData, true);
            if (!$data) {
                error_log("Failed to decode JSON: " . json_last_error_msg() . ", Input: " . $inputData);
                http_response_code(400);
                return ['error' => 'Invalid JSON data: ' . json_last_error_msg()];
            }
            
            if (!isset($data['email']) || !isset($data['password'])) {
                error_log("Missing required fields. Email: " . (isset($data['email']) ? 'yes' : 'no') . 
                         ", Password: " . (isset($data['password']) ? 'yes' : 'no'));
                http_response_code(400);
                return ['error' => 'Email and password are required'];
            }
            
            // Выполняем вход
            $result = $this->auth->login($data['email'], $data['password']);
            
            if (!$result) {
                error_log("Login failed for email: " . $data['email']);
                http_response_code(401);
                return ['error' => 'Invalid email or password'];
            }
            
            error_log("Login successful for email: " . $data['email']);
            return $result;
        } catch (\Exception $e) {
            error_log("Exception in login method: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function register() {
        // Получаем данные из запроса
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            return ['error' => 'Invalid request data'];
        }
        
        // Устанавливаем роль по умолчанию для регистрации через API
        $data['role'] = 'user';
        
        // Регистрируем пользователя
        $result = $this->auth->register($data);
        
        if (!$result) {
            http_response_code(400);
            return ['error' => 'User with this email already exists'];
        }
        
        http_response_code(201);
        return $result;
    }
    
    public function getCurrentUser() {
        // Проверяем авторизацию
        $currentUser = $this->auth->authenticateRequest();
        
        if (!$currentUser) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        return $currentUser;
    }
} 
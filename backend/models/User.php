<?php
namespace Models;

use API\Database;

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($page = 1, $limit = 20, $sortBy = 'id', $sortOrder = 'ASC', $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = '';
        $params = [];
        
        // Формируем условия фильтрации
        if (!empty($filters)) {
            $conditions = [];
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $conditions[] = "(name LIKE ? OR email LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (isset($filters['role']) && !empty($filters['role'])) {
                $conditions[] = "role = ?";
                $params[] = $filters['role'];
            }
            
            if (isset($filters['status']) && !empty($filters['status'])) {
                $conditions[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($conditions)) {
                $whereClause = "WHERE " . implode(" AND ", $conditions);
            }
        }
        
        // Запрос на получение пользователей
        $sql = "SELECT * FROM users {$whereClause} ORDER BY {$sortBy} {$sortOrder} LIMIT {$limit} OFFSET {$offset}";
        $users = $this->db->fetchAll($sql, $params);
        
        // Запрос на получение общего количества пользователей
        $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = $countResult['total'];
        
        return [
            'data' => array_map([$this, 'sanitizeUser'], $users),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }
    
    public function getById($id) {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$user) {
            return null;
        }
        
        return $this->sanitizeUser($user);
    }
    
    public function create($data) {
        // Проверяем, существует ли пользователь с таким email
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?", 
            [$data['email']]
        );
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Пользователь с таким email уже существует'
            ];
        }
        
        // Валидация данных
        $requiredFields = ['name', 'email', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => "Поле {$field} обязательно для заполнения"
                ];
            }
        }
        
        // Хешируем пароль
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Добавляем статус и даты
        $data['status'] = $data['status'] ?? 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Добавляем пользователя
        $userId = $this->db->insert('users', $data);
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Ошибка при создании пользователя'
            ];
        }
        
        // Получаем данные нового пользователя
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        
        return [
            'success' => true,
            'data' => $this->sanitizeUser($user)
        ];
    }
    
    public function update($id, $data) {
        // Проверяем, существует ли пользователь
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Пользователь не найден'
            ];
        }
        
        // Проверяем, если меняется email, что новый email не занят
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            $existingUser = $this->db->fetch(
                "SELECT id FROM users WHERE email = ? AND id != ?", 
                [$data['email'], $id]
            );
            
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Пользователь с таким email уже существует'
                ];
            }
        }
        
        // Если меняем пароль, хешируем его
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        
        // Обновляем дату изменения
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Обновляем пользователя
        $updated = $this->db->update('users', $data, 'id = ?', [$id]);
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении пользователя'
            ];
        }
        
        // Получаем обновленные данные пользователя
        $updatedUser = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        
        return [
            'success' => true,
            'data' => $this->sanitizeUser($updatedUser)
        ];
    }
    
    public function delete($id) {
        // Проверяем, существует ли пользователь
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Пользователь не найден'
            ];
        }
        
        // Удаляем пользователя
        $deleted = $this->db->delete('users', 'id = ?', [$id]);
        
        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Ошибка при удалении пользователя'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Пользователь успешно удален'
        ];
    }
    
    private function sanitizeUser($user) {
        // Удаляем чувствительные данные
        unset($user['password']);
        
        return $user;
    }
} 
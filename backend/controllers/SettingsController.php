<?php
namespace Controllers;

use Core\Core;
use API\Database;

class SettingsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получить все настройки
     */
    public function getAll() {
        try {
            $query = "SELECT name, value FROM settings";
            $settings = $this->db->fetchAll($query);
            
            // Преобразуем результат в ассоциативный массив name => value
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['name']] = $setting['value'];
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при получении настроек: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновить настройки
     */
    public function update($data) {
        try {
            if (!is_array($data) || empty($data)) {
                return [
                    'success' => false,
                    'message' => 'Необходимо указать настройки для обновления'
                ];
            }
            
            $this->db->beginTransaction();
            
            foreach ($data as $name => $value) {
                // Проверяем, существует ли уже такая настройка
                $existingQuery = "SELECT id FROM settings WHERE name = ?";
                $existing = $this->db->fetch($existingQuery, [$name]);
                
                if ($existing) {
                    // Обновляем существующую настройку
                    $this->db->update('settings', ['value' => $value], 'name = ?', [$name]);
                } else {
                    // Создаем новую настройку
                    $this->db->insert('settings', [
                        'name' => $name,
                        'value' => $value
                    ]);
                }
            }
            
            $this->db->commit();
            
            // Вызываем событие обновления настроек
            Core::getInstance()->trigger('settings.updated', [
                'settings' => $data
            ]);
            
            return [
                'success' => true,
                'message' => 'Настройки успешно обновлены'
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении настроек: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить значение настройки
     */
    public function getByName($name) {
        try {
            $query = "SELECT value FROM settings WHERE name = ?";
            $setting = $this->db->fetch($query, [$name]);
            
            if (!$setting) {
                return [
                    'success' => false,
                    'message' => 'Настройка не найдена'
                ];
            }
            
            return [
                'success' => true,
                'data' => $setting['value']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при получении настройки: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удалить настройку
     */
    public function delete($name) {
        try {
            $result = $this->db->delete('settings', 'name = ?', [$name]);
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Не удалось удалить настройку'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Настройка успешно удалена'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при удалении настройки: ' . $e->getMessage()
            ];
        }
    }
} 
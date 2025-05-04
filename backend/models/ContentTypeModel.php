<?php
namespace Models;

class ContentTypeModel {
    private $db;
    
    public function __construct() {
        $this->db = \API\Database::getInstance();
    }
    
    /**
     * Получить все типы контента
     */
    public function getAll() {
        try {
            $contentTypes = $this->db->fetchAll(
                "SELECT * FROM content_types ORDER BY label ASC"
            );
            
            return $contentTypes;
        } catch (\Exception $e) {
            throw new \Exception('Не удалось получить типы контента: ' . $e->getMessage());
        }
    }
    
    /**
     * Получить тип контента по ID
     */
    public function getById($id) {
        try {
            // Преобразуем любой ID в строку для логирования
            $idStr = is_null($id) ? 'NULL' : (string)$id;
            error_log("ContentTypeModel.getById() - Получен ID: " . $idStr . " (тип: " . gettype($id) . ")");
            
            // Проверка на пустой ID
            if ($id === null || $id === '') {
                error_log("ContentTypeModel.getById() - ID пустой, возвращаем null");
                return null;
            }
            
            // Простая подготовка ID для запроса
            $idForQuery = is_numeric($id) ? (int)$id : 0;
            
            error_log("ContentTypeModel.getById() - Выполняем SQL запрос с ID: " . $idForQuery);
            
            // Выполняем запрос
            $sql = "SELECT * FROM content_types WHERE id = ?";
            error_log("ContentTypeModel.getById() - SQL: " . $sql . " (параметр: " . $idForQuery . ")");
            
            $result = $this->db->fetch($sql, [$idForQuery]);
            
            if (!$result) {
                error_log("ContentTypeModel.getById() - Тип контента не найден для ID: " . $idForQuery);
                // Пробуем получить список всех типов контента для отладки
                $allTypes = $this->db->fetchAll("SELECT id, name FROM content_types LIMIT 5");
                if ($allTypes) {
                    error_log("ContentTypeModel.getById() - Доступные типы контента: " . json_encode($allTypes));
                }
                return null;
            }
            
            error_log("ContentTypeModel.getById() - Найден тип контента с ID " . $result['id'] . ", name: " . $result['name']);
            return $result;
        } catch (\Exception $e) {
            error_log("ContentTypeModel.getById() - Ошибка: " . $e->getMessage());
            throw new \Exception('Не удалось получить тип контента: ' . $e->getMessage());
        }
    }
    
    /**
     * Получить тип контента по slug
     */
    public function getBySlug($slug) {
        return $this->db->fetch("SELECT * FROM content_types WHERE slug = ?", [$slug]);
    }
    
    /**
     * Создать новый тип контента
     */
    public function create($data) {
        try {
            // Проверяем, нет ли дублирования name или slug
            $existing = $this->db->fetch(
                "SELECT id FROM content_types WHERE name = ? OR slug = ?", 
                [$data['name'], $data['slug']]
            );
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Тип контента с таким именем или slug уже существует'
                ];
            }
            
            // Добавляем значение по умолчанию для поля fields, если оно не указано
            if (!isset($data['fields'])) {
                $data['fields'] = '[]';
            }
            
            $id = $this->db->insert('content_types', $data);
            
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'Не удалось создать тип контента'
                ];
            }
            
            $contentType = $this->getById($id);
            
            return [
                'success' => true,
                'data' => $contentType
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при создании типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновить тип контента
     */
    public function update($id, $data) {
        try {
            // Проверяем, нет ли дублирования name или slug, исключая текущий ID
            if (isset($data['name']) || isset($data['slug'])) {
                $existing = $this->db->fetch(
                    "SELECT id FROM content_types WHERE (name = ? OR slug = ?) AND id != ?", 
                    [
                        $data['name'] ?? '', 
                        $data['slug'] ?? '', 
                        $id
                    ]
                );
                
                if ($existing) {
                    return [
                        'success' => false,
                        'message' => 'Тип контента с таким именем или slug уже существует'
                    ];
                }
            }
            
            // Проверяем, если обновляется поле fields и оно пустое, устанавливаем его в пустой массив
            if (isset($data['fields']) && empty($data['fields'])) {
                $data['fields'] = '[]';
            }
            
            $success = $this->db->update('content_types', $data, 'id = ?', [$id]);
            
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Не удалось обновить тип контента'
                ];
            }
            
            $contentType = $this->getById($id);
            
            return [
                'success' => true,
                'data' => $contentType
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удалить тип контента
     */
    public function delete($id) {
        try {
            // Проверяем, есть ли связанный контент
            $contentExists = $this->db->fetch(
                "SELECT COUNT(*) as count FROM content WHERE content_type_id = ?", 
                [$id]
            );
            
            if ($contentExists && $contentExists['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Невозможно удалить тип контента, так как существует связанный контент'
                ];
            }
            
            $success = $this->db->delete('content_types', 'id = ?', [$id]);
            
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Не удалось удалить тип контента'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Тип контента успешно удален'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при удалении типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить все поля типа контента
     */
    public function getFields($contentTypeId) {
        return $this->db->fetchAll(
            "SELECT * FROM content_type_fields WHERE content_type_id = ? ORDER BY `order`", 
            [$contentTypeId]
        );
    }
    
    /**
     * Получить активные типы контента для меню
     */
    public function getActiveForMenu() {
        return $this->db->fetchAll(
            "SELECT id, label, slug, icon FROM content_types 
             WHERE is_active = 1 
             ORDER BY menu_position, label"
        );
    }
} 
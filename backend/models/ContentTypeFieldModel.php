<?php
namespace Models;

class ContentTypeFieldModel {
    private $db;
    
    public function __construct() {
        $this->db = \API\Database::getInstance();
    }
    
    /**
     * Получить все поля типа контента
     */
    public function getAllByContentType($contentTypeId) {
        $fields = $this->db->fetchAll(
            "SELECT * FROM content_type_fields 
            WHERE content_type_id = ? 
            ORDER BY `order`", 
            [$contentTypeId]
        );
        
        // Декодируем JSON-поля обратно в массивы для всех полей
        foreach ($fields as &$field) {
            if (isset($field['options']) && is_string($field['options']) && !empty($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
            
            if (isset($field['validation']) && is_string($field['validation']) && !empty($field['validation'])) {
                $field['validation'] = json_decode($field['validation'], true);
            }
        }
        
        return $fields;
    }
    
    /**
     * Получить поле по ID
     */
    public function getById($id) {
        $field = $this->db->fetch(
            "SELECT * FROM content_type_fields WHERE id = ?", 
            [$id]
        );
        
        // Декодируем JSON-поля обратно в массивы
        if ($field) {
            if (isset($field['options']) && is_string($field['options']) && !empty($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
            
            if (isset($field['validation']) && is_string($field['validation']) && !empty($field['validation'])) {
                $field['validation'] = json_decode($field['validation'], true);
            }
        }
        
        return $field;
    }
    
    /**
     * Получить поле по имени в рамках типа контента
     */
    public function getByName($contentTypeId, $name) {
        return $this->db->fetch(
            "SELECT * FROM content_type_fields 
            WHERE content_type_id = ? AND name = ?", 
            [$contentTypeId, $name]
        );
    }
    
    /**
     * Создать новое поле
     */
    public function create($data) {
        try {
            // Проверяем, существует ли тип контента
            $contentType = $this->db->fetch(
                "SELECT id FROM content_types WHERE id = ?", 
                [$data['content_type_id']]
            );
            
            if (!$contentType) {
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            // Проверяем, нет ли дублирования имени поля в этом типе контента
            $existing = $this->db->fetch(
                "SELECT id FROM content_type_fields 
                WHERE content_type_id = ? AND name = ?", 
                [$data['content_type_id'], $data['name']]
            );
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Поле с таким именем уже существует в этом типе контента'
                ];
            }
            
            // Если order не указан, устанавливаем его в конец списка
            if (!isset($data['order']) || $data['order'] === null) {
                $maxOrder = $this->db->fetch(
                    "SELECT MAX(`order`) as max_order FROM content_type_fields 
                    WHERE content_type_id = ?", 
                    [$data['content_type_id']]
                );
                
                $data['order'] = $maxOrder['max_order'] ? $maxOrder['max_order'] + 1 : 0;
            }
            
            // Преобразуем массивы options и validation в JSON
            if (isset($data['options']) && is_array($data['options'])) {
                $data['options'] = json_encode($data['options']);
            }
            
            if (isset($data['validation']) && is_array($data['validation'])) {
                $data['validation'] = json_encode($data['validation']);
            }
            
            // Добавляем временные метки
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $id = $this->db->insert('content_type_fields', $data);
            
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'Не удалось создать поле'
                ];
            }
            
            $field = $this->getById($id);
            
            // Декодируем JSON-поля обратно в массивы
            if (isset($field['options']) && is_string($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
            
            if (isset($field['validation']) && is_string($field['validation'])) {
                $field['validation'] = json_decode($field['validation'], true);
            }
            
            return [
                'success' => true,
                'data' => $field
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при создании поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновить поле
     */
    public function update($id, $data) {
        try {
            // Проверяем, существует ли поле
            $field = $this->getById($id);
            
            if (!$field) {
                return [
                    'success' => false,
                    'message' => 'Поле не найдено'
                ];
            }
            
            // Проверяем, нет ли дублирования имени, если оно меняется
            if (isset($data['name']) && $data['name'] !== $field['name']) {
                $existing = $this->db->fetch(
                    "SELECT id FROM content_type_fields 
                    WHERE content_type_id = ? AND name = ? AND id != ?", 
                    [$field['content_type_id'], $data['name'], $id]
                );
                
                if ($existing) {
                    return [
                        'success' => false,
                        'message' => 'Поле с таким именем уже существует в этом типе контента'
                    ];
                }
            }
            
            // Преобразуем массивы options и validation в JSON
            if (isset($data['options']) && is_array($data['options'])) {
                $data['options'] = json_encode($data['options']);
            }
            
            if (isset($data['validation']) && is_array($data['validation'])) {
                $data['validation'] = json_encode($data['validation']);
            }
            
            // Добавляем временную метку обновления
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $success = $this->db->update('content_type_fields', $data, 'id = ?', [$id]);
            
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Не удалось обновить поле'
                ];
            }
            
            $field = $this->getById($id);
            
            return [
                'success' => true,
                'data' => $field
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удалить поле
     */
    public function delete($id) {
        try {
            // Проверяем, есть ли связанные значения
            $valuesExist = $this->db->fetch(
                "SELECT COUNT(*) as count FROM content_field_values WHERE field_id = ?", 
                [$id]
            );
            
            if ($valuesExist && $valuesExist['count'] > 0) {
                // Сначала удаляем связанные значения
                $this->db->delete('content_field_values', 'field_id = ?', [$id]);
            }
            
            $success = $this->db->delete('content_type_fields', 'id = ?', [$id]);
            
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Не удалось удалить поле'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Поле успешно удалено'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при удалении поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Изменить порядок полей
     */
    public function reorder($contentTypeId, $fieldIds) {
        try {
            $this->db->beginTransaction();
            
            foreach ($fieldIds as $order => $fieldId) {
                $this->db->update(
                    'content_type_fields',
                    ['order' => $order],
                    'id = ? AND content_type_id = ?',
                    [$fieldId, $contentTypeId]
                );
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Порядок полей успешно обновлен'
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => 'Ошибка при изменении порядка полей: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить доступные типы полей
     */
    public function getAvailableFieldTypes() {
        return [
            'text' => [
                'label' => 'Текстовое поле',
                'options' => [
                    'max_length' => 255
                ]
            ],
            'textarea' => [
                'label' => 'Текстовая область',
                'options' => [
                    'rows' => 5
                ]
            ],
            'number' => [
                'label' => 'Числовое поле',
                'options' => [
                    'min' => null,
                    'max' => null,
                    'step' => 1
                ]
            ],
            'select' => [
                'label' => 'Выпадающий список',
                'options' => [
                    'values' => []
                ]
            ],
            'checkbox' => [
                'label' => 'Флажок',
                'options' => []
            ],
            'radio' => [
                'label' => 'Радиокнопки',
                'options' => [
                    'values' => []
                ]
            ],
            'date' => [
                'label' => 'Дата',
                'options' => [
                    'min' => null,
                    'max' => null
                ]
            ],
            'image' => [
                'label' => 'Изображение',
                'options' => [
                    'max_size' => 2048, // KB
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
                ]
            ],
            'file' => [
                'label' => 'Файл',
                'options' => [
                    'max_size' => 10240, // KB
                    'allowed_types' => []
                ]
            ],
            'wysiwyg' => [
                'label' => 'Визуальный редактор',
                'options' => [
                    'toolbar' => 'basic' // basic, full
                ]
            ]
        ];
    }
} 
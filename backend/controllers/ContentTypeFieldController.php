<?php
namespace Controllers;

use Models\ContentTypeFieldModel;
use Core\Core;
use Core\Logger;

/**
 * Контроллер для управления полями типов контента
 */
class ContentTypeFieldController {
    private $model;
    private $core;
    private $logger;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->model = new ContentTypeFieldModel();
        $this->core = Core::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Получение полей типа контента
     */
    public function getByContentType($contentTypeId) {
        try {
            // Проверка ID типа контента
            if (!$contentTypeId) {
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Запускаем событие перед получением полей
            $this->core->trigger('content_type_field.before_get_all', [
                'contentTypeId' => $contentTypeId
            ]);
            
            $fields = $this->model->getAllByContentType($contentTypeId);
            
            // Запускаем событие после получения полей
            $result = $this->core->trigger('content_type_field.after_get_all', [
                'contentTypeId' => $contentTypeId,
                'fields' => &$fields
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $fields
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content type fields: ' . $e->getMessage(), [
                'contentTypeId' => $contentTypeId
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении полей типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение поля по ID
     */
    public function getById($requestOrId, $maybeId = null) {
        try {
            // Определяем ID поля в зависимости от того, как вызван метод
            $id = $maybeId !== null ? $maybeId : $requestOrId;
            
            // Проверка ID
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID поля не указан'
                ];
            }
            
            // Запускаем событие перед получением поля
            $this->core->trigger('content_type_field.before_get', ['id' => $id]);
            
            $field = $this->model->getById($id);
            
            if (!$field) {
                return [
                    'success' => false,
                    'message' => 'Поле не найдено'
                ];
            }
            
            // Запускаем событие после получения поля
            $result = $this->core->trigger('content_type_field.after_get', [
                'field' => &$field
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $field
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content type field: ' . $e->getMessage(), [
                'id' => $id
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Создание нового поля типа контента
     */
    public function create($data, $contentTypeId) {
        try {
            // Проверка ID типа контента
            if (!$contentTypeId) {
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
           
            // Добавляем ID типа контента в данные
            $data['content_type_id'] = $contentTypeId;
            
            // Проверка данных
            if (!isset($data['name']) || !isset($data['label']) || !isset($data['field_type'])) {
                return [
                    'success' => false,
                    'message' => 'Не указаны обязательные поля (name, label, field_type)'
                ];
            }
            
            // Запускаем событие перед созданием поля
            $beforeResult = $this->core->trigger('content_type_field.before_save', [
                'data' => &$data,
                'isNew' => true
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->create($data);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Запускаем событие после создания поля
            $afterResult = $this->core->trigger('content_type_field.after_save', [
                'field' => $result['data'],
                'isNew' => true
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type field created', [
                'name' => $data['name'],
                'contentTypeId' => $contentTypeId,
                'id' => $result['data']['id'] ?? null
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error creating content type field: ' . $e->getMessage(), [
                'contentTypeId' => $contentTypeId,
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при создании поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновление поля типа контента
     */
    public function update($data, $id) {
        try {
            // Проверка ID
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID поля не указан'
                ];
            }
            
            // Убедимся, что ID является числовым
            $id = (int)$id;
            
            // Логируем запрос для отладки
            $this->logger->debug('Обновление поля по ID', ['id' => $id]);
            
            // Запускаем событие перед обновлением поля
            $beforeResult = $this->core->trigger('content_type_field.before_save', [
                'id' => $id,
                'data' => &$data,
                'isNew' => false
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->update($id, $data);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Запускаем событие после обновления поля
            $afterResult = $this->core->trigger('content_type_field.after_save', [
                'field' => $result['data'],
                'isNew' => false
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type field updated', [
                'id' => $id
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error updating content type field: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление поля типа контента
     */
    public function delete($requestData, $id) {
        try {
            // Проверка ID
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID поля не указан'
                ];
            }
            
            // Убедимся, что ID является числовым
            $id = (int)$id;
            
            // Логируем запрос для отладки
            $this->logger->debug('Удаление поля по ID', ['id' => $id]);
            
            // Запускаем событие перед удалением поля
            $beforeResult = $this->core->trigger('content_type_field.before_delete', ['id' => $id]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->delete($id);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Запускаем событие после удаления поля
            $afterResult = $this->core->trigger('content_type_field.after_delete', ['id' => $id]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type field deleted', [
                'id' => $id
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error deleting content type field: ' . $e->getMessage(), [
                'id' => $id
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при удалении поля: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка доступных типов полей
     */
    public function getAvailableFieldTypes() {
        try {
            $fieldTypes = $this->model->getAvailableFieldTypes();
            
            // Запускаем событие для модификации списка типов полей
            $result = $this->core->trigger('content_type_field.get_field_types', [
                'fieldTypes' => &$fieldTypes
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $fieldTypes
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting field types: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении типов полей: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Изменение порядка полей
     */
    public function reorder($contentTypeId, $fieldIds) {
        try {
            // Проверка данных
            if (!$contentTypeId || !is_array($fieldIds) || empty($fieldIds)) {
                return [
                    'success' => false,
                    'message' => 'Не указаны необходимые данные'
                ];
            }
            
            // Запускаем событие перед изменением порядка полей
            $beforeResult = $this->core->trigger('content_type_field.before_reorder', [
                'contentTypeId' => $contentTypeId,
                'fieldIds' => &$fieldIds
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->reorder($contentTypeId, $fieldIds);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Запускаем событие после изменения порядка полей
            $afterResult = $this->core->trigger('content_type_field.after_reorder', [
                'contentTypeId' => $contentTypeId,
                'fieldIds' => $fieldIds
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type fields reordered', [
                'contentTypeId' => $contentTypeId
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error reordering fields: ' . $e->getMessage(), [
                'contentTypeId' => $contentTypeId,
                'fieldIds' => $fieldIds
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при изменении порядка полей: ' . $e->getMessage()
            ];
        }
    }
} 
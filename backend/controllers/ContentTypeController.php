<?php
namespace Controllers;

use Models\ContentTypeModel;
use Core\Core;
use Core\Logger;

/**
 * Контроллер для управления типами контента
 */
class ContentTypeController {
    private $model;
    private $core;
    private $logger;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->model = new ContentTypeModel();
        $this->core = Core::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Получение всех типов контента
     */
    public function getAll() {
        try {
            // Запускаем событие перед получением типов контента
            $this->core->trigger('content_type.before_get_all');
            
            $contentTypes = $this->model->getAll();
            
            // Запускаем событие после получения типов контента
            $result = $this->core->trigger('content_type.after_get_all', [
                'contentTypes' => &$contentTypes
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $contentTypes
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content types: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении типов контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение типа контента по ID
     */
    public function getById($id) {
        try {
            // Проверка ID
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Запускаем событие перед получением типа контента
            $this->core->trigger('content_type.before_get', ['id' => $id]);
            
            $contentType = $this->model->getById($id);
            
            if (!$contentType) {
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            // Получаем поля типа контента
            $fields = $this->model->getFields($id);
            $contentType['fields'] = $fields;
            
            // Запускаем событие после получения типа контента
            $result = $this->core->trigger('content_type.after_get', [
                'contentType' => &$contentType
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $contentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content type: ' . $e->getMessage(), [
                'id' => $id
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Создание нового типа контента
     */
    public function create($data) {
        try {
            // Проверка данных
            if (!isset($data['name']) || !isset($data['label']) || !isset($data['slug'])) {
                return [
                    'success' => false,
                    'message' => 'Не указаны обязательные поля (name, label, slug)'
                ];
            }
            
            // Запускаем событие перед созданием типа контента
            $beforeResult = $this->core->trigger('content_type.before_save', [
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
            
            // Запускаем событие после создания типа контента
            $afterResult = $this->core->trigger('content_type.after_save', [
                'contentType' => $result['data'],
                'isNew' => true
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type created', [
                'name' => $data['name'],
                'id' => $result['data']['id'] ?? null
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error creating content type: ' . $e->getMessage(), [
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при создании типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновление типа контента
     */
    public function update($id, $data) {
        try {
            // Проверка ID
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Запускаем событие перед обновлением типа контента
            $beforeResult = $this->core->trigger('content_type.before_save', [
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
            
            // Запускаем событие после обновления типа контента
            $afterResult = $this->core->trigger('content_type.after_save', [
                'contentType' => $result['data'],
                'isNew' => false
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type updated', [
                'id' => $id
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error updating content type: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении типа контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление типа контента
     */
    public function delete($id) {
        try {
            // Проверка ID
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Запускаем событие перед удалением типа контента
            $beforeResult = $this->core->trigger('content_type.before_delete', ['id' => $id]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->delete($id);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Запускаем событие после удаления типа контента
            $afterResult = $this->core->trigger('content_type.after_delete', ['id' => $id]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content type deleted', [
                'id' => $id
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error deleting content type: ' . $e->getMessage(), [
                'id' => $id
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при удалении типа контента: ' . $e->getMessage()
            ];
        }
    }
} 
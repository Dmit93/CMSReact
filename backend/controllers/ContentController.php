<?php
namespace Controllers;

use Models\ContentModel;
use Models\ContentTypeModel;
use Core\Core;
use Core\Logger;
use Core\Theme;

/**
 * Контроллер для управления контентом
 */
class ContentController {
    private $model;
    private $contentTypeModel;
    private $core;
    private $logger;
    private $theme;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->model = new ContentModel();
        $this->contentTypeModel = new ContentTypeModel();
        $this->core = Core::getInstance();
        $this->logger = Logger::getInstance();
        $this->theme = new Theme();
    }
    
    /**
     * Получение всех записей определенного типа контента
     */
    public function getAll($typeId, $page = 1, $limit = 20, $filters = []) {
        try {
            // Проверка ID типа контента
            if (!$typeId) {
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Проверяем существование типа контента
            $contentType = $this->contentTypeModel->getById($typeId);
            
            if (!$contentType) {
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            // Запускаем событие перед получением записей
            $this->core->trigger('content.before_get_all', [
                'typeId' => $typeId,
                'page' => $page,
                'limit' => $limit,
                'filters' => &$filters
            ]);
            
            // Рассчитываем смещение для пагинации
            $offset = ($page - 1) * $limit;
            
            // Подготавливаем опции для метода getAllByType
            $options = [
                'limit' => $limit,
                'offset' => $offset,
                'filters' => $filters
            ];
            
            $result = $this->model->getAllByType($typeId, $options);
            
            // Запускаем событие после получения записей
            $eventResult = $this->core->trigger('content.after_get_all', [
                'typeId' => $typeId,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters,
                'result' => &$result
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($eventResult !== null) {
                return $eventResult;
            }
            
            // Вычисляем общее количество страниц
            $totalPages = ceil($result['total'] / $limit);
            
            return [
                'success' => true,
                'data' => $result['items'],
                'meta' => [
                    'total' => $result['total'],
                    'total_pages' => $totalPages,
                    'page' => $page,
                    'limit' => $limit
                ],
                'content_type' => $contentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content items: ' . $e->getMessage(), [
                'typeId' => $typeId,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении записей: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение контента по типу
     */
    public function getAllByType($typeSlug, $requestData = []) {
        try {
            // Находим тип контента по slug
            $contentType = $this->contentTypeModel->getBySlug($typeSlug);
            
            if (!$contentType) {
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            $contentTypeId = $contentType['id'];
            
            // Формируем параметры для запроса
            $options = [
                'limit' => isset($requestData['limit']) ? intval($requestData['limit']) : 20,
                'offset' => isset($requestData['offset']) ? intval($requestData['offset']) : 0,
                'order' => isset($requestData['order']) ? $requestData['order'] : 'created_at DESC'
            ];
            
            // Добавляем фильтры, если они указаны
            if (isset($requestData['filters']) && is_array($requestData['filters'])) {
                $options['filters'] = $requestData['filters'];
            }
            
            // Запускаем событие перед получением контента
            $this->core->trigger('content.before_get_all', [
                'contentTypeId' => $contentTypeId,
                'options' => &$options
            ]);
            
            $content = $this->model->getAllByType($contentTypeId, $options);
            
            // Запускаем событие после получения контента
            $result = $this->core->trigger('content.after_get_all', [
                'contentTypeId' => $contentTypeId,
                'content' => &$content
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $content,
                'content_type' => $contentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content: ' . $e->getMessage(), [
                'type' => $typeSlug
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение записи контента по ID
     */
    public function getById($typeId, $id) {
        try {
            $this->logger->info('Запрос на получение записи по ID', [
                'typeId' => $typeId,
                'id' => $id
            ]);
            
            // Проверка параметров
            if (!$typeId) {
                $this->logger->error('ID типа контента не указан при получении записи');
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Проверяем, является ли typeId числом или строкой
            if (is_numeric($typeId)) {
                // Если typeId - число, ищем по ID
                $contentType = $this->contentTypeModel->getById((int)$typeId);
                $this->logger->info('Поиск типа контента по числовому ID', [
                    'typeId' => $typeId,
                    'found' => $contentType ? 'да' : 'нет'
                ]);
            } else {
                // Если typeId - строка, ищем по slug
                $contentType = $this->contentTypeModel->getBySlug($typeId);
                $this->logger->info('Поиск типа контента по slug', [
                    'typeSlug' => $typeId,
                    'found' => $contentType ? 'да' : 'нет'
                ]);
            }
            
            if (!$contentType) {
                $this->logger->error('Тип контента не найден при получении записи', [
                    'typeId' => $typeId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            $contentTypeId = $contentType['id'];
            
            // Запускаем событие перед получением записи
            $this->core->trigger('content.before_get', [
                'contentTypeId' => $contentTypeId,
                'id' => $id
            ]);
            
            $content = $this->model->getById($id);
            
            if (!$content) {
                $this->logger->error('Запись не найдена', [
                    'contentTypeId' => $contentTypeId,
                    'id' => $id
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Запись не найдена'
                ];
            }
            
            // Запускаем событие после получения записи
            $result = $this->core->trigger('content.after_get', [
                'contentTypeId' => $contentTypeId,
                'content' => &$content
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            $this->logger->info('Запись успешно получена', [
                'contentTypeId' => $contentTypeId,
                'id' => $id
            ]);
            
            return [
                'success' => true,
                'data' => $content,
                'content_type' => $contentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content item: ' . $e->getMessage(), [
                'type' => $typeId,
                'id' => $id
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение записи контента по slug
     */
    public function getBySlug($typeSlug, $slug) {
        try {
            // Находим тип контента по slug
            $contentType = $this->contentTypeModel->getBySlug($typeSlug);
            
            if (!$contentType) {
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            $contentTypeId = $contentType['id'];
            
            // Запускаем событие перед получением записи
            $this->core->trigger('content.before_get_by_slug', [
                'contentTypeId' => $contentTypeId,
                'slug' => $slug
            ]);
            
            $content = $this->model->getBySlug($slug, $contentTypeId);
            
            if (!$content) {
                return [
                    'success' => false,
                    'message' => 'Запись не найдена'
                ];
            }
            
            // Запускаем событие после получения записи
            $result = $this->core->trigger('content.after_get_by_slug', [
                'contentTypeId' => $contentTypeId,
                'content' => &$content
            ]);
            
            // Возвращаем результат события, если он установлен
            if ($result !== null) {
                return $result;
            }
            
            return [
                'success' => true,
                'data' => $content,
                'content_type' => $contentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting content item by slug: ' . $e->getMessage(), [
                'type' => $typeSlug,
                'slug' => $slug
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Создание новой записи контента
     * 
     * Важно: Роутер передает параметры в порядке: 
     * 1. requestData (для POST/PUT)
     * 2. typeId (если указано в URL)
     */
    public function create($data, $typeId = null) {
        try {
            // ВАЖНО: В методе POST первым параметром идут данные запроса, затем typeId
            // Это может отличаться от других методов контроллера
            
            // Логи для отладки поступающих параметров
            $this->logger->info('Параметры create():', [
                'param1_type' => gettype($data),
                'param1_value' => $data,
                'param2_type' => gettype($typeId),
                'param2_value' => $typeId
            ]);
            
            // Добавляем логирование для отладки
            $this->logger->info('Запрос на создание записи контента', [
                'typeId_param' => $typeId,
                'typeId_type' => gettype($typeId),
                'data' => $data
            ]);
            
            // Определяем ID типа контента простым способом
            // 1. Из параметра URL (второй параметр)
            $contentTypeId = null;
            
            if (isset($typeId) && is_numeric($typeId)) {
                $contentTypeId = (int)$typeId;
                $this->logger->info('Используем typeId из URL параметра', ['contentTypeId' => $contentTypeId]);
            }
            
            // 2. Из данных запроса
            if ($contentTypeId === null && isset($data['content_type_id']) && is_numeric($data['content_type_id'])) {
                $contentTypeId = (int)$data['content_type_id'];
                $this->logger->info('Используем content_type_id из данных запроса', ['contentTypeId' => $contentTypeId]);
            }
            
            // 3. Из вложенного объекта contentType
            if ($contentTypeId === null && isset($data['contentType']) && isset($data['contentType']['id']) && is_numeric($data['contentType']['id'])) {
                $contentTypeId = (int)$data['contentType']['id'];
                $this->logger->info('Используем contentType.id из данных', ['contentTypeId' => $contentTypeId]);
            }
            
            // Если ID не найден, возвращаем ошибку
            if ($contentTypeId === null) {
                $this->logger->error('ID типа контента не указан при создании записи', [
                    'typeId_param' => $typeId,
                    'request_data' => $data
                ]);
                
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан или имеет некорректный формат'
                ];
            }
            
            // Проверяем существование типа контента
            $contentType = $this->contentTypeModel->getById($contentTypeId);
            
            $this->logger->info('Результат поиска типа контента:', [
                'contentTypeId' => $contentTypeId,
                'contentTypeFound' => $contentType ? 'да' : 'нет'
            ]);
            
            if (!$contentType) {
                $this->logger->error('Тип контента не найден при создании записи', [
                    'contentTypeId' => $contentTypeId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            // Логируем успешное получение типа контента
            $this->logger->info('Получен тип контента для создания записи', [
                'contentTypeId' => $contentTypeId,
                'contentType' => $contentType['name']
            ]);
            
            // Добавляем ID типа контента в данные
            $data['content_type_id'] = $contentTypeId;
            
            // Если slug не указан, генерируем его из заголовка
            if (!isset($data['slug']) && isset($data['title'])) {
                $data['slug'] = $this->model->generateUniqueSlug($data['title'], $contentTypeId);
            }
            
            // Если не указан автор, используем ID пользователя из сессии
            if (!isset($data['author_id'])) {
                $data['author_id'] = $_SESSION['user_id'] ?? 1; // По умолчанию admin (id=1)
            }
            
            // Запускаем событие перед созданием записи
            $beforeResult = $this->core->trigger('content.before_save', [
                'typeId' => $contentTypeId,
                'data' => &$data,
                'isNew' => true
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->create($data);
            
            if (!$result['success']) {
                $this->logger->error('Ошибка при создании записи через модель', [
                    'contentTypeId' => $contentTypeId,
                    'error' => $result['message']
                ]);
                return $result;
            }
            
            // Запускаем событие после создания записи
            $afterResult = $this->core->trigger('content.after_save', [
                'typeId' => $contentTypeId,
                'content' => $result['data'],
                'isNew' => true
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content item created', [
                'contentTypeId' => $contentTypeId,
                'id' => $result['data']['id'] ?? null
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error creating content item: ' . $e->getMessage(), [
                'typeId' => $typeId,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при создании записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновление записи контента
     * 
     * @param mixed $typeId ID или slug типа контента
     * @param int $id ID записи контента
     * @param array $data Данные для обновления
     * @return array Результат операции
     */
    public function update($typeId, $id, $data) {
        try {
            // Устанавливаем кодировку
            mb_internal_encoding('UTF-8');
            
            // Подробное логирование
            error_log("ContentController.update() - Начало обновления записи. TypeId: {$typeId}, ID: {$id}");
            error_log("ContentController.update() - Входные данные: " . (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : gettype($data)));
            
            // Проверяем первый параметр - это должны быть данные запроса
            if (!is_array($data)) {
                error_log("ContentController.update() - ПРОБЛЕМА: Первый параметр не является массивом! Тип: " . gettype($data));
                
                // Если данные пришли как строка, пробуем парсить JSON
                if (is_string($data) && !empty($data)) {
                    error_log("ContentController.update() - Пробуем декодировать JSON из строки: " . $data);
                    $decoded = json_decode($data, true);
                    if ($decoded !== null) {
                        $data = $decoded;
                        error_log("ContentController.update() - Успешно декодирован JSON: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                    } else {
                        error_log("ContentController.update() - Не удалось декодировать JSON: " . json_last_error_msg());
                    }
                }
                
                // Если данные всё ещё не массив, создаём пустой массив
                if (!is_array($data)) {
                    error_log("ContentController.update() - Создаём пустой массив данных вместо: " . gettype($data));
                    $data = [];
                }
            }
            
            // Проверка ID записи
            $contentId = (int)$id;
            if ($contentId <= 0) {
                error_log("ContentController.update() - Некорректный ID записи: {$id}");
                return [
                    'success' => false,
                    'message' => 'ID записи должен быть положительным числом'
                ];
            }
            
            // Проверка существования записи
            $content = $this->model->getById($contentId);
            if (!$content) {
                error_log("ContentController.update() - Запись не найдена, ID={$contentId}");
                return [
                    'success' => false,
                    'message' => 'Запись не найдена'
                ];
            }
            
            // Проверяем наличие ключевых полей в данных
            if (!isset($data['title']) && isset($content['title'])) {
                error_log("ContentController.update() - Поле title отсутствует в запросе, используем текущее: " . $content['title']);
                $data['title'] = $content['title'];
            }
            
            if (!isset($data['slug']) && isset($content['slug'])) {
                error_log("ContentController.update() - Поле slug отсутствует в запросе, используем текущее: " . $content['slug']);
                $data['slug'] = $content['slug'];
            }
            
            // Обязательно добавляем content_type_id из текущей записи
            if (!isset($data['content_type_id']) && isset($content['content_type_id'])) {
                error_log("ContentController.update() - Добавляем content_type_id: " . $content['content_type_id']);
                $data['content_type_id'] = (int)$content['content_type_id'];
            }
            
            // Добавляем гарантированное обновление для updated_at
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Проверяем данные перед отправкой в модель
            error_log("ContentController.update() - Данные перед отправкой в модель: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Вызываем обновление в модели
            $result = $this->model->update($contentId, $data);
            
            // Логируем результат
            $status = $result['success'] ? 'УСПЕШНО' : 'ОШИБКА';
            error_log("ContentController.update() - Обновление {$status} для записи ID={$contentId}");
            
            // Если успешно, возвращаем обновленные данные
            if ($result['success']) {
                $updatedContent = $this->model->getById($contentId);
                error_log("ContentController.update() - Обновленные данные: " . json_encode($updatedContent, JSON_UNESCAPED_UNICODE));
                $result['data'] = $updatedContent;
            } else {
                error_log("ContentController.update() - Ошибка при обновлении: " . ($result['message'] ?? 'Неизвестная ошибка'));
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("ContentController.update() - Исключение: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление записи контента
     */
    public function delete($typeId, $id) {
        try {
            $this->logger->info('Запрос на удаление записи', [
                'typeId' => $typeId,
                'id' => $id
            ]);
            
            // Проверка параметров
            if (!$typeId) {
                $this->logger->error('ID типа контента не указан при удалении записи');
                return [
                    'success' => false,
                    'message' => 'ID типа контента не указан'
                ];
            }
            
            // Проверяем, является ли typeId числом или строкой
            if (is_numeric($typeId)) {
                // Если typeId - число, ищем по ID
                $contentType = $this->contentTypeModel->getById((int)$typeId);
                $this->logger->info('Поиск типа контента по числовому ID', [
                    'typeId' => $typeId,
                    'found' => $contentType ? 'да' : 'нет'
                ]);
            } else {
                // Если typeId - строка, ищем по slug
                $contentType = $this->contentTypeModel->getBySlug($typeId);
                $this->logger->info('Поиск типа контента по slug', [
                    'typeSlug' => $typeId,
                    'found' => $contentType ? 'да' : 'нет'
                ]);
            }
            
            if (!$contentType) {
                $this->logger->error('Тип контента не найден при удалении записи', [
                    'typeId' => $typeId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            $contentTypeId = $contentType['id'];
            
            // Проверяем, существует ли запись
            $content = $this->model->getById($id);
            
            if (!$content) {
                $this->logger->error('Запись не найдена при удалении', [
                    'contentTypeId' => $contentTypeId,
                    'id' => $id
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Запись не найдена'
                ];
            }
            
            // Запускаем событие перед удалением записи
            $beforeResult = $this->core->trigger('content.before_delete', [
                'contentTypeId' => $contentTypeId,
                'content' => $content
            ]);
            
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->delete($id);
            
            if (!$result['success']) {
                $this->logger->error('Ошибка при удалении записи', [
                    'contentTypeId' => $contentTypeId,
                    'id' => $id,
                    'error' => $result['message']
                ]);
                
                return $result;
            }
            
            // Запускаем событие после удаления записи
            $afterResult = $this->core->trigger('content.after_delete', [
                'contentTypeId' => $contentTypeId,
                'id' => $id
            ]);
            
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Запись успешно удалена', [
                'contentTypeId' => $contentTypeId,
                'id' => $id
            ]);
            
            return [
                'success' => true,
                'message' => 'Запись успешно удалена'
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error deleting content: ' . $e->getMessage(), [
                'type' => $typeId,
                'id' => $id
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при удалении записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Изменение статуса записи контента
     */
    public function changeStatus($typeSlug, $id, $status) {
        try {
            // Находим тип контента по slug
            $contentType = $this->contentTypeModel->getBySlug($typeSlug);
            
            if (!$contentType) {
                return [
                    'success' => false,
                    'message' => 'Тип контента не найден'
                ];
            }
            
            $contentTypeId = $contentType['id'];
            
            // Получаем текущую запись
            $content = $this->model->getById($id, false);
            
            if (!$content) {
                return [
                    'success' => false,
                    'message' => 'Запись не найдена'
                ];
            }
            
            // Проверяем, соответствует ли запись указанному типу контента
            if ($content['content_type_id'] != $contentTypeId) {
                return [
                    'success' => false,
                    'message' => 'Запись не принадлежит указанному типу контента'
                ];
            }
            
            // Запускаем событие перед изменением статуса
            $beforeResult = $this->core->trigger('content.before_status_change', [
                'contentTypeId' => $contentTypeId,
                'id' => $id,
                'oldStatus' => $content['status'],
                'newStatus' => $status
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($beforeResult !== null) {
                return $beforeResult;
            }
            
            $result = $this->model->changeStatus($id, $status);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Запускаем событие после изменения статуса
            $afterResult = $this->core->trigger('content.after_status_change', [
                'contentTypeId' => $contentTypeId,
                'id' => $id,
                'oldStatus' => $content['status'],
                'newStatus' => $status
            ]);
            
            // Если обработчик события вернул результат, возвращаем его
            if ($afterResult !== null) {
                return $afterResult;
            }
            
            $this->logger->info('Content status changed', [
                'type' => $typeSlug,
                'id' => $id,
                'oldStatus' => $content['status'],
                'newStatus' => $status
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error changing content status: ' . $e->getMessage(), [
                'type' => $typeSlug,
                'id' => $id,
                'status' => $status
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при изменении статуса записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка доступных шаблонов для контента
     */
    public function getAvailableTemplates($contentId) {
        try {
            // Получаем информацию о контенте
            $content = $this->model->getById($contentId);
            
            if (!$content) {
                return [
                    'success' => false,
                    'message' => 'Контент не найден'
                ];
            }
            
            // Получаем активную тему
            $activeTheme = $this->theme->getActiveTheme();
            
            // Получаем список шаблонов темы
            $templates = $this->theme->getThemeTemplates($activeTheme);
            
            // Текущий шаблон контента
            $currentTemplate = $content['template_name'] ?? '';
            
            return [
                'success' => true,
                'data' => [
                    'templates' => $templates,
                    'current_template' => $currentTemplate
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting available templates: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ошибка при получении списка шаблонов: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Установка шаблона для контента
     */
    public function setTemplate($contentId, $data) {
        try {
            // Получаем информацию о контенте
            $content = $this->model->getById($contentId);
            
            if (!$content) {
                return [
                    'success' => false,
                    'message' => 'Контент не найден'
                ];
            }
            
            // Получаем название шаблона из данных
            $templateName = $data['template_name'] ?? '';
            
            // Если шаблон не указан, сбрасываем на значение по умолчанию
            if (empty($templateName)) {
                $templateName = null;
            } else {
                // Проверяем, существует ли шаблон
                $activeTheme = $this->theme->getActiveTheme();
                $templates = $this->theme->getThemeTemplates($activeTheme);
                
                if (!in_array($templateName, $templates)) {
                    return [
                        'success' => false,
                        'message' => 'Указанный шаблон не существует'
                    ];
                }
            }
            
            // Обновляем шаблон контента
            $result = $this->model->update($contentId, [
                'template_name' => $templateName
            ]);
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Не удалось обновить шаблон контента'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Шаблон контента успешно обновлен',
                'data' => [
                    'template_name' => $templateName
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error setting template: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ошибка при установке шаблона: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Метод для логирования в файл
     */
    private function logToFile($message) {
        $logFile = __DIR__ . '/../logs/controller.log';
        $timestamp = date('Y-m-d H:i:s');
        
        // Безопасное преобразование сообщения в строку с корректной кодировкой
        if (is_array($message)) {
            $message = 'Array: ' . json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif (is_object($message)) {
            $message = 'Object: ' . json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif (!is_string($message)) {
            $message = (string)$message;
        }
        
        // Проверка кодировки и конвертация при необходимости
        if (!mb_check_encoding($message, 'UTF-8')) {
            $message = mb_convert_encoding($message, 'UTF-8', 'auto');
            $message .= ' [конвертировано в UTF-8]';
        }
        
        file_put_contents($logFile, "[{$timestamp}] {$message}" . PHP_EOL, FILE_APPEND);
    }
} 
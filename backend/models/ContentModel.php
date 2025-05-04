<?php
namespace Models;

/**
 * Модель для работы с контентом
 */
class ContentModel {
    private $db;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = \API\Database::getInstance();
    }
    
    /**
     * Получить экземпляр объекта базы данных
     * 
     * @return \API\Database
     */
    public function getDb() {
        return $this->db;
    }
    
    /**
     * Получение всех записей контента по типу
     * 
     * @param int $contentTypeId ID типа контента
     * @param array $options Дополнительные параметры (limit, offset, order, filters)
     * @return array Массив записей контента
     */
    public function getAllByType($contentTypeId, $options = []) {
        $limit = isset($options['limit']) ? intval($options['limit']) : 20;
        $offset = isset($options['offset']) ? intval($options['offset']) : 0;
        $orderBy = isset($options['order']) ? $options['order'] : 'created_at DESC';
        $filters = isset($options['filters']) ? $options['filters'] : [];
        
        $query = "SELECT c.* FROM content c WHERE c.content_type_id = ?";
        $params = [$contentTypeId];
        
        // Добавляем условия фильтрации
        if (!empty($filters)) {
            if (isset($filters['status']) && $filters['status']) {
                $query .= " AND c.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['search']) && $filters['search']) {
                $query .= " AND (c.title LIKE ? OR c.slug LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (isset($filters['author_id']) && $filters['author_id']) {
                $query .= " AND c.author_id = ?";
                $params[] = $filters['author_id'];
            }
            
            if (isset($filters['date_from']) && $filters['date_from']) {
                $query .= " AND c.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && $filters['date_to']) {
                $query .= " AND c.created_at <= ?";
                $params[] = $filters['date_to'];
            }
        }
        
        // Добавляем сортировку и лимит
        $query .= " ORDER BY $orderBy LIMIT $offset, $limit";
        
        $content = $this->db->fetchAll($query, $params);
        
        // Получаем общее количество записей (для пагинации)
        $countQuery = "SELECT COUNT(*) as total FROM content c WHERE c.content_type_id = ?";
        $countParams = [$contentTypeId];
        
        // Добавляем те же условия фильтрации для подсчета
        if (!empty($filters)) {
            if (isset($filters['status']) && $filters['status']) {
                $countQuery .= " AND c.status = ?";
                $countParams[] = $filters['status'];
            }
            
            if (isset($filters['search']) && $filters['search']) {
                $countQuery .= " AND (c.title LIKE ? OR c.slug LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
            }
            
            if (isset($filters['author_id']) && $filters['author_id']) {
                $countQuery .= " AND c.author_id = ?";
                $countParams[] = $filters['author_id'];
            }
            
            if (isset($filters['date_from']) && $filters['date_from']) {
                $countQuery .= " AND c.created_at >= ?";
                $countParams[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && $filters['date_to']) {
                $countQuery .= " AND c.created_at <= ?";
                $countParams[] = $filters['date_to'];
            }
        }
        
        $totalResult = $this->db->fetch($countQuery, $countParams);
        $total = $totalResult ? $totalResult['total'] : 0;
        
        return [
            'items' => $content,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Получение записи контента по ID
     * 
     * @param int $id ID записи
     * @param bool $withFields Включать ли значения полей
     * @return array|bool Данные записи или false
     */
    public function getById($id, $withFields = true) {
        $content = $this->db->fetch(
            "SELECT c.* FROM content c WHERE c.id = ?", 
            [$id]
        );
        
        if (!$content) {
            return false;
        }
        
        if ($withFields) {
            $content['fields'] = $this->getFieldValues($id);
        }
        
        return $content;
    }
    
    /**
     * Получение записи контента по slug
     * 
     * @param string $slug Slug записи
     * @param int $contentTypeId ID типа контента
     * @param bool $withFields Включать ли значения полей
     * @return array|bool Данные записи или false
     */
    public function getBySlug($slug, $contentTypeId, $withFields = true) {
        $content = $this->db->fetch(
            "SELECT c.* FROM content c 
            WHERE c.slug = ? AND c.content_type_id = ?", 
            [$slug, $contentTypeId]
        );
        
        if (!$content) {
            return false;
        }
        
        if ($withFields) {
            $content['fields'] = $this->getFieldValues($content['id']);
        }
        
        return $content;
    }
    
    /**
     * Получение значений полей для записи контента
     * 
     * @param int $contentId ID записи контента
     * @return array Массив значений полей
     */
    public function getFieldValues($contentId) {
        $values = $this->db->fetchAll(
            "SELECT cfv.*, ctf.name, ctf.field_type 
            FROM content_field_values cfv
            JOIN content_type_fields ctf ON cfv.field_id = ctf.id
            WHERE cfv.content_id = ?
            ORDER BY ctf.order", 
            [$contentId]
        );
        
        $result = [];
        
        foreach ($values as $value) {
            $result[$value['name']] = [
                'id' => $value['id'],
                'field_id' => $value['field_id'],
                'field_type' => $value['field_type'],
                'value' => $value['value']
            ];
        }
        
        return $result;
    }
    
    /**
     * Создание новой записи контента
     * 
     * @param array $data Данные записи
     * @return array Результат операции
     */
    public function create($data) {
        try {
            // Преобразуем данные в массив, если это не массив
            if (!is_array($data)) {
                $data = is_object($data) ? (array)$data : [];
            }
            
            if (!isset($data['content_type_id']) || !(int)$data['content_type_id']) {
                return [
                    'success' => false,
                    'message' => 'Не указан тип контента (content_type_id)'
                ];
            }
            
            error_log("ContentModel.create() - Начало создания записи, тип контента: " . $data['content_type_id']);
            
            // Получаем список колонок в таблице content
            $tableColumns = $this->getContentTableColumns();
            error_log("ContentModel.create() - Получены колонки таблицы: " . json_encode($tableColumns));
            
            // Разделяем стандартные поля (для таблицы content) и пользовательские поля
            $standardFields = [];
            $customFields = [];
            
            foreach ($data as $key => $value) {
                // Пропускаем служебные поля
                if (strpos($key, '_') === 0) continue;
                
                if (in_array($key, $tableColumns)) {
                    $standardFields[$key] = $value;
                    error_log("ContentModel.create() - Добавлено стандартное поле: {$key}");
                } elseif ($key !== 'fields' && $key !== 'contentType') {
                    $customFields[$key] = $value;
                    error_log("ContentModel.create() - Добавлено пользовательское поле: {$key}");
                }
            }
            
            // Добавляем специальную обработку для полей description и test, если они существуют
            $specialFields = ['description', 'test'];
            foreach ($specialFields as $specialField) {
                if (isset($customFields[$specialField])) {
                    error_log("ContentModel.create() - Обнаружено специальное поле: {$specialField}");
                    
                    // Проверяем, существует ли такое поле в типах полей контента
                    $fieldExists = $this->db->fetch(
                        "SELECT id FROM content_type_fields 
                         WHERE content_type_id = ? AND name = ?",
                        [$data['content_type_id'], $specialField]
                    );
                    
                    if (!$fieldExists) {
                        error_log("ContentModel.create() - Поле {$specialField} не найдено в списке полей типа контента, создаём.");
                        // Создаем поле автоматически, если оно не существует
                        $fieldId = $this->db->insert('content_type_fields', [
                            'content_type_id' => $data['content_type_id'],
                            'name' => $specialField,
                            'label' => ucfirst($specialField), // Делаем первую букву заглавной для label
                            'field_type' => 'text',
                            'is_required' => 0,
                            'order' => 0
                        ]);
                        
                        if ($fieldId) {
                            error_log("ContentModel.create() - Создано поле типа контента {$specialField}, ID: {$fieldId}");
                        } else {
                            error_log("ContentModel.create() - Не удалось создать поле типа контента {$specialField}");
                        }
                    }
                }
            }
            
            // Проверка и автоматическое создание полей для всех пользовательских полей
            foreach ($customFields as $fieldName => $fieldValue) {
                // Проверяем, существует ли такое поле в типах полей контента
                $fieldExists = $this->db->fetch(
                    "SELECT id FROM content_type_fields 
                     WHERE content_type_id = ? AND name = ?",
                    [$data['content_type_id'], $fieldName]
                );
                
                if (!$fieldExists) {
                    error_log("ContentModel.create() - Поле {$fieldName} не найдено в списке полей типа контента, создаём автоматически");
                    // Создаем поле автоматически, если оно не существует
                    $fieldId = $this->db->insert('content_type_fields', [
                        'content_type_id' => $data['content_type_id'],
                        'name' => $fieldName,
                        'label' => ucfirst($fieldName), // Делаем первую букву заглавной для label
                        'field_type' => 'text',
                        'is_required' => 0,
                        'order' => 0
                    ]);
                    
                    if ($fieldId) {
                        error_log("ContentModel.create() - Создано поле типа контента {$fieldName}, ID: {$fieldId}");
                    } else {
                        error_log("ContentModel.create() - Не удалось создать поле типа контента {$fieldName}");
                    }
                }
            }
            
            // Оптимизированная проверка и создание полей - получаем все поля одним запросом
            if (!empty($customFields)) {
                // Получаем все существующие поля для данного типа контента
                $existingFields = $this->db->fetchAll(
                    "SELECT name FROM content_type_fields WHERE content_type_id = ?",
                    [$data['content_type_id']]
                );
                
                // Преобразуем результат в простой массив имен полей
                $existingFieldNames = array_column($existingFields, 'name');
                error_log("ContentModel.create() - Существующие поля: " . json_encode($existingFieldNames));
                
                // Определяем, какие поля нужно создать
                $fieldsToCreate = [];
                foreach ($customFields as $fieldName => $fieldValue) {
                    if (!in_array($fieldName, $existingFieldNames)) {
                        $fieldsToCreate[] = $fieldName;
                    }
                }
                
                // Создаем недостающие поля одним запросом, если они есть
                if (!empty($fieldsToCreate)) {
                    error_log("ContentModel.create() - Создаем " . count($fieldsToCreate) . " новых полей: " . json_encode($fieldsToCreate));
                    
                    // Формируем SQL для создания всех полей одной операцией
                    $values = [];
                    $placeholders = [];
                    
                    foreach ($fieldsToCreate as $fieldName) {
                        $placeholders[] = "(?, ?, ?, ?, ?, ?)";
                        $values[] = $data['content_type_id'];
                        $values[] = $fieldName;
                        $values[] = ucfirst($fieldName); // Label - первая буква заглавная
                        $values[] = 'text'; // Тип поля
                        $values[] = 0; // is_required
                        $values[] = 0; // order
                    }
                    
                    $sql = "INSERT INTO content_type_fields 
                            (content_type_id, name, label, field_type, is_required, `order`) 
                            VALUES " . implode(", ", $placeholders);
                    
                    try {
                        $stmt = $this->db->getConnection()->prepare($sql);
                        $result = $stmt->execute($values);
                        
                        if ($result) {
                            error_log("ContentModel.create() - Успешно создано " . count($fieldsToCreate) . " новых полей");
                        } else {
                            error_log("ContentModel.create() - Ошибка при создании полей: " . implode(', ', $stmt->errorInfo()));
                        }
                    } catch (\Exception $e) {
                        error_log("ContentModel.create() - Исключение при создании полей: " . $e->getMessage());
                    }
                }
            }
            
            error_log("ContentModel.create() - Стандартные поля: " . json_encode($standardFields));
            error_log("ContentModel.create() - Пользовательские поля: " . json_encode($customFields));
            
            // Устанавливаем базовые значения по умолчанию только для существующих колонок
            $defaultValues = [
                'status' => 'draft',
                'content' => json_encode(['html' => '<p>Содержимое записи</p>'], JSON_UNESCAPED_UNICODE), // Валидный JSON для поля content
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            foreach ($defaultValues as $column => $value) {
                if (in_array($column, $tableColumns) && !isset($standardFields[$column])) {
                    $standardFields[$column] = $value;
                }
            }
            
            // Всегда проверяем наличие поля content и убеждаемся, что оно содержит валидный JSON
            if (in_array('content', $tableColumns)) {
                // Проверяем, содержит ли поле content валидный JSON
                if (!isset($standardFields['content']) || 
                    $standardFields['content'] === null || 
                    !$this->isValidJson($standardFields['content'])) {
                    
                    // Создаем валидный JSON объект для поля content
                    $standardFields['content'] = json_encode(
                        ['html' => '<p>Содержимое записи</p>'], 
                        JSON_UNESCAPED_UNICODE
                    );
                    error_log("ContentModel.create() - Установлен валидный JSON для поля content: " . $standardFields['content']);
                }
            }
            
            // Если указан статус "published", устанавливаем дату публикации
            if (isset($standardFields['status']) && 
                $standardFields['status'] === 'published' && 
                in_array('published_at', $tableColumns) &&
                !isset($standardFields['published_at'])) {
                $standardFields['published_at'] = date('Y-m-d H:i:s');
            }
            
            // -------- СОЗДАНИЕ ЗАПИСИ БЕЗ ТРАНЗАКЦИИ --------
            // Добавляем основную запись контента
            error_log("ContentModel.create() - Добавление основной записи content");
            $contentId = $this->db->insert('content', $standardFields);
            
            if (!$contentId) {
                error_log("ContentModel.create() - Ошибка при вставке основной записи");
                return [
                    'success' => false,
                    'message' => 'Не удалось создать запись контента'
                ];
            }
            
            error_log("ContentModel.create() - Основная запись успешно создана, ID: " . $contentId);
            
            // Добавляем значения пользовательских полей
            if (!empty($customFields)) {
                error_log("ContentModel.create() - Добавление пользовательских полей");
                $result = $this->saveFieldValues($contentId, $customFields);
                
                if (!$result['success']) {
                    error_log("ContentModel.create() - Ошибка при сохранении полей: " . $result['message']);
                    
                    // Если произошла ошибка при сохранении полей, пытаемся удалить основную запись
                    $this->db->delete('content', 'id = ?', [$contentId]);
                    
                    return $result;
                }
                
                error_log("ContentModel.create() - Пользовательские поля успешно добавлены");
            }
            
            // Получаем созданную запись с полями
            $content = $this->getById($contentId);
            
            error_log("ContentModel.create() - Запись успешно создана: ID=" . $contentId);
            
            return [
                'success' => true,
                'data' => $content,
                'message' => 'Запись контента успешно создана'
            ];
        } catch (\Exception $e) {
            error_log("ContentModel.create() - Исключение: " . $e->getMessage());
            error_log("ContentModel.create() - Трассировка: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Ошибка при создании записи контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка колонок таблицы content
     * 
     * @return array Список колонок
     */
    private function getContentTableColumns() {
        static $cachedColumns = null;
        
        // Если колонки уже были получены ранее, возвращаем кэшированный результат
        if ($cachedColumns !== null) {
            return $cachedColumns;
        }
        
        // Список стандартных колонок таблицы content из структуры базы данных
        $defaultColumns = [
            'id', 'content_type_id', 'title', 'slug', 'content', 
            'image', 'author_id', 'status', 'published_at', 'created_at', 'updated_at'
        ];
        
        try {
            // Логируем начало процесса получения колонок
            $this->logToFile("Получение колонок таблицы content");
            
            // Прямой запрос о структуре таблицы
            $query = "SHOW COLUMNS FROM content";
            $this->logToFile("SQL запрос: {$query}");
            
            $stmt = $this->db->getConnection()->query($query);
            
            if ($stmt) {
                $columns = [];
                $columnData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($columnData as $column) {
                    if (isset($column['Field'])) {
                        $columns[] = $column['Field'];
                    }
                }
                
                if (!empty($columns)) {
                    $this->logToFile("Получены колонки из БД: " . implode(', ', $columns));
                    error_log("getContentTableColumns() - Получены колонки из БД: " . json_encode($columns));
                    $cachedColumns = $columns;
                    return $columns;
                }
            }
            
            // Альтернативный метод для получения колонок
            $stmt = $this->db->getConnection()->query("SELECT * FROM content LIMIT 0");
            if ($stmt) {
                $columnCount = $stmt->columnCount();
                $columns = [];
                
                for ($i = 0; $i < $columnCount; $i++) {
                    $meta = $stmt->getColumnMeta($i);
                    if (isset($meta['name'])) {
                        $columns[] = $meta['name'];
                    }
                }
                
                if (!empty($columns)) {
                    $this->logToFile("Получены колонки из метаданных: " . implode(', ', $columns));
                    error_log("getContentTableColumns() - Получены колонки из метаданных: " . json_encode($columns));
                    $cachedColumns = $columns;
                    return $columns;
                }
            }
            
            // Третий способ - через информационную схему
            $infoQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'content'";
            $this->logToFile("Запрос к INFORMATION_SCHEMA: {$infoQuery}");
            
            $stmt = $this->db->getConnection()->query($infoQuery);
            if ($stmt) {
                $columns = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if (isset($row['COLUMN_NAME'])) {
                        $columns[] = $row['COLUMN_NAME'];
                    }
                }
                
                if (!empty($columns)) {
                    $this->logToFile("Получены колонки из INFORMATION_SCHEMA: " . implode(', ', $columns));
                    error_log("getContentTableColumns() - Получены колонки из INFORMATION_SCHEMA: " . json_encode($columns));
                    $cachedColumns = $columns;
                    return $columns;
                }
            }
        } catch (\Exception $e) {
            $this->logToFile("ОШИБКА при получении колонок: " . $e->getMessage());
            error_log("getContentTableColumns() - Ошибка при получении колонок: " . $e->getMessage());
        }
        
        // В случае ошибки используем значения из структуры таблицы
        $this->logToFile("Использование колонок по умолчанию: " . implode(', ', $defaultColumns));
        error_log("getContentTableColumns() - Используем колонки по умолчанию: " . json_encode($defaultColumns));
        $cachedColumns = $defaultColumns;
        return $defaultColumns;
    }
    
    private function logToFile($message) {
        $logFile = __DIR__ . '/../logs/update.log';
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
    
    /**
     * Обновление записи контента
     * 
     * @param int $id ID записи
     * @param array $data Данные для обновления
     * @return array Результат операции
     */
    public function update($id, $data) {
        try {
            error_log("ContentModel.update() - Начало обновления записи, ID: {$id}");
            error_log("ContentModel.update() - Полученные данные: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Проверяем, существует ли запись
            $content = $this->getById($id, false);
            
            if (!$content) {
                error_log("ContentModel.update() - Запись не найдена, ID: {$id}");
                return [
                    'success' => false,
                    'message' => 'Запись контента не найдена'
                ];
            }
            
            // Получаем список стандартных полей таблицы
            $standardColumns = $this->getContentTableColumns();
            
            // Разделяем данные на стандартные поля и пользовательские
            $standardFields = [];
            $customFields = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $standardColumns)) {
                    $standardFields[$key] = $value;
                    error_log("ContentModel.update() - Стандартное поле: {$key} = " . (is_array($value) ? json_encode($value) : $value));
                } elseif ($key !== 'id' && $key !== 'content_type_id' && $key !== 'contentType' && $key !== 'fields') {
                    $customFields[$key] = $value;
                    error_log("ContentModel.update() - Пользовательское поле: {$key} = " . (is_array($value) ? json_encode($value) : $value));
                }
            }
            
            // ЯВНО добавим content_type_id из данных или из существующей записи
            if (isset($data['content_type_id'])) {
                $contentTypeId = $data['content_type_id'];
            } else {
                $contentTypeId = $content['content_type_id'];
            }
            
            // Устанавливаем метку времени обновления
            $standardFields['updated_at'] = date('Y-m-d H:i:s');
            
            // -------- ПРЯМОЕ ОБНОВЛЕНИЕ БЕЗ ПРОМЕЖУТОЧНЫХ СЛОЕВ --------
            // Для гарантированного обновления title и slug используем прямой SQL-запрос
            $hasDirectUpdate = false;
            
            // Проверяем наличие важных полей для прямого обновления
            if (isset($standardFields['title']) || isset($standardFields['slug'])) {
                error_log("ContentModel.update() - Прямое обновление важных полей");
                
                $pdo = $this->db->getConnection();
                $directFields = [];
                $directValues = [];
                
                // Добавляем поля для обновления
                foreach ($standardFields as $field => $value) {
                    $directFields[] = "`{$field}` = ?";
                    $directValues[] = $value;
                    error_log("ContentModel.update() - Прямое обновление поля {$field} = {$value}");
                }
                
                // Если есть поля для обновления
                if (!empty($directFields)) {
                    $directSql = "UPDATE content SET " . implode(", ", $directFields) . " WHERE id = ?";
                    $directValues[] = $id;
                    
                    $stmt = $pdo->prepare($directSql);
                    $result = $stmt->execute($directValues);
                    
                    $rowCount = $stmt->rowCount();
                    error_log("ContentModel.update() - Результат прямого обновления: " . ($result ? "успешно" : "ошибка") . ", строк затронуто: {$rowCount}");
                    
                    $hasDirectUpdate = $result;
                }
            }
            
            // Если прямое обновление не выполнялось, используем стандартный метод
            if (!$hasDirectUpdate && !empty($standardFields)) {
                error_log("ContentModel.update() - Обновление через стандартный метод");
                $updateResult = $this->db->update('content', $standardFields, 'id = ?', [$id]);
                error_log("ContentModel.update() - Результат стандартного обновления: " . ($updateResult ? "успешно" : "ошибка"));
                
                if (!$updateResult) {
                    error_log("ContentModel.update() - Не удалось обновить основные поля записи");
                }
            }
            
            // Обновляем пользовательские поля
            $fieldsUpdated = true;
            $fieldsError = '';
            
            if (!empty($customFields)) {
                error_log("ContentModel.update() - Обновление пользовательских полей");
                $fieldsResult = $this->saveFieldValues($id, $customFields);
                
                if (!$fieldsResult['success']) {
                    $fieldsUpdated = false;
                    $fieldsError = $fieldsResult['message'];
                    error_log("ContentModel.update() - Ошибка при сохранении полей: " . $fieldsError);
                } else {
                    error_log("ContentModel.update() - Поля успешно обновлены");
                }
            }
            
            // Получаем обновленную запись
            $updatedContent = $this->getById($id);
            
            // Формируем ответ
            return [
                'success' => true,
                'data' => $updatedContent,
                'message' => 'Запись успешно обновлена' . (!$fieldsUpdated ? ", но с ошибками в полях: {$fieldsError}" : '')
            ];
        } catch (\Exception $e) {
            error_log("ContentModel.update() - Исключение: " . $e->getMessage());
            error_log("ContentModel.update() - Трассировка: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление записи контента
     * 
     * @param int $id ID записи
     * @return array Результат операции
     */
    public function delete($id) {
        try {
            // Проверяем, существует ли запись
            $content = $this->getById($id, false);
            
            if (!$content) {
                return [
                    'success' => false,
                    'message' => 'Запись контента не найдена'
                ];
            }
            
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Удаляем значения полей
            $this->db->delete('content_field_values', 'content_id = ?', [$id]);
            
            // Удаляем запись
            $success = $this->db->delete('content', 'id = ?', [$id]);
            
            if (!$success) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'Не удалось удалить запись контента'
                ];
            }
            
            // Завершаем транзакцию
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Запись контента успешно удалена'
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => 'Ошибка при удалении записи контента: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Сохранение значений полей
     * 
     * @param int $contentId ID записи контента
     * @param array $fieldValues Значения полей
     * @return array Результат операции
     */
    private function saveFieldValues($contentId, $fieldValues) {
        try {
            // Базовое логирование
            error_log("saveFieldValues() - Начало сохранения полей для content_id: " . $contentId);
            error_log("saveFieldValues() - Данные полей: " . json_encode($fieldValues, JSON_UNESCAPED_UNICODE));
            
            // Проверяем, существует ли запись контента
            $contentType = $this->db->fetch(
                "SELECT content_type_id FROM content WHERE id = ?", 
                [$contentId]
            );
            
            if (!$contentType) {
                error_log("saveFieldValues() - Ошибка: запись контента не найдена");
                return [
                    'success' => false,
                    'message' => 'Запись контента не найдена'
                ];
            }
            
            $contentTypeId = $contentType['content_type_id'];
            
            // Получаем список доступных полей для данного типа контента
            $availableFields = $this->db->fetchAll(
                "SELECT id, name, field_type, is_required FROM content_type_fields 
                WHERE content_type_id = ?", 
                [$contentTypeId]
            );
            
            if (empty($availableFields)) {
                error_log("saveFieldValues() - Для типа контента нет настроенных полей");
                return [
                    'success' => true,
                    'message' => 'Для типа контента нет полей'
                ];
            }
            
            // Преобразуем список полей в ассоциативный массив для быстрого поиска
            $fieldsMap = [];
            foreach ($availableFields as $field) {
                $fieldsMap[$field['name']] = $field;
            }
            
            // Массивы для учета обработанных полей
            $updatedFields = [];
            $errorFields = [];
            
            // Устанавливаем текущее время для обновлений
            $timestamp = date('Y-m-d H:i:s');
            
            // Обновляем основную запись контента
            $this->db->update('content', ['updated_at' => $timestamp], 'id = ?', [$contentId]);
            
            // Получаем все существующие значения полей для данной записи
            $existingValues = $this->db->fetchAll(
                "SELECT field_id, id FROM content_field_values WHERE content_id = ?",
                [$contentId]
            );
            
            // Создаем карту существующих значений для быстрого поиска
            $existingValueMap = [];
            foreach ($existingValues as $value) {
                $existingValueMap[$value['field_id']] = $value['id'];
            }
            
            // Массивы для пакетных операций
            $fieldsToUpdate = [];  // [id => [value, updated_at]]
            $fieldsToInsert = [];  // [[content_id, field_id, value, created_at, updated_at]]
            
            // Подготавливаем данные для обновления или вставки
            foreach ($fieldValues as $fieldName => $fieldValue) {
                try {
                    // Проверяем, существует ли такое поле в типе контента
                    if (!isset($fieldsMap[$fieldName])) {
                        error_log("saveFieldValues() - Поле '{$fieldName}' не существует в типе контента");
                        $errorFields[] = $fieldName;
                        continue;
                    }
                    
                    $field = $fieldsMap[$fieldName];
                    $fieldId = $field['id'];
                    
                    // Получаем значение (может быть как простым значением, так и массивом с ключом 'value')
                    $value = is_array($fieldValue) && isset($fieldValue['value']) ? $fieldValue['value'] : $fieldValue;
                    
                    // Проверяем обязательные поля
                    if ($field['is_required'] && ($value === null || $value === '')) {
                        error_log("saveFieldValues() - Обязательное поле '{$fieldName}' имеет пустое значение");
                        $errorFields[] = $fieldName;
                        continue;
                    }
                    
                    // Преобразуем значение в строку для хранения в БД
                    $dbValue = null;
                    if (is_array($value) || is_object($value)) {
                        $dbValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } else {
                        $dbValue = (string)$value;
                    }
                    
                    // Проверяем, существует ли уже значение для этого поля
                    if (isset($existingValueMap[$fieldId])) {
                        // Будем обновлять существующее значение
                        $valueId = $existingValueMap[$fieldId];
                        $fieldsToUpdate[$valueId] = ['value' => $dbValue, 'updated_at' => $timestamp];
                        $updatedFields[] = $fieldName;
                    } else {
                        // Будем добавлять новое значение
                        $fieldsToInsert[] = [
                            'content_id' => $contentId,
                            'field_id' => $fieldId,
                            'value' => $dbValue,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp
                        ];
                        $updatedFields[] = $fieldName;
                    }
                } catch (\Exception $e) {
                    error_log("saveFieldValues() - Исключение при обработке поля '{$fieldName}': " . $e->getMessage());
                    $errorFields[] = $fieldName;
                }
            }
            
            // Выполняем массовое обновление
            if (!empty($fieldsToUpdate)) {
                error_log("saveFieldValues() - Обновление " . count($fieldsToUpdate) . " существующих значений");
                
                $pdo = $this->db->getConnection();
                $success = true;
                
                foreach ($fieldsToUpdate as $valueId => $updateData) {
                    $updateStmt = $pdo->prepare(
                        "UPDATE content_field_values SET value = ?, updated_at = ? WHERE id = ?"
                    );
                    $updateResult = $updateStmt->execute([$updateData['value'], $updateData['updated_at'], $valueId]);
                    
                    if (!$updateResult) {
                        error_log("saveFieldValues() - Ошибка при обновлении значения ID: {$valueId}");
                        $success = false;
                    }
                }
                
                if (!$success) {
                    error_log("saveFieldValues() - Были ошибки при выполнении обновлений");
                }
            }
            
            // Выполняем массовую вставку
            if (!empty($fieldsToInsert)) {
                error_log("saveFieldValues() - Добавление " . count($fieldsToInsert) . " новых значений");
                
                $pdo = $this->db->getConnection();
                $insertStmt = $pdo->prepare(
                    "INSERT INTO content_field_values 
                    (content_id, field_id, value, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?)"
                );
                
                $success = true;
                foreach ($fieldsToInsert as $insertData) {
                    $insertResult = $insertStmt->execute([
                        $insertData['content_id'],
                        $insertData['field_id'],
                        $insertData['value'],
                        $insertData['created_at'],
                        $insertData['updated_at']
                    ]);
                    
                    if (!$insertResult) {
                        error_log("saveFieldValues() - Ошибка при добавлении значения для поля ID: {$insertData['field_id']}");
                        $success = false;
                    }
                }
                
                if (!$success) {
                    error_log("saveFieldValues() - Были ошибки при выполнении вставок");
                }
            }
            
            // Завершающее обновление основной записи
            $this->db->update('content', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$contentId]);
            
            // Формируем результат операции
            if (!empty($errorFields)) {
                error_log("saveFieldValues() - Завершено с ошибками: " . implode(', ', $errorFields));
                return [
                    'success' => false,
                    'message' => 'Ошибка при сохранении полей: ' . implode(', ', $errorFields),
                    'updated_fields' => $updatedFields,
                    'error_fields' => $errorFields
                ];
            }
            
            error_log("saveFieldValues() - Успешно сохранены поля: " . implode(', ', $updatedFields));
            return [
                'success' => true,
                'message' => 'Значения полей успешно сохранены',
                'updated_fields' => $updatedFields
            ];
        } catch (\Exception $e) {
            error_log("saveFieldValues() - Критическая ошибка: " . $e->getMessage());
            error_log("saveFieldValues() - Стек вызовов: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Ошибка при сохранении значений полей: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Изменение статуса записи контента
     * 
     * @param int $id ID записи
     * @param string $status Новый статус
     * @return array Результат операции
     */
    public function changeStatus($id, $status) {
        try {
            // Проверяем, существует ли запись
            $content = $this->getById($id, false);
            
            if (!$content) {
                return [
                    'success' => false,
                    'message' => 'Запись контента не найдена'
                ];
            }
            
            $data = ['status' => $status];
            
            // Устанавливаем дату публикации для статуса "published"
            if ($status === 'published' && 
                (!isset($content['published_at']) || $content['published_at'] === null)) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            
            $success = $this->db->update('content', $data, 'id = ?', [$id]);
            
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Не удалось изменить статус записи'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Статус записи успешно изменен'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при изменении статуса записи: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверка уникальности slug для записи контента
     * 
     * @param string $slug Проверяемый slug
     * @param int $contentTypeId ID типа контента
     * @param int|null $excludeId ID записи, которую нужно исключить из проверки
     * @return bool Уникален ли slug
     */
    public function isSlugUnique($slug, $contentTypeId, $excludeId = null) {
        $params = [$slug, $contentTypeId];
        $query = "SELECT COUNT(*) as count FROM content 
                  WHERE slug = ? AND content_type_id = ?";
        
        if ($excludeId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($query, $params);
        
        return $result['count'] === 0;
    }
    
    /**
     * Генерация уникального slug на основе заголовка
     * 
     * @param string $title Заголовок
     * @param int $contentTypeId ID типа контента
     * @param int|null $excludeId ID записи для исключения
     * @return string Уникальный slug
     */
    public function generateUniqueSlug($title, $contentTypeId, $excludeId = null) {
        // Транслитерация и приведение к нижнему регистру
        $slug = $this->slugify($title);
        
        // Проверка уникальности
        if ($this->isSlugUnique($slug, $contentTypeId, $excludeId)) {
            return $slug;
        }
        
        // Добавляем числовой суффикс
        $i = 1;
        do {
            $newSlug = $slug . '-' . $i;
            $i++;
        } while (!$this->isSlugUnique($newSlug, $contentTypeId, $excludeId));
        
        return $newSlug;
    }
    
    /**
     * Транслитерация строки для получения slug
     * 
     * @param string $text Исходный текст
     * @return string Slug
     */
    private function slugify($text) {
        // Транслитерация кириллицы
        $text = $this->transliterate($text);
        
        // Приведение к нижнему регистру
        $text = mb_strtolower($text, 'UTF-8');
        
        // Замена пробелов на дефисы
        $text = preg_replace('/\s+/', '-', $text);
        
        // Удаление всех символов, кроме букв, цифр и дефисов
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        
        // Удаление повторяющихся дефисов
        $text = preg_replace('/-+/', '-', $text);
        
        // Удаление дефисов в начале и конце
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Транслитерация кириллицы
     * 
     * @param string $text Исходный текст
     * @return string Транслитерированный текст
     */
    private function transliterate($text) {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];
        
        return strtr($text, $converter);
    }
    
    /**
     * Проверка, является ли строка валидным JSON
     * 
     * @param string $json Строка для проверки
     * @return bool Является ли строка валидным JSON
     */
    private function isValidJson($json) {
        if (!is_string($json)) {
            return false;
        }
        
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
} 
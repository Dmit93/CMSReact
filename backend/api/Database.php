<?php

namespace API;

class Database {
    protected $connection = null;
    private static $instance = null;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Получение экземпляра класса (singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Установка соединения с базой данных
     */
    private function connect() {
        try {
            // Загрузка конфигурации
            $config = include __DIR__ . '/../config/database.php';
            
            // Строка подключения
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            
            // Опции PDO
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Создание соединения
            $this->connection = new \PDO($dsn, $config['username'], $config['password'], $options);
            
            // Устанавливаем кодировку
            $this->connection->exec("SET NAMES utf8mb4");
            $this->connection->exec("SET CHARACTER SET utf8mb4");
            
            return true;
        } catch (\PDOException $e) {
            // Логирование ошибки
            error_log("Database connection error: " . $e->getMessage());
            
            // В продакшене не стоит показывать детали ошибки
            return false;
        }
    }
    
    /**
     * Получение соединения с базой данных
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Выполнение запроса
     */
    public function query($sql, $params = []) {
        if (empty($params)) {
            $params = [];
        }
        
        try {
            $stmt = $this->connection->prepare($sql);
            
            // Обработка параметров перед выполнением запроса
            if (!empty($params)) {
                // Преобразование объектов и массивов в строки
                foreach ($params as $key => $value) {
                    // Проверка на объект класса Request
                    if (is_object($value) && get_class($value) === 'API\\Request') {
                        error_log("Database.query() - Обнаружен объект Request в параметрах, заменяем на NULL");
                        $params[$key] = null;
                    }
                    // Преобразование других объектов в строки JSON
                    else if (is_object($value)) {
                        error_log("Database.query() - Преобразование объекта " . get_class($value) . " в JSON");
                        $params[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    // Преобразование массивов в строки JSON
                    else if (is_array($value)) {
                        $params[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                        error_log("Database.query() - Параметр $key преобразован из массива в JSON: " . $params[$key]);
                    }
                }
            }
            
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log("Database.query() - Ошибка выполнения запроса: " . $e->getMessage());
            error_log("Database.query() - SQL: " . $sql);
            error_log("Database.query() - Параметры: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }
    
    /**
     * Получение одной записи
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Получение всех записей
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Подготовка запроса (prepare)
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Вставка записи
     */
    public function insert($table, $data) {
        try {
            error_log("Database.insert() - таблица: {$table}, данные: " . json_encode($data));
            
            // Фильтруем служебные поля
            $filteredData = array_filter($data, function($key) {
                return strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            
            // Столбцы и заполнители для запроса
            $columns = [];
            $placeholders = [];
            $values = [];
            
            // Подготовка данных для запроса
            foreach ($filteredData as $column => $value) {
                $columns[] = "`{$column}`";
                $placeholders[] = "?";
                
                // Преобразуем массивы и объекты в JSON
                if (is_array($value) || is_object($value)) {
                    $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                    $values[] = $jsonValue;
                    error_log("Database.insert() - поле {$column} преобразовано из объекта/массива в JSON: " . $jsonValue);
                } else {
                    $values[] = $value;
                }
            }
            
            // Формируем SQL-запрос
            $columnsStr = implode(', ', $columns);
            $placeholdersStr = implode(', ', $placeholders);
            $sql = "INSERT INTO `{$table}` ({$columnsStr}) VALUES ({$placeholdersStr})";
            
            error_log("Database.insert() - SQL: {$sql}");
            error_log("Database.insert() - Значения: " . json_encode($values));
            
            // Выполняем запрос и возвращаем ID вставленной записи
            $stmt = $this->query($sql, $values);
            $newId = $this->connection->lastInsertId();
            
            error_log("Database.insert() - Успешная вставка, новый ID: {$newId}");
            
            return $newId;
        } catch (\Exception $e) {
            error_log("Database.insert() - Ошибка: " . $e->getMessage());
            error_log("Database.insert() - Трейс: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Обновление записей в таблице
     */
    public function update($table, $data, $where, $whereParams = []) {
        // Проверка на пустой массив данных
        if (empty($data)) {
            error_log("Database.update() - Пустой массив данных для таблицы {$table}");
            return false;
        }
        
        // Специальный метод для таблицы content
        if ($table === 'content' && is_numeric($whereParams[0])) {
            return $this->updateContentTable($data, $whereParams[0]);
        }
        
        try {
            // Фильтруем служебные поля
            $filteredData = array_filter($data, function($key) {
                return strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            
            // Формируем SQL-запрос
            $setClauses = [];
            $values = [];
            
            foreach ($filteredData as $column => $value) {
                $setClauses[] = "`{$column}` = ?";
                
                // Преобразуем массивы и объекты в JSON
                if (is_array($value) || is_object($value)) {
                    $values[] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $values[] = $value;
                }
            }
            
            if (empty($setClauses)) {
                error_log("Database.update() - Нет полей для обновления в таблице {$table}");
                return false;
            }
            
            $setClauseStr = implode(', ', $setClauses);
            $sql = "UPDATE `{$table}` SET {$setClauseStr} WHERE {$where}";
            
            // Объединяем массивы значений с параметрами WHERE
            $params = array_merge($values, $whereParams);
            
            error_log("Database.update() - SQL: {$sql}");
            error_log("Database.update() - Параметры: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("Database.update() - Ошибка: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Специальный метод для обновления записи в content
     */
    private function updateContentTable($data, $id) {
        try {
            error_log("Database.updateContentTable() - Начало обновления ID: {$id}, данные: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Начинаем транзакцию
            $this->connection->beginTransaction();
            
            // Прямое обновление всех стандартных полей одним запросом
            $filteredData = array_filter($data, function($key) {
                return strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            
            // Гарантируем наличие метки времени
            if (!isset($filteredData['updated_at'])) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            // Проверяем наличие полей для обновления
            if (!empty($filteredData)) {
                error_log("Database.updateContentTable() - Прямое обновление полей: " . implode(', ', array_keys($filteredData)));
                
                $updateFields = [];
                $updateValues = [];
                
                foreach ($filteredData as $field => $value) {
                    $updateFields[] = "`{$field}` = ?";
                    
                    // Преобразуем массивы и объекты в JSON
                    if (is_array($value) || is_object($value)) {
                        $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                        $updateValues[] = $jsonValue;
                        error_log("Database.updateContentTable() - Поле {$field} преобразовано в JSON");
                    } else {
                        $updateValues[] = $value;
                        error_log("Database.updateContentTable() - Обновление поля {$field} = {$value}");
                    }
                }
                
                // Формируем SQL запрос
                $updateSql = "UPDATE `content` SET " . implode(", ", $updateFields) . " WHERE `id` = ?";
                $updateValues[] = $id;
                
                // Выполняем запрос
                $stmt = $this->connection->prepare($updateSql);
                $updateResult = $stmt->execute($updateValues);
                
                $rowCount = $stmt->rowCount();
                error_log("Database.updateContentTable() - Результат обновления, затронуто строк: {$rowCount}");
                
                if (!$updateResult) {
                    $this->connection->rollBack();
                    error_log("Database.updateContentTable() - Ошибка при обновлении: " . implode(', ', $stmt->errorInfo()));
                    return false;
                }
                
                // Если запись не обновилась (rowCount=0), проверяем ее существование
                if ($rowCount === 0) {
                    $checkSql = "SELECT EXISTS(SELECT 1 FROM `content` WHERE `id` = ?) as record_exists";
                    $checkStmt = $this->connection->prepare($checkSql);
                    $checkStmt->execute([$id]);
                    $checkResult = $checkStmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if (!isset($checkResult['record_exists']) || $checkResult['record_exists'] != 1) {
                        // Запись не существует
                        $this->connection->rollBack();
                        error_log("Database.updateContentTable() - Запись не найдена, ID: {$id}");
                        return false;
                    }
                    
                    // Запись существует, но не было изменений
                    error_log("Database.updateContentTable() - Запись существует, но не было изменений");
                    
                    // Принудительно обновляем временную метку
                    $forceSql = "UPDATE `content` SET `updated_at` = ? WHERE `id` = ?";
                    $forceTimestamp = date('Y-m-d H:i:s', time() + 1);
                    $forceStmt = $this->connection->prepare($forceSql);
                    $forceStmt->execute([$forceTimestamp, $id]);
                }
            } else {
                // Если нет данных для обновления, принудительно обновляем только временную метку
                error_log("Database.updateContentTable() - Нет данных для обновления, обновляем только временную метку");
                
                $forceSql = "UPDATE `content` SET `updated_at` = ? WHERE `id` = ?";
                $forceTimestamp = date('Y-m-d H:i:s');
                $forceStmt = $this->connection->prepare($forceSql);
                $forceResult = $forceStmt->execute([$forceTimestamp, $id]);
                
                $forceRowCount = $forceStmt->rowCount();
                error_log("Database.updateContentTable() - Результат обновления метки времени, затронуто строк: {$forceRowCount}");
                
                if (!$forceResult || $forceRowCount === 0) {
                    $this->connection->rollBack();
                    error_log("Database.updateContentTable() - Запись не найдена при обновлении метки времени");
                    return false;
                }
            }
            
            // Фиксируем изменения
            $this->connection->commit();
            error_log("Database.updateContentTable() - Транзакция успешно завершена");
            return true;
            
        } catch (\Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            error_log("Database.updateContentTable() - Исключение: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление записей
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Начать транзакцию
     */
    public function beginTransaction() {
        try {
            // Проверяем, не активна ли уже транзакция
            if ($this->connection->inTransaction()) {
                error_log("Database.beginTransaction() - Транзакция уже активна");
                return true; // Возвращаем true, так как транзакция активна
            }
            
            // Пытаемся начать транзакцию
            $result = $this->connection->beginTransaction();
            error_log("Database.beginTransaction() - Результат: " . ($result ? "Успешно" : "Ошибка"));
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Database.beginTransaction() - Ошибка: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Применить изменения транзакции
     */
    public function commit() {
        try {
            // Проверяем, активна ли транзакция
            if (!$this->connection->inTransaction()) {
                error_log("Database.commit() - Нет активной транзакции");
                return false;
            }
            
            // Применяем изменения
            $result = $this->connection->commit();
            error_log("Database.commit() - Результат: " . ($result ? "Успешно" : "Ошибка"));
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Database.commit() - Ошибка: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Откатить изменения транзакции
     */
    public function rollback() {
        try {
            // Проверяем, активна ли транзакция
            if (!$this->connection->inTransaction()) {
                error_log("Database.rollback() - Нет активной транзакции");
                return false;
            }
            
            // Откатываем изменения
            $result = $this->connection->rollBack();
            error_log("Database.rollback() - Результат: " . ($result ? "Успешно" : "Ошибка"));
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Database.rollback() - Ошибка: " . $e->getMessage());
            return false;
        }
    }
}

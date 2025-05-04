<?php
namespace Core;

use API\Database;

/**
 * Базовый класс миграций для модулей
 */
abstract class ModuleMigration {
    /**
     * @var Database Экземпляр базы данных
     */
    protected $db;
    
    /**
     * @var string Имя модуля
     */
    protected $moduleName;
    
    /**
     * Конструктор
     * 
     * @param string $moduleName Имя модуля
     */
    public function __construct($moduleName) {
        $this->db = Database::getInstance();
        $this->moduleName = $moduleName;
    }
    
    /**
     * Применить миграцию
     * 
     * @return bool Успешность применения миграции
     */
    abstract public function up();
    
    /**
     * Откатить миграцию
     * 
     * @return bool Успешность отката миграции
     */
    abstract public function down();
    
    /**
     * Выполнить SQL-запрос
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return bool Успешность выполнения запроса
     */
    protected function execute($sql, $params = []) {
        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\Exception $e) {
            error_log("Migration error in module {$this->moduleName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверить существование таблицы
     * 
     * @param string $tableName Имя таблицы
     * @return bool Существует ли таблица
     */
    protected function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->db->fetch($sql, [$tableName]);
        return !empty($result);
    }
    
    /**
     * Проверить существование столбца в таблице
     * 
     * @param string $tableName Имя таблицы
     * @param string $columnName Имя столбца
     * @return bool Существует ли столбец
     */
    protected function columnExists($tableName, $columnName) {
        if (!$this->tableExists($tableName)) {
            return false;
        }
        
        $sql = "SHOW COLUMNS FROM `{$tableName}` LIKE ?";
        $result = $this->db->fetch($sql, [$columnName]);
        
        return !empty($result);
    }
} 
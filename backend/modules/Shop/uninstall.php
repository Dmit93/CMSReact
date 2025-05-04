<?php
namespace Modules\Shop;

use API\Database;
use Core\Logger;

/**
 * Класс удаления модуля Shop
 */
class Uninstall {
    /**
     * @var Database Экземпляр базы данных
     */
    protected $db;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Метод удаления модуля
     * 
     * @return bool Результат удаления
     */
    public function uninstall() {
        try {
            // Удаляем данные из таблиц
            $this->cleanupData();
            
            // Логируем успешное удаление
            Logger::getInstance()->info('Shop module successfully uninstalled');
            
            return true;
        } catch (\Exception $e) {
            Logger::getInstance()->error('Error during shop module uninstallation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Очистка данных модуля из таблиц
     * Замечание: сами таблицы удаляются через миграции, здесь только удаление данных
     */
    private function cleanupData() {
        // Удаляем настройки модуля
        $this->db->query("DELETE FROM settings WHERE group_name = 'shop'");
        
        // Удаляем права доступа, связанные с модулем
        $this->db->query("DELETE FROM permissions WHERE name LIKE 'shop.%'");
        
        // Удаляем роли, связанные с модулем (если необходимо)
        // $this->db->query("DELETE FROM roles WHERE name = 'shop_manager'");
        
        // Очищаем кэш, связанный с модулем
        $cacheDir = ROOT_DIR . '/cache/shop';
        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }
    }
    
    /**
     * Рекурсивное удаление директории с файлами
     * 
     * @param string $dir Путь к директории
     * @return bool Результат удаления
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            
            $path = $dir . '/' . $object;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
} 
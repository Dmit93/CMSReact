<?php
namespace Controllers;

use Core\ModuleManager;
use API\Database;

/**
 * Контроллер для управления модулями через API
 */
class ModulesController {
    /**
     * @var ModuleManager Экземпляр менеджера модулей
     */
    private $moduleManager;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->moduleManager = ModuleManager::getInstance();
    }
    
    /**
     * Получение списка всех модулей
     * 
     * @return array JSON-ответ со списком модулей
     */
    public function getAll() {
        try {
            // Получаем модули из БД
            $query = "SELECT * FROM modules ORDER BY name ASC";
            $installedModules = Database::getInstance()->fetchAll($query);
            
            // Получаем доступные модули из директорий
            $availableModules = $this->moduleManager->getAvailableModules();
            
            // Фильтруем доступные модули, исключая те, что уже установлены
            $installedSlugs = array_column($installedModules, 'slug');
            $availableForInstall = [];
            
            foreach ($availableModules as $moduleId => $moduleInfo) {
                if (!in_array($moduleId, $installedSlugs)) {
                    $availableForInstall[] = [
                        'id' => $moduleId,
                        'name' => $moduleInfo['name'],
                        'description' => $moduleInfo['description'] ?? '',
                        'version' => $moduleInfo['version'] ?? '1.0.0'
                    ];
                }
            }
            
            return [
                'installed' => $installedModules,
                'available' => $availableForInstall
            ];
        } catch (\Exception $e) {
            return ['error' => 'Ошибка при получении списка модулей: ' . $e->getMessage()];
        }
    }
    
    /**
     * Получение информации о конкретном модуле
     * 
     * @param string $slug Slug модуля
     * @return array JSON-ответ с информацией о модуле
     */
    public function getById($slug) {
        try {
            $query = "SELECT * FROM modules WHERE slug = ?";
            $module = Database::getInstance()->fetch($query, [$slug]);
            
            if (!$module) {
                return ['error' => 'Модуль не найден'];
            }
            
            return $module;
        } catch (\Exception $e) {
            return ['error' => 'Ошибка при получении информации о модуле: ' . $e->getMessage()];
        }
    }
    
    /**
     * Установка модуля
     * 
     * @param string $slug Slug модуля
     * @return array JSON-ответ с результатом установки
     */
    public function install($slug) {
        try {
            $result = $this->moduleManager->installModule($slug);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при установке модуля: ' . $e->getMessage()];
        }
    }
    
    /**
     * Активация модуля
     * 
     * @param string $slug Slug модуля
     * @return array JSON-ответ с результатом активации
     */
    public function activate($slug) {
        try {
            $result = $this->moduleManager->activateModule($slug);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при активации модуля: ' . $e->getMessage()];
        }
    }
    
    /**
     * Деактивация модуля
     * 
     * @param string $slug Slug модуля
     * @return array JSON-ответ с результатом деактивации
     */
    public function deactivate($slug) {
        try {
            $result = $this->moduleManager->deactivateModule($slug);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при деактивации модуля: ' . $e->getMessage()];
        }
    }
    
    /**
     * Удаление модуля
     * 
     * @param string $slug Slug модуля
     * @return array JSON-ответ с результатом удаления
     */
    public function uninstall($slug) {
        try {
            $result = $this->moduleManager->uninstallModule($slug);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при удалении модуля: ' . $e->getMessage()];
        }
    }
} 
<?php
namespace Core;

use API\Database;

/**
 * Менеджер модулей - класс для управления модулями системы
 */
class ModuleManager {
    private static $instance = null;
    private $modules = [];
    private $enabledModules = [];
    private $modulePaths = [];
    private $db;
    
    /**
     * Получение singleton экземпляра класса
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Конструктор
     */
    private function __construct() {
        // Получаем экземпляр базы данных
        $this->db = Database::getInstance();
        
        // Устанавливаем директорию для модулей по умолчанию
        $this->addModulePath(dirname(__DIR__) . '/modules');
    }
    
    /**
     * Добавление пути для поиска модулей
     * 
     * @param string $path Путь до директории с модулями
     * @return bool Успешность добавления пути
     */
    public function addModulePath($path) {
        if (!is_dir($path)) {
            return false;
        }
        
        $this->modulePaths[] = $path;
        return true;
    }
    
    /**
     * Сканирование доступных модулей
     * 
     * @return array Массив доступных модулей
     */
    public function scanModules() {
        $this->modules = [];
        
        foreach ($this->modulePaths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $dirs = scandir($path);
            
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                $moduleDir = $path . '/' . $dir;
                
                if (is_dir($moduleDir) && file_exists($moduleDir . '/module.php')) {
                    $moduleInfo = $this->getModuleInfo($moduleDir);
                    
                    if ($moduleInfo) {
                        $this->modules[$moduleInfo['id']] = $moduleInfo;
                    }
                }
            }
        }
        
        return $this->modules;
    }
    
    /**
     * Получение информации о модуле
     * 
     * @param string $moduleDir Путь к директории модуля
     * @return array|bool Информация о модуле или false в случае ошибки
     */
    private function getModuleInfo($moduleDir) {
        $moduleFile = $moduleDir . '/module.php';
        
        if (!file_exists($moduleFile)) {
            return false;
        }
        
        $moduleInfo = include $moduleFile;
        
        if (!is_array($moduleInfo) || !isset($moduleInfo['id']) || !isset($moduleInfo['name'])) {
            return false;
        }
        
        $moduleInfo['path'] = $moduleDir;
        return $moduleInfo;
    }
    
    /**
     * Загрузка всех активных модулей
     * 
     * @return array Массив загруженных модулей
     */
    public function loadModules() {
        // Получаем список активных модулей из базы данных
        $activeModules = $this->getActiveModules();
        $loaded = [];
        
        foreach ($activeModules as $moduleId) {
            if ($this->loadModule($moduleId)) {
                $loaded[] = $moduleId;
            }
        }
        
        return $loaded;
    }
    
    /**
     * Загрузка конкретного модуля
     * 
     * @param string $moduleId Идентификатор модуля
     * @return bool Успешность загрузки
     */
    public function loadModule($moduleId) {
        if (!isset($this->modules[$moduleId])) {
            $this->scanModules();
            
            if (!isset($this->modules[$moduleId])) {
                return false;
            }
        }
        
        $moduleInfo = $this->modules[$moduleId];
        $initFile = $moduleInfo['path'] . '/init.php';
        
        if (file_exists($initFile)) {
            include_once $initFile;
            
            $className = "\\Modules\\{$moduleId}\\Module";
            
            if (class_exists($className)) {
                $moduleInstance = new $className();
                
                if (method_exists($moduleInstance, 'init')) {
                    $moduleInstance->init();
                }
                
                $this->enabledModules[$moduleId] = $moduleInstance;
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получение списка активных модулей из базы данных
     * 
     * @return array Массив идентификаторов активных модулей
     */
    private function getActiveModules() {
        $activeModules = [];
        
        try {
            $query = "SELECT slug FROM modules WHERE status = 'active'";
            $result = $this->db->fetchAll($query);
            
            if ($result) {
                foreach ($result as $row) {
                    $activeModules[] = $row['slug'];
                }
            }
        } catch (\Exception $e) {
            error_log("Error fetching active modules: " . $e->getMessage());
        }
        
        // Если нет активных модулей в БД, вернем базовые модули
        if (empty($activeModules)) {
            // Базовые модули, которые должны быть активны всегда
            $activeModules = ['core', 'admin'];
        }
        
        return $activeModules;
    }
    
    /**
     * Получение экземпляра загруженного модуля
     * 
     * @param string $moduleId Идентификатор модуля
     * @return object|null Экземпляр модуля или null
     */
    public function getModule($moduleId) {
        return isset($this->enabledModules[$moduleId]) ? $this->enabledModules[$moduleId] : null;
    }
    
    /**
     * Получение списка всех доступных модулей
     * 
     * @return array Массив информации о модулях
     */
    public function getAvailableModules() {
        if (empty($this->modules)) {
            $this->scanModules();
        }
        
        return $this->modules;
    }
    
    /**
     * Получение списка загруженных модулей
     * 
     * @return array Массив загруженных модулей
     */
    public function getEnabledModules() {
        return $this->enabledModules;
    }
    
    /**
     * Получение списка установленных модулей из базы данных
     * 
     * @return array Массив установленных модулей
     */
    public function getInstalledModules() {
        try {
            $query = "SELECT * FROM modules ORDER BY name ASC";
            $modules = $this->db->fetchAll($query);
            return $modules ?: [];
        } catch (\Exception $e) {
            error_log("Error fetching installed modules: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Установка модуля
     * 
     * @param string|array $moduleId Идентификатор модуля
     * @return array Результат установки ['success' => bool, 'message' => string]
     */
    public function installModule($moduleId) {
        // Если получен массив (из маршрутизатора), извлекаем slug
        if (is_array($moduleId)) {
            // Предполагаем, что последний элемент массива - это slug
            $moduleId = end($moduleId);
        }
        
        // Проверяем, что модуль существует
        if (!isset($this->modules[$moduleId])) {
            $this->scanModules();
            
            if (!isset($this->modules[$moduleId])) {
                return [
                    'success' => false,
                    'message' => "Модуль с ID {$moduleId} не найден"
                ];
            }
        }
        
        $moduleInfo = $this->modules[$moduleId];
        
        // Проверяем, установлен ли уже модуль
        $query = "SELECT id FROM modules WHERE slug = ?";
        $existingModule = $this->db->fetch($query, [$moduleId]);
        
        if ($existingModule) {
            return [
                'success' => false, 
                'message' => "Модуль {$moduleInfo['name']} уже установлен"
            ];
        }
        
        // Проверяем зависимости
        if (isset($moduleInfo['dependencies']) && is_array($moduleInfo['dependencies'])) {
            foreach ($moduleInfo['dependencies'] as $dependency) {
                $depQuery = "SELECT id FROM modules WHERE slug = ? AND status = 'active'";
                $dep = $this->db->fetch($depQuery, [$dependency]);
                
                if (!$dep) {
                    return [
                        'success' => false,
                        'message' => "Для установки требуется активный модуль: {$dependency}"
                    ];
                }
            }
        }
        
        // Запускаем миграции
        $migrationsPath = $moduleInfo['path'] . '/migrations';
        if (is_dir($migrationsPath)) {
            $migrations = scandir($migrationsPath);
            
            foreach ($migrations as $migration) {
                if ($migration === '.' || $migration === '..' || pathinfo($migration, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }
                
                // Подключаем и выполняем миграцию
                require_once $migrationsPath . '/' . $migration;
                
                $className = 'Modules\\' . ucfirst($moduleId) . '\\Migrations\\' . pathinfo($migration, PATHINFO_FILENAME);
                
                if (class_exists($className)) {
                    $migrationInstance = new $className($moduleId);
                    $result = $migrationInstance->up();
                    
                    if (!$result) {
                        return [
                            'success' => false,
                            'message' => "Ошибка выполнения миграции: {$migration}"
                        ];
                    }
                }
            }
        }
        
        // Записываем информацию о модуле в БД
        $now = date('Y-m-d H:i:s');
        $insertQuery = "INSERT INTO modules (name, slug, description, status, version, config, created_at, updated_at, installed_at) 
                        VALUES (?, ?, ?, 'inactive', ?, ?, ?, ?, ?)";
        
        $params = [
            $moduleInfo['name'],
            $moduleId,
            $moduleInfo['description'] ?? '',
            $moduleInfo['version'] ?? '1.0.0',
            json_encode($moduleInfo['config'] ?? []),
            $now,
            $now,
            $now
        ];
        
        try {
            $this->db->query($insertQuery, $params);
            
            // Запускаем метод установки в модуле, если он есть
            $installFile = $moduleInfo['path'] . '/install.php';
            if (file_exists($installFile)) {
                include_once $installFile;
                
                $installClassName = "\\Modules\\{$moduleId}\\Install";
                if (class_exists($installClassName)) {
                    $installer = new $installClassName();
                    if (method_exists($installer, 'install')) {
                        $installer->install();
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Модуль {$moduleInfo['name']} успешно установлен"
            ];
        } catch (\Exception $e) {
            error_log("Error installing module {$moduleId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка установки модуля: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Активация модуля
     * 
     * @param string|array $moduleId Идентификатор модуля
     * @return array Результат активации ['success' => bool, 'message' => string]
     */
    public function activateModule($moduleId) {
        // Если получен массив (из маршрутизатора), извлекаем slug
        if (is_array($moduleId)) {
            // Предполагаем, что последний элемент массива - это slug
            $moduleId = end($moduleId);
        }
        
        // Проверяем, что модуль установлен
        $query = "SELECT id, name FROM modules WHERE slug = ?";
        $module = $this->db->fetch($query, [$moduleId]);
        
        if (!$module) {
            return [
                'success' => false,
                'message' => "Модуль с ID {$moduleId} не установлен"
            ];
        }
        
        // Проверяем, что модуль не активирован
        $statusQuery = "SELECT status FROM modules WHERE slug = ?";
        $status = $this->db->fetch($statusQuery, [$moduleId]);
        
        if ($status && $status['status'] === 'active') {
            return [
                'success' => true,
                'message' => "Модуль {$module['name']} уже активирован"
            ];
        }
        
        // Активируем модуль
        $updateQuery = "UPDATE modules SET status = 'active', updated_at = ? WHERE slug = ?";
        $now = date('Y-m-d H:i:s');
        
        try {
            $this->db->query($updateQuery, [$now, $moduleId]);
            
            // Запускаем хук активации
            if (isset($this->modules[$moduleId])) {
                $moduleInfo = $this->modules[$moduleId];
                $activateFile = $moduleInfo['path'] . '/activate.php';
                
                if (file_exists($activateFile)) {
                    include_once $activateFile;
                    
                    $activateClassName = "\\Modules\\{$moduleId}\\Activate";
                    if (class_exists($activateClassName)) {
                        $activator = new $activateClassName();
                        if (method_exists($activator, 'activate')) {
                            $activator->activate();
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Модуль {$module['name']} успешно активирован"
            ];
        } catch (\Exception $e) {
            error_log("Error activating module {$moduleId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка активации модуля: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Деактивация модуля
     * 
     * @param string|array $moduleId Идентификатор модуля
     * @return array Результат деактивации ['success' => bool, 'message' => string]
     */
    public function deactivateModule($moduleId) {
        // Если получен массив (из маршрутизатора), извлекаем slug
        if (is_array($moduleId)) {
            // Предполагаем, что последний элемент массива - это slug
            $moduleId = end($moduleId);
        }
        
        // Проверяем, что модуль установлен
        $query = "SELECT id, name FROM modules WHERE slug = ?";
        $module = $this->db->fetch($query, [$moduleId]);
        
        if (!$module) {
            return [
                'success' => false,
                'message' => "Модуль с ID {$moduleId} не установлен"
            ];
        }
        
        // Проверяем, что модуль активирован
        $statusQuery = "SELECT status FROM modules WHERE slug = ?";
        $status = $this->db->fetch($statusQuery, [$moduleId]);
        
        if ($status && $status['status'] === 'inactive') {
            return [
                'success' => true,
                'message' => "Модуль {$module['name']} уже деактивирован"
            ];
        }
        
        // Проверяем, зависят ли другие модули от этого
        $dependencyCheck = "SELECT name FROM modules WHERE JSON_SEARCH(dependencies, 'one', ?) IS NOT NULL AND status = 'active'";
        $dependencies = $this->db->fetchAll($dependencyCheck, [$moduleId]);
        
        if (!empty($dependencies)) {
            $dependencyNames = [];
            foreach ($dependencies as $dep) {
                $dependencyNames[] = $dep['name'];
            }
            
            return [
                'success' => false,
                'message' => "Невозможно деактивировать модуль, так как от него зависят: " . implode(', ', $dependencyNames)
            ];
        }
        
        // Деактивируем модуль
        $updateQuery = "UPDATE modules SET status = 'inactive', updated_at = ? WHERE slug = ?";
        $now = date('Y-m-d H:i:s');
        
        try {
            $this->db->query($updateQuery, [$now, $moduleId]);
            
            // Запускаем хук деактивации
            if (isset($this->modules[$moduleId])) {
                $moduleInfo = $this->modules[$moduleId];
                $deactivateFile = $moduleInfo['path'] . '/deactivate.php';
                
                if (file_exists($deactivateFile)) {
                    include_once $deactivateFile;
                    
                    $deactivateClassName = "\\Modules\\{$moduleId}\\Deactivate";
                    if (class_exists($deactivateClassName)) {
                        $deactivator = new $deactivateClassName();
                        if (method_exists($deactivator, 'deactivate')) {
                            $deactivator->deactivate();
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Модуль {$module['name']} успешно деактивирован"
            ];
        } catch (\Exception $e) {
            error_log("Error deactivating module {$moduleId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка деактивации модуля: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление модуля
     * 
     * @param string|array $moduleId Идентификатор модуля
     * @return array Результат удаления ['success' => bool, 'message' => string]
     */
    public function uninstallModule($moduleId) {
        // Если получен массив (из маршрутизатора), извлекаем slug
        if (is_array($moduleId)) {
            // Предполагаем, что последний элемент массива - это slug
            $moduleId = end($moduleId);
        }
        
        // Проверяем, что модуль установлен
        $query = "SELECT id, name FROM modules WHERE slug = ?";
        $module = $this->db->fetch($query, [$moduleId]);
        
        if (!$module) {
            return [
                'success' => false,
                'message' => "Модуль с ID {$moduleId} не установлен"
            ];
        }
        
        // Сначала деактивируем модуль, если он активен
        if ($module['status'] === 'active') {
            $deactivateResult = $this->deactivateModule($moduleId);
            
            if (!$deactivateResult['success']) {
                return $deactivateResult;
            }
        }
        
        // Проверяем, есть ли модуль в системе
        if (!isset($this->modules[$moduleId])) {
            $this->scanModules();
        }
        
        // Запускаем миграции для отката
        if (isset($this->modules[$moduleId])) {
            $moduleInfo = $this->modules[$moduleId];
            $migrationsPath = $moduleInfo['path'] . '/migrations';
            
            if (is_dir($migrationsPath)) {
                $migrations = scandir($migrationsPath);
                $migrations = array_filter($migrations, function($file) {
                    return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php';
                });
                
                // Сортируем миграции в обратном порядке для правильного отката
                rsort($migrations);
                
                foreach ($migrations as $migration) {
                    require_once $migrationsPath . '/' . $migration;
                    
                    $className = 'Modules\\' . ucfirst($moduleId) . '\\Migrations\\' . pathinfo($migration, PATHINFO_FILENAME);
                    
                    if (class_exists($className)) {
                        $migrationInstance = new $className($moduleId);
                        $result = $migrationInstance->down();
                        
                        if (!$result) {
                            return [
                                'success' => false,
                                'message' => "Ошибка отката миграции: {$migration}"
                            ];
                        }
                    }
                }
            }
            
            // Запускаем метод удаления в модуле, если он есть
            $uninstallFile = $moduleInfo['path'] . '/uninstall.php';
            if (file_exists($uninstallFile)) {
                include_once $uninstallFile;
                
                $uninstallClassName = "\\Modules\\{$moduleId}\\Uninstall";
                if (class_exists($uninstallClassName)) {
                    $uninstaller = new $uninstallClassName();
                    if (method_exists($uninstaller, 'uninstall')) {
                        $uninstaller->uninstall();
                    }
                }
            }
        }
        
        // Удаляем запись о модуле из БД
        $deleteQuery = "DELETE FROM modules WHERE slug = ?";
        
        try {
            $this->db->query($deleteQuery, [$moduleId]);
            
            return [
                'success' => true,
                'message' => "Модуль {$module['name']} успешно удален"
            ];
        } catch (\Exception $e) {
            error_log("Error uninstalling module {$moduleId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка удаления модуля: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка всех модулей с их статусами (активные/неактивные)
     * 
     * @return array Массив со списком всех модулей и их статусами
     */
    public function getAllModulesWithStatus() {
        try {
            // Получаем список установленных модулей
            $query = "SELECT * FROM modules ORDER BY name ASC";
            $modules = $this->db->fetchAll($query);
            
            foreach ($modules as &$module) {
                // Явно устанавливаем статус 'active' или 'inactive' в зависимости от поля status в БД
                if (isset($module['status']) && $module['status'] === 'active') {
                    $module['status'] = 'active';
                } else {
                    $module['status'] = 'inactive';
                }
                
                // Преобразуем пустые строки в null
                foreach ($module as $key => $value) {
                    if ($value === "") {
                        $module[$key] = null;
                    }
                }
                
                // Преобразование конфига из JSON, если он в таком формате
                if (!empty($module['config']) && is_string($module['config'])) {
                    $config = json_decode($module['config'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $module['config'] = $config;
                    } else {
                        $module['config'] = [];
                    }
                } else {
                    $module['config'] = [];
                }
            }
            
            // Для диагностики - выводим список модулей со статусами
            error_log("Список модулей со статусами: " . json_encode($modules));
            
            return $modules;
        } catch (\Exception $e) {
            error_log("Ошибка при получении списка модулей со статусами: " . $e->getMessage());
            return [];
        }
    }
} 
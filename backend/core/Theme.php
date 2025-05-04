<?php
namespace Core;

class Theme {
    private $themesDir;
    private $activeTheme;
    private $previewTheme; // Тема для предварительного просмотра
    private $db;
    
    /**
     * Инициализация менеджера тем
     */
    public function __construct() {
        $this->themesDir = __DIR__ . '/../themes/';
        $this->db = \API\Database::getInstance();
        $this->loadActiveTheme();
    }
    
    /**
     * Загружает активную тему из настроек
     */
    private function loadActiveTheme() {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE name = 'active_theme'");
        $stmt->execute();
        
        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->activeTheme = $row['value'];
        } else {
            // Если настройка не найдена, используем тему по умолчанию
            $this->activeTheme = 'default';
            // Создаем настройку
            $stmt = $this->db->prepare("INSERT INTO settings (name, value) VALUES ('active_theme', 'default')");
            $stmt->execute();
        }
    }
    
    /**
     * Возвращает имя активной темы
     */
    public function getActiveTheme() {
        return $this->previewTheme ?? $this->activeTheme;
    }
    
    /**
     * Устанавливает активную тему
     */
    public function setActiveTheme($themeName) {
        if (!$this->themeExists($themeName)) {
            return [
                'success' => false,
                'message' => "Тема {$themeName} не существует"
            ];
        }
        
        $stmt = $this->db->prepare("UPDATE settings SET value = ? WHERE name = 'active_theme'");
        $result = $stmt->execute([$themeName]);
        
        if ($result) {
            $this->activeTheme = $themeName;
            return [
                'success' => true,
                'message' => "Тема {$themeName} успешно активирована"
            ];
        }
        
        return [
            'success' => false,
            'message' => "Не удалось активировать тему {$themeName}"
        ];
    }
    
    /**
     * Устанавливает тему для предварительного просмотра
     */
    public function setThemeForPreview($themeName) {
        if (!$this->themeExists($themeName)) {
            return false;
        }
        
        $this->previewTheme = $themeName;
        return true;
    }
    
    /**
     * Сбрасывает тему предварительного просмотра
     */
    public function resetPreviewTheme() {
        $this->previewTheme = null;
    }
    
    /**
     * Проверяет, находимся ли мы в режиме предварительного просмотра
     */
    public function isPreviewMode() {
        return $this->previewTheme !== null;
    }
    
    /**
     * Проверяет существование темы
     */
    public function themeExists($themeName) {
        return is_dir($this->themesDir . $themeName);
    }
    
    /**
     * Возвращает список доступных тем
     */
    public function getAvailableThemes() {
        $themes = [];
        $dirs = scandir($this->themesDir);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            if (is_dir($this->themesDir . $dir)) {
                // Проверяем наличие конфига темы
                if (file_exists($this->themesDir . $dir . '/theme.json')) {
                    $config = json_decode(file_get_contents($this->themesDir . $dir . '/theme.json'), true);
                    $themes[] = [
                        'name' => $dir,
                        'title' => $config['title'] ?? $dir,
                        'description' => $config['description'] ?? '',
                        'version' => $config['version'] ?? '1.0',
                        'author' => $config['author'] ?? '',
                        'active' => ($dir === $this->activeTheme)
                    ];
                } else {
                    // Если конфиг отсутствует, добавляем базовую информацию
                    $themes[] = [
                        'name' => $dir,
                        'title' => $dir,
                        'description' => '',
                        'version' => '1.0',
                        'author' => '',
                        'active' => ($dir === $this->activeTheme)
                    ];
                }
            }
        }
        
        return $themes;
    }
    
    /**
     * Возвращает список доступных шаблонов в теме
     */
    public function getThemeTemplates($themeName = null) {
        $theme = $themeName ?? $this->getActiveTheme();
        $templatesDir = $this->themesDir . $theme . '/templates';
        $templates = [];
        
        if (is_dir($templatesDir)) {
            $files = scandir($templatesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $templateName = pathinfo($file, PATHINFO_FILENAME);
                    $templates[] = $templateName;
                }
            }
        }
        
        return $templates;
    }
    
    /**
     * Отображает шаблон темы
     */
    public function render($template, $data = []) {
        $theme = $this->getActiveTheme();
        $templatePath = $this->themesDir . $theme . '/templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            // Если шаблон не найден в активной теме, проверяем тему по умолчанию
            $templatePath = $this->themesDir . 'default/templates/' . $template . '.php';
            
            if (!file_exists($templatePath)) {
                throw new \Exception("Шаблон {$template} не найден");
            }
        }
        
        // Извлекаем переменные из массива данных
        extract($data);
        
        // Включаем буферизацию вывода
        ob_start();
        
        // Подключаем шаблон
        include $templatePath;
        
        // Получаем содержимое буфера и очищаем его
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Возвращает URL темы
     */
    public function getThemeUrl() {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $baseUrl .= dirname($_SERVER['SCRIPT_NAME']);
        return $baseUrl . '/themes/' . $this->getActiveTheme();
    }
} 
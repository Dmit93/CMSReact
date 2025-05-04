<?php
namespace Controllers;

use Core\Theme;

class ThemeController {
    private $theme;
    
    public function __construct() {
        $this->theme = new Theme();
    }
    
    /**
     * Получить список доступных тем
     */
    public function getList() {
        try {
            $themes = $this->theme->getAvailableThemes();
            
            return [
                'success' => true,
                'data' => $themes
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Активировать тему
     */
    public function activate($data) {
        try {
            $themeName = $data['theme_name'] ?? null;
            
            if (!$themeName) {
                return [
                    'success' => false,
                    'message' => 'Не указано имя темы'
                ];
            }
            
            $result = $this->theme->setActiveTheme($themeName);
            
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить информацию о теме
     */
    public function getThemeInfo($themeName) {
        try {
            if (!$this->theme->themeExists($themeName)) {
                return [
                    'success' => false,
                    'message' => "Тема {$themeName} не существует"
                ];
            }
            
            $themePath = dirname(__DIR__) . '/themes/' . $themeName;
            $configPath = $themePath . '/theme.json';
            
            if (!file_exists($configPath)) {
                return [
                    'success' => true,
                    'data' => [
                        'name' => $themeName,
                        'title' => $themeName,
                        'description' => 'Информация о теме отсутствует',
                        'version' => '1.0',
                        'author' => '',
                        'active' => ($themeName === $this->theme->getActiveTheme()),
                        'screenshots' => [],
                        'templates' => $this->getTemplatesList($themePath),
                    ]
                ];
            }
            
            $config = json_decode(file_get_contents($configPath), true);
            
            // Получаем список скриншотов
            $screenshots = [];
            $screenshotsDir = $themePath . '/screenshots';
            if (is_dir($screenshotsDir)) {
                $files = scandir($screenshotsDir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                        $screenshots[] = 'themes/' . $themeName . '/screenshots/' . $file;
                    }
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'name' => $themeName,
                    'title' => $config['title'] ?? $themeName,
                    'description' => $config['description'] ?? '',
                    'version' => $config['version'] ?? '1.0',
                    'author' => $config['author'] ?? '',
                    'active' => ($themeName === $this->theme->getActiveTheme()),
                    'screenshots' => $screenshots,
                    'templates' => $this->getTemplatesList($themePath),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить список шаблонов темы
     */
    private function getTemplatesList($themePath) {
        $templates = [];
        $templatesDir = $themePath . '/templates';
        
        if (is_dir($templatesDir)) {
            $files = scandir($templatesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $templates[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        
        return $templates;
    }
} 
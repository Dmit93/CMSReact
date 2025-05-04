<?php
namespace Controllers;

use Core\Theme;
use Models\ContentModel;
use Models\ContentTypeModel;

class SiteController {
    private $theme;
    private $contentModel;
    private $contentTypeModel;
    
    public function __construct() {
        $this->theme = new Theme();
        $this->contentModel = new ContentModel();
        $this->contentTypeModel = new ContentTypeModel();
    }
    
    /**
     * Главная страница сайта
     */
    public function index() {
        // Получаем последние записи для отображения на главной
        $latestContent = $this->contentModel->getList([
            'status' => 'published',
            'limit' => 10,
            'order' => 'created_at DESC'
        ]);
        
        // Получаем типы контента для меню
        $contentTypes = $this->contentTypeModel->getList();
        
        // Получаем настройки сайта
        $siteTitle = $this->getSetting('site_title', 'CMS Site');
        $siteDescription = $this->getSetting('site_description', 'A custom CMS website');
        
        // Отображаем шаблон
        return $this->theme->render('index', [
            'latestContent' => $latestContent,
            'contentTypes' => $contentTypes,
            'siteTitle' => $siteTitle,
            'siteDescription' => $siteDescription,
            'themeUrl' => $this->theme->getThemeUrl()
        ]);
    }
    
    /**
     * Страница отдельного типа контента
     */
    public function contentType($typeId) {
        // Получаем информацию о типе контента
        $contentType = $this->contentTypeModel->getById($typeId);
        
        if (!$contentType) {
            return $this->notFound();
        }
        
        // Получаем записи этого типа
        $content = $this->contentModel->getList([
            'content_type_id' => $typeId,
            'status' => 'published',
            'limit' => 20,
            'order' => 'created_at DESC'
        ]);
        
        // Получаем типы контента для меню
        $contentTypes = $this->contentTypeModel->getList();
        
        // Отображаем шаблон
        return $this->theme->render('content_type', [
            'contentType' => $contentType,
            'content' => $content,
            'contentTypes' => $contentTypes,
            'themeUrl' => $this->theme->getThemeUrl()
        ]);
    }
    
    /**
     * Страница отдельной записи
     */
    public function contentItem($typeId, $itemId) {
        // Получаем информацию о типе контента
        $contentType = $this->contentTypeModel->getById($typeId);
        
        if (!$contentType) {
            return $this->notFound();
        }
        
        // Получаем запись
        $item = $this->contentModel->getById($itemId);
        
        if (!$item || $item['content_type_id'] != $typeId || $item['status'] !== 'published') {
            return $this->notFound();
        }
        
        // Получаем типы контента для меню
        $contentTypes = $this->contentTypeModel->getList();
        
        // Получаем соседние записи для навигации
        $prevItem = $this->contentModel->getPrevious($itemId, $typeId);
        $nextItem = $this->contentModel->getNext($itemId, $typeId);
        
        // Определяем какой шаблон использовать
        $templateName = 'content_item'; // Шаблон по умолчанию
        
        // Если у контента указан кастомный шаблон, используем его
        if (!empty($item['template_name'])) {
            // Проверяем существование шаблона в активной теме
            $templates = $this->theme->getThemeTemplates();
            if (in_array($item['template_name'], $templates)) {
                $templateName = $item['template_name'];
            }
        }
        
        // Отображаем шаблон
        return $this->theme->render($templateName, [
            'contentType' => $contentType,
            'item' => $item,
            'contentTypes' => $contentTypes,
            'prevItem' => $prevItem,
            'nextItem' => $nextItem,
            'themeUrl' => $this->theme->getThemeUrl()
        ]);
    }
    
    /**
     * Страница по слагу
     */
    public function bySlug($slug) {
        // Ищем запись по слагу
        $item = $this->contentModel->getBySlug($slug);
        
        if (!$item || $item['status'] !== 'published') {
            return $this->notFound();
        }
        
        // Получаем тип контента
        $contentType = $this->contentTypeModel->getById($item['content_type_id']);
        
        // Получаем типы контента для меню
        $contentTypes = $this->contentTypeModel->getList();
        
        // Получаем соседние записи для навигации
        $prevItem = $this->contentModel->getPrevious($item['id'], $item['content_type_id']);
        $nextItem = $this->contentModel->getNext($item['id'], $item['content_type_id']);
        
        // Определяем какой шаблон использовать
        $templateName = 'content_item'; // Шаблон по умолчанию
        
        // Если у контента указан кастомный шаблон, используем его
        if (!empty($item['template_name'])) {
            // Проверяем существование шаблона в активной теме
            $templates = $this->theme->getThemeTemplates();
            if (in_array($item['template_name'], $templates)) {
                $templateName = $item['template_name'];
            }
        }
        
        // Отображаем шаблон
        return $this->theme->render($templateName, [
            'contentType' => $contentType,
            'item' => $item,
            'contentTypes' => $contentTypes,
            'prevItem' => $prevItem,
            'nextItem' => $nextItem,
            'themeUrl' => $this->theme->getThemeUrl()
        ]);
    }
    
    /**
     * Страница 404
     */
    public function notFound() {
        // Получаем типы контента для меню
        $contentTypes = $this->contentTypeModel->getList();
        
        // Устанавливаем HTTP код 404
        http_response_code(404);
        
        // Отображаем шаблон
        return $this->theme->render('404', [
            'contentTypes' => $contentTypes,
            'themeUrl' => $this->theme->getThemeUrl()
        ]);
    }
    
    /**
     * Получает настройку из базы данных
     */
    private function getSetting($name, $default = '') {
        $db = \Core\Database::getInstance();
        $stmt = $db->prepare("SELECT value FROM settings WHERE name = ?");
        $stmt->execute([$name]);
        
        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }
        
        return $default;
    }
} 
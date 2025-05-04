<?php
namespace Controllers;

use Core\Theme;
use Models\ContentModel;
use Models\ContentTypeModel;

/**
 * Контроллер для предварительного просмотра тем
 */
class PreviewController {
    private $theme;
    private $contentModel;
    private $contentTypeModel;
    private $siteController;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->theme = new Theme();
        $this->contentModel = new ContentModel();
        $this->contentTypeModel = new ContentTypeModel();
        $this->siteController = new SiteController();
    }
    
    /**
     * Предпросмотр главной страницы сайта в указанной теме
     */
    public function previewIndex($themeName) {
        // Устанавливаем тему для предпросмотра
        if (!$this->theme->themeExists($themeName)) {
            return $this->siteController->notFound();
        }
        
        $this->theme->setThemeForPreview($themeName);
        
        // Делегируем отображение контроллеру сайта
        return $this->siteController->index();
    }
    
    /**
     * Предпросмотр страницы типа контента в указанной теме
     */
    public function previewContentType($themeName, $typeId) {
        // Устанавливаем тему для предпросмотра
        if (!$this->theme->themeExists($themeName)) {
            return $this->siteController->notFound();
        }
        
        $this->theme->setThemeForPreview($themeName);
        
        // Делегируем отображение контроллеру сайта
        return $this->siteController->contentType($typeId);
    }
    
    /**
     * Предпросмотр страницы отдельной записи в указанной теме
     */
    public function previewContentItem($themeName, $typeId, $itemId) {
        // Устанавливаем тему для предпросмотра
        if (!$this->theme->themeExists($themeName)) {
            return $this->siteController->notFound();
        }
        
        $this->theme->setThemeForPreview($themeName);
        
        // Делегируем отображение контроллеру сайта
        return $this->siteController->contentItem($typeId, $itemId);
    }
    
    /**
     * Предпросмотр страницы с кастомным шаблоном
     */
    public function previewWithTemplate($themeName, $itemId, $templateName) {
        // Устанавливаем тему для предпросмотра
        if (!$this->theme->themeExists($themeName)) {
            return $this->siteController->notFound();
        }
        
        // Получаем запись контента
        $item = $this->contentModel->getById($itemId);
        if (!$item) {
            return $this->siteController->notFound();
        }
        
        // Получаем тип контента
        $contentType = $this->contentTypeModel->getById($item['content_type_id']);
        if (!$contentType) {
            return $this->siteController->notFound();
        }
        
        // Проверяем, существует ли такой шаблон в теме
        $templates = $this->theme->getThemeTemplates($themeName);
        if (!in_array($templateName, $templates)) {
            return $this->siteController->notFound();
        }
        
        // Устанавливаем тему для предпросмотра
        $this->theme->setThemeForPreview($themeName);
        
        // Получаем типы контента для меню
        $contentTypes = $this->contentTypeModel->getList();
        
        // Отображаем шаблон через механизм тем
        return $this->theme->render($templateName, [
            'contentType' => $contentType,
            'item' => $item,
            'contentTypes' => $contentTypes,
            'themeUrl' => $this->theme->getThemeUrl()
        ]);
    }
} 
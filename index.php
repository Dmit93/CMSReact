<?php
/**
 * Главный файл CMS, отображающий фронтенд сайта с выбранной темой
 */

// Определяем корневой каталог приложения
define('ROOT_DIR', __DIR__);

// Загрузка необходимых файлов
require_once ROOT_DIR . '/backend/config/config.php';
require_once ROOT_DIR . '/backend/api/Database.php';
require_once ROOT_DIR . '/backend/helpers/functions.php';
require_once ROOT_DIR . '/backend/api/ThemeRenderer.php';

// Создаем экземпляр рендерера темы
$themeRenderer = new \API\ThemeRenderer();

// Если запрошен предпросмотр определенной темы
if (isset($_GET['preview_theme']) && !empty($_GET['preview_theme'])) {
    $previewTheme = basename($_GET['preview_theme']);
    $themeRenderer->setPreviewTheme($previewTheme);
}

// Если запрошен конкретный шаблон
$template = 'index';
if (isset($_GET['template']) && !empty($_GET['template'])) {
    $template = basename($_GET['template']);
}

// Получаем последние записи блога для отображения на главной
$latestContent = $themeRenderer->getContent('post', [
    'limit' => 6,
    'order' => 'created_at DESC'
]);

// Добавляем данные для шаблона
$themeRenderer->addData('latestContent', $latestContent);

// Рендерим шаблон
$themeRenderer->render($template); 
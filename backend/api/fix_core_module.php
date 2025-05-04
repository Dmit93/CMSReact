<?php
/**
 * Скрипт для исправления модуля Core
 * Создает файлы Module.php и module.php с правильным содержимым
 */

// Определяем пути к файлам
$moduleDir = dirname(__DIR__) . '/modules/Core';
$moduleClassPath = $moduleDir . '/Module.php';
$moduleInfoPath = $moduleDir . '/module.php';

// Содержимое для Module.php (класс модуля)
$moduleClassContent = '<?php
namespace Modules\Core;

use Core\BaseModule;
use Core\Logger;

/**
 * Основной класс базового модуля Core
 */
class Module extends BaseModule {
    /**
     * Инициализация модуля
     */
    public function init() {
        // Загружаем информацию о модуле
        $moduleInfo = include __DIR__ . \'/module.php\';
        $this->setInfo($moduleInfo);
        
        // Регистрируем обработчики событий
        $this->registerEventHandlers();
        
        // Логируем загрузку
        Logger::getInstance()->info(\'Core module initialized\', [
            \'version\' => $this->moduleInfo[\'version\']
        ]);
    }
    
    /**
     * Регистрация обработчиков событий
     */
    private function registerEventHandlers() {
        // Регистрируем базовые хуки
        $this->registerEventHandler(\'core.init\', [$this, \'onCoreInit\']);
        $this->registerEventHandler(\'core.shutdown\', [$this, \'onCoreShutdown\']);
        
        // Хуки для API
        $this->registerEventHandler(\'api.before_register_routes\', [$this, \'onApiBeforeRegisterRoutes\']);
    }
    
    /**
     * Обработчик события инициализации ядра
     */
    public function onCoreInit($params) {
        // Логика при инициализации ядра
        return $params;
    }
    
    /**
     * Обработчик события завершения работы ядра
     */
    public function onCoreShutdown($params) {
        // Логика при завершении работы ядра
        return $params;
    }
    
    /**
     * Обработчик события перед регистрацией маршрутов API
     */
    public function onApiBeforeRegisterRoutes($params) {
        // Логика перед регистрацией маршрутов API
        return $params;
    }
    
    /**
     * Получение общих утилит
     * 
     * @return array Массив с утилитами
     */
    public function getUtils() {
        return [
            \'formatDate\' => function($date, $format = \'Y-m-d H:i:s\') {
                return date($format, strtotime($date));
            },
            \'sanitize\' => function($input) {
                return htmlspecialchars($input, ENT_QUOTES, \'UTF-8\');
            },
            \'slugify\' => function($text) {
                $text = transliterator_transliterate(\'Any-Latin; Latin-ASCII; Lower()\', $text);
                $text = preg_replace(\'/[^a-z0-9-]/\', \'-\', $text);
                $text = preg_replace(\'/-+/\', \'-\', $text);
                return trim($text, \'-\');
            }
        ];
    }
}';

// Содержимое для module.php (описание модуля)
$moduleInfoContent = '<?php
/**
 * Описание базового модуля Core
 */
return [
    \'id\' => \'core\',
    \'name\' => \'Core Module\',
    \'description\' => \'Базовый модуль системы, предоставляющий основные функции и API\',
    \'version\' => \'1.0.0\',
    \'author\' => \'Universal CMS Team\',
    \'author_url\' => \'https://example.com\',
    \'dependencies\' => [], // базовый модуль без зависимостей
    \'config\' => [
        \'debug\' => false,
        \'cache_enabled\' => true,
        \'cache_ttl\' => 3600
    ]
];';

// Создаем или перезаписываем файлы
$result = [
    'success' => true,
    'message' => 'Файлы Core модуля успешно созданы/обновлены',
    'details' => []
];

// Сначала удаляем старые файлы, если они существуют
if (file_exists($moduleClassPath)) {
    if (unlink($moduleClassPath)) {
        $result['details'][] = 'Старый файл Module.php удален';
    } else {
        $result['success'] = false;
        $result['details'][] = 'Не удалось удалить старый файл Module.php';
    }
}

if (file_exists($moduleInfoPath)) {
    if (unlink($moduleInfoPath)) {
        $result['details'][] = 'Старый файл module.php удален';
    } else {
        $result['success'] = false;
        $result['details'][] = 'Не удалось удалить старый файл module.php';
    }
}

// Создаем новые файлы
if (file_put_contents($moduleClassPath, $moduleClassContent)) {
    $result['details'][] = 'Файл Module.php успешно создан';
} else {
    $result['success'] = false;
    $result['details'][] = 'Не удалось создать файл Module.php';
}

if (file_put_contents($moduleInfoPath, $moduleInfoContent)) {
    $result['details'][] = 'Файл module.php успешно создан';
} else {
    $result['success'] = false;
    $result['details'][] = 'Не удалось создать файл module.php';
}

// Проверяем права доступа
$result['details'][] = 'Права доступа Module.php: ' . substr(sprintf('%o', fileperms($moduleClassPath)), -4);
$result['details'][] = 'Права доступа module.php: ' . substr(sprintf('%o', fileperms($moduleInfoPath)), -4);

// Возвращаем результат в формате JSON
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT); 
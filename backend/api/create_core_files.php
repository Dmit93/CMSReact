<?php
/**
 * Скрипт для создания файлов модуля Core
 */

// Включаем вывод ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Вывод информации о действиях
echo '<pre>';
echo "Начинаем создание файлов модуля Core...\n";

// Определяем пути
$coreDir = dirname(__DIR__) . '/modules/Core';
echo "Директория модуля: {$coreDir}\n";

// Проверяем существование директории
if (!file_exists($coreDir)) {
    if (mkdir($coreDir, 0755, true)) {
        echo "Директория {$coreDir} создана\n";
    } else {
        echo "ОШИБКА: Не удалось создать директорию {$coreDir}\n";
        die();
    }
} else {
    echo "Директория {$coreDir} уже существует\n";
}

// Путь к файлу Module.php
$moduleFilePath = $coreDir . '/Module.php';
echo "Путь к Module.php: {$moduleFilePath}\n";

// Содержимое Module.php
$moduleContent = <<<'EOT'
<?php
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
        $moduleInfo = include __DIR__ . '/module.php';
        $this->setInfo($moduleInfo);
        
        // Регистрируем обработчики событий
        $this->registerEventHandlers();
        
        // Логируем загрузку
        Logger::getInstance()->info('Core module initialized', [
            'version' => $this->moduleInfo['version']
        ]);
    }
    
    /**
     * Регистрация обработчиков событий
     */
    private function registerEventHandlers() {
        // Регистрируем базовые хуки
        $this->registerEventHandler('core.init', [$this, 'onCoreInit']);
        $this->registerEventHandler('core.shutdown', [$this, 'onCoreShutdown']);
        
        // Хуки для API
        $this->registerEventHandler('api.before_register_routes', [$this, 'onApiBeforeRegisterRoutes']);
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
            'formatDate' => function($date, $format = 'Y-m-d H:i:s') {
                return date($format, strtotime($date));
            },
            'sanitize' => function($input) {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            },
            'slugify' => function($text) {
                $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
                $text = preg_replace('/[^a-z0-9-]/', '-', $text);
                $text = preg_replace('/-+/', '-', $text);
                return trim($text, '-');
            }
        ];
    }
}
EOT;

// Создаем файл Module.php
if (file_put_contents($moduleFilePath, $moduleContent)) {
    echo "Файл Module.php успешно создан\n";
    echo "Размер файла: " . filesize($moduleFilePath) . " байт\n";
    echo "Права доступа: " . substr(sprintf('%o', fileperms($moduleFilePath)), -4) . "\n";
} else {
    echo "ОШИБКА: Не удалось создать файл Module.php\n";
}

// Путь к файлу module.php
$moduleInfoPath = $coreDir . '/module.php';
echo "Путь к module.php: {$moduleInfoPath}\n";

// Содержимое module.php
$moduleInfoContent = <<<'EOT'
<?php
/**
 * Описание базового модуля Core
 */
return [
    'id' => 'core',
    'name' => 'Core Module',
    'description' => 'Базовый модуль системы, предоставляющий основные функции и API',
    'version' => '1.0.0',
    'author' => 'Universal CMS Team',
    'author_url' => 'https://example.com',
    'dependencies' => [], // базовый модуль без зависимостей
    'config' => [
        'debug' => false,
        'cache_enabled' => true,
        'cache_ttl' => 3600
    ]
];
EOT;

// Создаем файл module.php
if (file_put_contents($moduleInfoPath, $moduleInfoContent)) {
    echo "Файл module.php успешно создан\n";
    echo "Размер файла: " . filesize($moduleInfoPath) . " байт\n";
    echo "Права доступа: " . substr(sprintf('%o', fileperms($moduleInfoPath)), -4) . "\n";
} else {
    echo "ОШИБКА: Не удалось создать файл module.php\n";
}

// Проверяем существование файлов
if (file_exists($moduleFilePath) && file_exists($moduleInfoPath)) {
    echo "Оба файла успешно созданы\n";
    
    // Выводим содержимое файлов для проверки
    echo "\nСодержимое Module.php:\n" . file_get_contents($moduleFilePath) . "\n";
    echo "\nСодержимое module.php:\n" . file_get_contents($moduleInfoPath) . "\n";
} else {
    echo "ОШИБКА: Не все файлы были созданы\n";
}

echo '</pre>';
?> 
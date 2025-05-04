<?php
/**
 * Скрипт для исправления модуля Core для PHP 8+
 */

// Включаем вывод ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<pre>';
echo "=== ИСПРАВЛЕНИЕ МОДУЛЯ CORE ДЛЯ PHP 8+ ===\n\n";

// Определяем пути
$moduleDir = dirname(__DIR__) . '/modules/Core';
$oldModuleFilePath = $moduleDir . '/Module.php';
$oldModuleInfoPath = $moduleDir . '/module.php';
$newModuleFilePath = $moduleDir . '/Module.new.php';
$newModuleInfoPath = $moduleDir . '/module.new.php';

// Проверяем существование директории
if (!is_dir($moduleDir)) {
    if (mkdir($moduleDir, 0755, true)) {
        echo "Директория {$moduleDir} создана\n";
    } else {
        echo "ОШИБКА: Не удалось создать директорию {$moduleDir}\n";
        exit(1);
    }
} else {
    echo "Директория {$moduleDir} уже существует\n";
}

// Класс модуля для PHP 8+ с использованием строгой типизации
$moduleContent = <<<'EOT'
<?php
declare(strict_types=1);

namespace Modules\Core;

use Core\BaseModule;
use Core\Logger;

/**
 * Основной класс базового модуля Core
 */
class Module extends BaseModule 
{
    /**
     * Инициализация модуля
     */
    public function init(): void 
    {
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
    private function registerEventHandlers(): void 
    {
        // Регистрируем базовые хуки
        $this->registerEventHandler('core.init', [$this, 'onCoreInit']);
        $this->registerEventHandler('core.shutdown', [$this, 'onCoreShutdown']);
        
        // Хуки для API
        $this->registerEventHandler('api.before_register_routes', [$this, 'onApiBeforeRegisterRoutes']);
    }
    
    /**
     * Обработчик события инициализации ядра
     */
    public function onCoreInit(array $params): array 
    {
        // Логика при инициализации ядра
        return $params;
    }
    
    /**
     * Обработчик события завершения работы ядра
     */
    public function onCoreShutdown(array $params): array 
    {
        // Логика при завершении работы ядра
        return $params;
    }
    
    /**
     * Обработчик события перед регистрацией маршрутов API
     */
    public function onApiBeforeRegisterRoutes(array $params): array 
    {
        // Логика перед регистрацией маршрутов API
        return $params;
    }
    
    /**
     * Получение общих утилит
     * 
     * @return array Массив с утилитами
     */
    public function getUtils(): array 
    {
        return [
            'formatDate' => function(string $date, string $format = 'Y-m-d H:i:s'): string {
                return date($format, strtotime($date));
            },
            'sanitize' => function(string $input): string {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            },
            'slugify' => function(string $text): string {
                if (function_exists('transliterator_transliterate')) {
                    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
                } else {
                    $text = strtolower($text);
                }
                $text = preg_replace('/[^a-z0-9-]/', '-', $text);
                $text = preg_replace('/-+/', '-', $text);
                return trim($text, '-');
            }
        ];
    }
}
EOT;

// Описание модуля для PHP 8+ с использованием строгой типизации
$moduleInfoContent = <<<'EOT'
<?php
declare(strict_types=1);

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

// Создаем новые файлы
echo "Создаем новые файлы...\n";

if (file_put_contents($newModuleFilePath, $moduleContent)) {
    echo "Файл Module.new.php успешно создан\n";
} else {
    echo "ОШИБКА: Не удалось создать файл Module.new.php\n";
    exit(1);
}

if (file_put_contents($newModuleInfoPath, $moduleInfoContent)) {
    echo "Файл module.new.php успешно создан\n";
} else {
    echo "ОШИБКА: Не удалось создать файл module.new.php\n";
    exit(1);
}

// Перемещаем старые файлы в бэкап, если они существуют
if (file_exists($oldModuleFilePath)) {
    $backupName = $oldModuleFilePath . '.bak';
    if (rename($oldModuleFilePath, $backupName)) {
        echo "Сделан бэкап старого Module.php -> {$backupName}\n";
    } else {
        echo "ВНИМАНИЕ: Не удалось создать бэкап Module.php\n";
        // Пробуем удалить старый файл
        if (unlink($oldModuleFilePath)) {
            echo "Старый файл Module.php удален\n";
        } else {
            echo "ОШИБКА: Не удалось удалить старый файл Module.php\n";
            exit(1);
        }
    }
}

if (file_exists($oldModuleInfoPath)) {
    $backupName = $oldModuleInfoPath . '.bak';
    if (rename($oldModuleInfoPath, $backupName)) {
        echo "Сделан бэкап старого module.php -> {$backupName}\n";
    } else {
        echo "ВНИМАНИЕ: Не удалось создать бэкап module.php\n";
        // Пробуем удалить старый файл
        if (unlink($oldModuleInfoPath)) {
            echo "Старый файл module.php удален\n";
        } else {
            echo "ОШИБКА: Не удалось удалить старый файл module.php\n";
            exit(1);
        }
    }
}

// Перемещаем новые файлы на место старых
if (rename($newModuleFilePath, $oldModuleFilePath)) {
    echo "Новый файл Module.php успешно установлен\n";
} else {
    echo "ОШИБКА: Не удалось установить новый файл Module.php\n";
    exit(1);
}

if (rename($newModuleInfoPath, $oldModuleInfoPath)) {
    echo "Новый файл module.php успешно установлен\n";
} else {
    echo "ОШИБКА: Не удалось установить новый файл module.php\n";
    exit(1);
}

// Проверяем права доступа файлов
chmod($oldModuleFilePath, 0644);
chmod($oldModuleInfoPath, 0644);

echo "Установлены права 0644 для обоих файлов\n";

// Меняем файл init.php на более надежный вариант для PHP 8+
$initFilePath = $moduleDir . '/init.php';
$initContent = <<<'EOT'
<?php
declare(strict_types=1);

/**
 * Инициализация модуля Core для PHP 8+
 */

namespace Modules\Core;

// Подключаем класс модуля напрямую с полным путем
$moduleClassFile = __DIR__ . '/Module.php';

if (!file_exists($moduleClassFile)) {
    throw new \RuntimeException("Файл модуля Core не найден: {$moduleClassFile}");
}

require_once $moduleClassFile;

// Проверяем, существует ли класс Module
if (!class_exists('\\Modules\\Core\\Module')) {
    throw new \RuntimeException("Класс \\Modules\\Core\\Module не найден после подключения файла");
}

// Создаем экземпляр модуля
$module = new Module();

// Возвращаем модуль
return $module;
EOT;

if (file_exists($initFilePath)) {
    $backupName = $initFilePath . '.bak';
    if (rename($initFilePath, $backupName)) {
        echo "Сделан бэкап старого init.php -> {$backupName}\n";
    } else {
        echo "ВНИМАНИЕ: Не удалось создать бэкап init.php\n";
        if (unlink($initFilePath)) {
            echo "Старый файл init.php удален\n";
        } else {
            echo "ОШИБКА: Не удалось удалить старый файл init.php\n";
            exit(1);
        }
    }
}

if (file_put_contents($initFilePath, $initContent)) {
    echo "Файл init.php успешно обновлен\n";
    chmod($initFilePath, 0644);
    echo "Установлены права 0644 для init.php\n";
} else {
    echo "ОШИБКА: Не удалось обновить файл init.php\n";
    exit(1);
}

echo "\n=== МОДУЛЬ CORE УСПЕШНО ИСПРАВЛЕН ДЛЯ PHP 8+ ===\n";
echo "Теперь вы можете запустить скрипт install_core.php\n";
echo '</pre>'; 
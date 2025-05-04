<?php
/**
 * Скрипт для диагностики проблемы с модулем Core
 */

// Включаем вывод ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<pre>';
echo "=== ДИАГНОСТИКА МОДУЛЯ CORE ===\n\n";

// Версия PHP
echo "Версия PHP: " . phpversion() . "\n";
echo "Расширения PHP: " . implode(', ', get_loaded_extensions()) . "\n\n";

// Пути и директории
$moduleDir = dirname(__DIR__) . '/modules/Core';
$moduleFilePath = $moduleDir . '/Module.php';
$moduleInfoPath = $moduleDir . '/module.php';
$initFilePath = $moduleDir . '/init.php';

echo "Директория модуля: {$moduleDir}\n";
echo "Существование директории: " . (file_exists($moduleDir) ? 'ДА' : 'НЕТ') . "\n";
echo "Права на директорию: " . substr(sprintf('%o', fileperms($moduleDir)), -4) . "\n\n";

// Проверка файлов
echo "=== ПРОВЕРКА ФАЙЛОВ ===\n";
$files = [$moduleFilePath, $moduleInfoPath, $initFilePath];
$fileNames = ['Module.php', 'module.php', 'init.php'];

foreach ($files as $index => $file) {
    $fileName = $fileNames[$index];
    echo "Файл: {$fileName}\n";
    echo "Путь: {$file}\n";
    echo "Существование: " . (file_exists($file) ? 'ДА' : 'НЕТ') . "\n";
    
    if (file_exists($file)) {
        echo "Размер: " . filesize($file) . " байт\n";
        echo "Права: " . substr(sprintf('%o', fileperms($file)), -4) . "\n";
        echo "MD5 хеш: " . md5_file($file) . "\n";
        
        // Проверяем содержимое файла
        $content = file_get_contents($file);
        echo "Начало файла: " . substr(str_replace("\n", "\\n", $content), 0, 100) . "...\n";
        
        // Проверка Module.php на правильное пространство имен
        if ($fileName === 'Module.php') {
            if (strpos($content, 'namespace Modules\\Core;') !== false) {
                echo "Пространство имен: КОРРЕКТНО\n";
            } else {
                echo "Пространство имен: НЕПРАВИЛЬНО\n";
                echo "Найдено: " . preg_match('/namespace\s+([^;]+);/', $content, $matches) ? $matches[1] : 'не найдено' . "\n";
            }
            
            if (strpos($content, 'class Module extends BaseModule') !== false) {
                echo "Объявление класса: КОРРЕКТНО\n";
            } else {
                echo "Объявление класса: НЕПРАВИЛЬНО\n";
                echo "Найдено: " . preg_match('/class\s+Module\s+extends\s+([^\s{]+)/', $content, $matches) ? $matches[1] : 'не найдено' . "\n";
            }
        }
    }
    echo "\n";
}

// Проверяем файловую систему
echo "=== ПРОВЕРКА СОДЕРЖИМОГО ДИРЕКТОРИИ ===\n";
if (is_dir($moduleDir)) {
    $dirContents = scandir($moduleDir);
    foreach ($dirContents as $item) {
        if ($item != "." && $item != "..") {
            echo "{$item} (";
            if (is_dir($moduleDir . '/' . $item)) {
                echo "директория";
            } else {
                echo "файл, " . filesize($moduleDir . '/' . $item) . " байт";
            }
            echo ")\n";
        }
    }
} else {
    echo "Директория не существует или недоступна\n";
}

echo "\n=== ПРОВЕРКА ПОДКЛЮЧЕНИЯ ФАЙЛА ===\n";
try {
    // Пробуем подключить файл напрямую
    echo "Пробуем подключить Module.php напрямую...\n";
    if (file_exists($moduleFilePath)) {
        $content = file_get_contents($moduleFilePath);
        echo "Содержимое файла перед подключением:\n";
        echo substr($content, 0, 500) . "...\n\n";
        
        include_once $moduleFilePath;
        echo "Файл успешно подключен\n";
        
        // Проверяем, доступен ли класс
        if (class_exists('Modules\\Core\\Module')) {
            echo "Класс Modules\\Core\\Module НАЙДЕН\n";
        } else {
            echo "Класс Modules\\Core\\Module НЕ НАЙДЕН\n";
            echo "Доступные классы в пространстве имен Modules\\Core:\n";
            $declaredClasses = get_declared_classes();
            foreach ($declaredClasses as $class) {
                if (strpos($class, 'Modules\\Core\\') === 0) {
                    echo "- {$class}\n";
                }
            }
        }
    } else {
        echo "Файл Module.php не существует\n";
    }
} catch (Throwable $e) {
    echo "ОШИБКА при подключении файла: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . " (строка " . $e->getLine() . ")\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== ПРОВЕРКА АВТОЗАГРУЗЧИКА ===\n";
// Получаем зарегистрированные функции автозагрузки
$autoloaders = spl_autoload_functions();
if ($autoloaders) {
    echo "Зарегистрированные автозагрузчики (" . count($autoloaders) . "):\n";
    foreach ($autoloaders as $index => $autoloader) {
        if (is_array($autoloader)) {
            if (is_object($autoloader[0])) {
                echo ($index+1) . ". Объект класса " . get_class($autoloader[0]) . "->" . $autoloader[1] . "()\n";
            } else {
                echo ($index+1) . ". Статический метод " . $autoloader[0] . "::" . $autoloader[1] . "()\n";
            }
        } else {
            echo ($index+1) . ". Функция " . $autoloader . "()\n";
        }
    }
} else {
    echo "Автозагрузчики не зарегистрированы\n";
}

echo "\n=== КОНЕЦ ДИАГНОСТИКИ ===\n";
echo '</pre>'; 
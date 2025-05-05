<?php
// Устанавливаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Заголовок для вывода текста
header('Content-Type: text/html; charset=utf-8');

// Подключаем Database.php
require_once __DIR__ . '/backend/api/Database.php';

// Получаем экземпляр базы данных
$db = \API\Database::getInstance();

// HTML страница
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Исправление таблицы категорий</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .table-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        button:hover { background: #45a049; }
        .actions { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Исправление структуры таблицы product_categories</h1>';

// Функция для проверки существования таблицы
function tableExists($db, $table) {
    $result = $db->fetch("SHOW TABLES LIKE '$table'");
    return !empty($result);
}

// Функция для проверки существования столбца в таблице
function columnExists($db, $table, $column) {
    $result = $db->fetchAll("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return !empty($result);
}

// Функция для получения внешних ключей таблицы
function getForeignKeys($db, $table) {
    $query = "
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME 
        FROM 
            information_schema.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = DATABASE() AND 
            TABLE_NAME = ? AND 
            REFERENCED_TABLE_NAME IS NOT NULL";
    
    return $db->fetchAll($query, [$table]);
}

try {
    // Проверяем существование таблицы
    if (!tableExists($db, 'product_categories')) {
        echo '<p class="error">Таблица product_categories не существует!</p>';
        echo '<p>Необходимо сначала создать таблицу. Перейдите на <a href="check_shop_tables.php">страницу проверки таблиц</a>.</p>';
    } else {
        // Получаем текущую структуру таблицы
        $structure = $db->fetchAll("SHOW COLUMNS FROM product_categories");
        $columns = [];
        foreach ($structure as $column) {
            $columns[$column['Field']] = $column;
        }
        
        echo '<h2>Текущая структура таблицы:</h2>';
        echo '<pre>';
        print_r($structure);
        echo '</pre>';
        
        // Получаем информацию о внешних ключах
        $foreignKeys = getForeignKeys($db, 'product_categories');
        
        if (!empty($foreignKeys)) {
            echo '<h2>Внешние ключи таблицы:</h2>';
            echo '<pre>';
            print_r($foreignKeys);
            echo '</pre>';
            
            // Удаляем внешние ключи, связанные с image_id
            foreach ($foreignKeys as $fk) {
                if ($fk['COLUMN_NAME'] === 'image_id') {
                    try {
                        $dropFkSql = "ALTER TABLE `product_categories` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`";
                        $db->query($dropFkSql);
                        echo "<p class='success'>Удален внешний ключ {$fk['CONSTRAINT_NAME']} для поля image_id</p>";
                    } catch (\Exception $e) {
                        echo "<p class='error'>Ошибка при удалении внешнего ключа {$fk['CONSTRAINT_NAME']}: {$e->getMessage()}</p>";
                    }
                }
            }
        } else {
            echo '<p>Внешние ключи не найдены.</p>';
        }
        
        // Список изменений, которые нужно выполнить
        $alterCommands = [];
        
        // Проверяем наличие AUTO_INCREMENT у id
        if (isset($columns['id']) && strpos($columns['id']['Extra'], 'auto_increment') === false) {
            $alterCommands[] = "MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";
        }
        
        // Проверяем и исправляем image_id на image
        if (isset($columns['image_id']) && !isset($columns['image'])) {
            $alterCommands[] = "CHANGE `image_id` `image` varchar(255) DEFAULT NULL";
        } else if (!isset($columns['image_id']) && !isset($columns['image'])) {
            $alterCommands[] = "ADD `image` varchar(255) DEFAULT NULL AFTER `parent_id`";
        }
        
        // Добавляем недостающие поля
        $missingColumns = [
            'status' => "ADD `status` enum('active','inactive') NOT NULL DEFAULT 'active' AFTER `description`",
            'featured' => "ADD `featured` tinyint(1) DEFAULT '0' AFTER `image`",
            'sort_order' => "ADD `sort_order` int(11) DEFAULT '0' AFTER `featured`",
            'meta_title' => "ADD `meta_title` varchar(255) DEFAULT NULL AFTER `sort_order`",
            'meta_description' => "ADD `meta_description` text AFTER `meta_title`",
        ];
        
        foreach ($missingColumns as $column => $command) {
            if (!isset($columns[$column])) {
                $alterCommands[] = $command;
            }
        }
        
        // Если есть изменения, выполняем их
        if (!empty($alterCommands)) {
            echo '<h2>Необходимые изменения:</h2>';
            echo '<pre>';
            echo implode(";\n", $alterCommands);
            echo '</pre>';
            
            // Выполняем изменения поочередно
            try {
                // Начинаем транзакцию
                $db->beginTransaction();
                
                // Выполняем каждую команду отдельно
                foreach ($alterCommands as $command) {
                    $alterSql = "ALTER TABLE `product_categories` " . $command;
                    echo "<p>Выполнение команды: {$alterSql}</p>";
                    $db->query($alterSql);
                }
                
                // Фиксируем транзакцию
                $db->commit();
                
                echo '<p class="success">Структура таблицы успешно изменена!</p>';
            } catch (\Exception $e) {
                // Откатываем транзакцию в случае ошибки
                $db->rollback();
                echo '<p class="error">Ошибка при изменении структуры таблицы: ' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
            
            // Получаем обновленную структуру
            $newStructure = $db->fetchAll("SHOW COLUMNS FROM product_categories");
            
            echo '<h2>Обновленная структура таблицы:</h2>';
            echo '<pre>';
            print_r($newStructure);
            echo '</pre>';
        } else {
            echo '<p class="success">Структура таблицы соответствует требованиям, изменения не требуются.</p>';
        }
        
        // Добавляем первичный ключ, если его нет
        $indexes = $db->fetchAll("SHOW INDEX FROM product_categories");
        $hasPrimaryKey = false;
        
        foreach ($indexes as $index) {
            if ($index['Key_name'] === 'PRIMARY') {
                $hasPrimaryKey = true;
                break;
            }
        }
        
        if (!$hasPrimaryKey) {
            try {
                $db->query("ALTER TABLE `product_categories` ADD PRIMARY KEY (`id`)");
                echo '<p class="success">Добавлен первичный ключ для поля id.</p>';
            } catch (\Exception $e) {
                echo '<p class="error">Ошибка при добавлении первичного ключа: ' . $e->getMessage() . '</p>';
            }
        }
        
        // Проверяем наличие уникального индекса для slug
        $hasSlugIndex = false;
        
        foreach ($indexes as $index) {
            if ($index['Key_name'] === 'slug' || ($index['Key_name'] === 'UNI' && $index['Column_name'] === 'slug')) {
                $hasSlugIndex = true;
                break;
            }
        }
        
        if (!$hasSlugIndex) {
            try {
                $db->query("ALTER TABLE `product_categories` ADD UNIQUE KEY `slug` (`slug`)");
                echo '<p class="success">Добавлен уникальный индекс для поля slug.</p>';
            } catch (\Exception $e) {
                echo '<p class="error">Ошибка при добавлении индекса для slug: ' . $e->getMessage() . '</p>';
            }
        }
        
        // Кнопки для навигации
        echo '<div class="actions">';
        echo '<button onclick="location.href=\'check_shop_tables.php\'">Вернуться к проверке таблиц</button>';
        echo '<button onclick="location.href=\'test_create_product.php\'">Тестировать добавление товаров</button>';
        echo '</div>';
    }
} catch (\Exception $e) {
    echo '<p class="error">Общая ошибка: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>
</body>
</html>';
?> 
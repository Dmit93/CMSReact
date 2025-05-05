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
    <title>Добавление столбца category_id</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Добавление столбца category_id в таблицу products</h1>';

try {
    // Проверяем, есть ли уже столбец category_id
    $columns = $db->fetchAll("SHOW COLUMNS FROM products LIKE 'category_id'");
    
    if (!empty($columns)) {
        echo '<p class="success">Столбец category_id уже существует в таблице products.</p>';
    } else {
        // Добавляем столбец category_id
        $db->query("ALTER TABLE products ADD COLUMN category_id INT(11) DEFAULT NULL AFTER sku");
        
        // Добавляем индекс для быстрого поиска по category_id
        $db->query("ALTER TABLE products ADD INDEX idx_category_id (category_id)");
        
        // Создаем внешний ключ, если существует таблица categories
        try {
            // Проверяем существование таблицы категорий
            $tableExists = $db->fetchAll("SHOW TABLES LIKE 'categories'");
            
            if (!empty($tableExists)) {
                // Проверяем, есть ли в таблице категорий первичный ключ id
                $primaryKey = $db->fetchAll("SHOW KEYS FROM categories WHERE Key_name = 'PRIMARY'");
                
                if (!empty($primaryKey)) {
                    // Добавляем внешний ключ
                    $db->query("ALTER TABLE products 
                                ADD CONSTRAINT fk_product_category 
                                FOREIGN KEY (category_id) 
                                REFERENCES categories(id) 
                                ON DELETE SET NULL");
                    
                    echo '<p class="success">Столбец category_id успешно добавлен и связан с таблицей categories.</p>';
                } else {
                    echo '<p class="success">Столбец category_id успешно добавлен, но не связан с таблицей categories (отсутствует первичный ключ).</p>';
                }
            } else {
                echo '<p class="success">Столбец category_id успешно добавлен, но таблица categories не найдена для создания внешнего ключа.</p>';
            }
        } catch (\Exception $e) {
            echo '<p class="success">Столбец category_id успешно добавлен, но не удалось создать внешний ключ: ' . $e->getMessage() . '</p>';
        }
    }
    
    // Получаем обновленную структуру таблицы
    $tableStructure = $db->fetchAll("SHOW COLUMNS FROM products");
    
    echo '<h2>Текущая структура таблицы products:</h2>';
    echo '<pre>';
    foreach ($tableStructure as $column) {
        echo $column['Field'] . ' - ' . $column['Type'];
        if ($column['Null'] === 'NO') {
            echo ' NOT NULL';
        }
        if ($column['Key'] !== '') {
            echo ' (' . $column['Key'] . ')';
        }
        if ($column['Default'] !== null) {
            echo ' DEFAULT ' . $column['Default'];
        }
        echo "\n";
    }
    echo '</pre>';
    
    // Проверяем индексы
    $indexes = $db->fetchAll("SHOW INDEX FROM products");
    
    echo '<h2>Индексы таблицы products:</h2>';
    echo '<pre>';
    foreach ($indexes as $index) {
        echo $index['Key_name'] . ' - ' . $index['Column_name'] . ' (' . ($index['Non_unique'] ? 'Not unique' : 'Unique') . ")\n";
    }
    echo '</pre>';
    
    // Если все успешно, добавляем кнопку для возврата к тестированию
    echo '<p><a href="test_create_product.php" style="display: inline-block; padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Вернуться к тестированию добавления товаров</a></p>';
    
} catch (\Exception $e) {
    echo '<p class="error">Ошибка: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>
</body>
</html>';
?> 
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
    <title>Проверка таблиц Shop</title>
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
        <h1>Проверка таблиц модуля Shop</h1>';

// Функция для проверки существования таблицы
function tableExists($db, $table) {
    $result = $db->fetch("SHOW TABLES LIKE '$table'");
    return !empty($result);
}

// Функция для создания таблицы категорий
function createProductCategoriesTable($db) {
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS `product_categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `slug` varchar(255) NOT NULL,
                `description` text,
                `parent_id` int(11) DEFAULT NULL,
                `image` varchar(255) DEFAULT NULL,
                `featured` tinyint(1) DEFAULT '0',
                `sort_order` int(11) DEFAULT '0',
                `meta_title` varchar(255) DEFAULT NULL,
                `meta_description` text,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `parent_id` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Добавляем внешний ключ, если таблица уже существует
        try {
            $db->query("
                ALTER TABLE `product_categories`
                ADD CONSTRAINT `fk_category_parent`
                FOREIGN KEY (`parent_id`)
                REFERENCES `product_categories` (`id`)
                ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            // Игнорируем ошибку, если ключ уже существует
        }
        
        return true;
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// Функция для создания таблицы связей product_category
function createProductCategoryTable($db) {
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS `product_category` (
                `product_id` int(11) NOT NULL,
                `category_id` int(11) NOT NULL,
                PRIMARY KEY (`product_id`,`category_id`),
                KEY `category_id` (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Добавляем внешние ключи
        try {
            $db->query("
                ALTER TABLE `product_category`
                ADD CONSTRAINT `fk_pc_product_id`
                FOREIGN KEY (`product_id`)
                REFERENCES `products` (`id`)
                ON DELETE CASCADE
            ");
        } catch (\Exception $e) {
            // Игнорируем ошибку, если ключ уже существует
        }
        
        try {
            $db->query("
                ALTER TABLE `product_category`
                ADD CONSTRAINT `fk_pc_category_id`
                FOREIGN KEY (`category_id`)
                REFERENCES `product_categories` (`id`)
                ON DELETE CASCADE
            ");
        } catch (\Exception $e) {
            // Игнорируем ошибку, если ключ уже существует
        }
        
        return true;
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

// Создаем таблицы, если они отсутствуют
try {
    // Проверяем таблицы
    $tables = [
        'products' => tableExists($db, 'products'),
        'product_categories' => tableExists($db, 'product_categories'),
        'product_category' => tableExists($db, 'product_category')
    ];
    
    echo '<h2>Состояние таблиц:</h2>';
    
    // Проверяем таблицу products
    echo '<div class="table-box">';
    echo '<h3>Таблица products:</h3>';
    if ($tables['products']) {
        echo '<p class="success">✓ Таблица products существует</p>';
        
        // Проверяем наличие столбца category_id
        $columns = $db->fetchAll("SHOW COLUMNS FROM products LIKE 'category_id'");
        if (!empty($columns)) {
            echo '<p class="success">✓ Столбец category_id существует</p>';
        } else {
            echo '<p class="error">✗ Столбец category_id отсутствует!</p>';
            echo '<button onclick="location.href=\'add_category_id.php\'">Добавить столбец category_id</button>';
        }
        
        // Показываем структуру таблицы
        $structure = $db->fetchAll("SHOW COLUMNS FROM products");
        echo '<h4>Структура таблицы:</h4>';
        echo '<pre>';
        foreach ($structure as $column) {
            echo $column['Field'] . ' - ' . $column['Type'];
            if ($column['Null'] === 'NO') echo ' NOT NULL';
            if ($column['Key']) echo ' (' . $column['Key'] . ')';
            echo "\n";
        }
        echo '</pre>';
        
        // Показываем количество записей
        $count = $db->fetch("SELECT COUNT(*) as cnt FROM products");
        echo '<p>Количество записей: ' . $count['cnt'] . '</p>';
    } else {
        echo '<p class="error">✗ Таблица products не существует!</p>';
    }
    echo '</div>';
    
    // Проверяем таблицу product_categories
    echo '<div class="table-box">';
    echo '<h3>Таблица product_categories:</h3>';
    if ($tables['product_categories']) {
        echo '<p class="success">✓ Таблица product_categories существует</p>';
        
        // Показываем структуру таблицы
        $structure = $db->fetchAll("SHOW COLUMNS FROM product_categories");
        echo '<h4>Структура таблицы:</h4>';
        echo '<pre>';
        foreach ($structure as $column) {
            echo $column['Field'] . ' - ' . $column['Type'];
            if ($column['Null'] === 'NO') echo ' NOT NULL';
            if ($column['Key']) echo ' (' . $column['Key'] . ')';
            echo "\n";
        }
        echo '</pre>';
        
        // Показываем количество записей
        $count = $db->fetch("SELECT COUNT(*) as cnt FROM product_categories");
        echo '<p>Количество категорий: ' . $count['cnt'] . '</p>';
        
        // Если категорий нет, предлагаем создать тестовые
        if ($count['cnt'] == 0) {
            echo '<p class="error">В таблице нет категорий!</p>';
            echo '<button id="createTestCategories">Создать тестовые категории</button>';
        }
    } else {
        echo '<p class="error">✗ Таблица product_categories не существует!</p>';
        echo '<button id="createCategoriesTable">Создать таблицу категорий</button>';
    }
    echo '</div>';
    
    // Проверяем таблицу product_category
    echo '<div class="table-box">';
    echo '<h3>Таблица product_category (связи товаров с категориями):</h3>';
    if ($tables['product_category']) {
        echo '<p class="success">✓ Таблица product_category существует</p>';
        
        // Показываем структуру таблицы
        $structure = $db->fetchAll("SHOW COLUMNS FROM product_category");
        echo '<h4>Структура таблицы:</h4>';
        echo '<pre>';
        foreach ($structure as $column) {
            echo $column['Field'] . ' - ' . $column['Type'];
            if ($column['Null'] === 'NO') echo ' NOT NULL';
            if ($column['Key']) echo ' (' . $column['Key'] . ')';
            echo "\n";
        }
        echo '</pre>';
        
        // Показываем количество связей
        $count = $db->fetch("SELECT COUNT(*) as cnt FROM product_category");
        echo '<p>Количество связей товаров с категориями: ' . $count['cnt'] . '</p>';
    } else {
        echo '<p class="error">✗ Таблица product_category не существует!</p>';
        echo '<button id="createProductCategoryTable">Создать таблицу связей</button>';
    }
    echo '</div>';
    
    // Кнопки для действий
    echo '<div class="actions">';
    echo '<button onclick="location.href=\'test_create_product.php\'">Тестировать добавление товаров</button>';
    echo '<button id="testCreateCategory">Тестировать добавление категорий</button>';
    echo '</div>';
    
    // Форма для создания тестовой категории
    echo '<div id="categoryForm" style="display:none; margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
    echo '<h3>Создать тестовую категорию</h3>';
    echo '<form id="createCategoryForm">';
    echo '<div style="margin-bottom: 10px;">';
    echo '<label for="name" style="display: block; margin-bottom: 5px;">Название категории:</label>';
    echo '<input type="text" id="name" name="name" value="Тестовая категория ' . rand(1000, 9999) . '" style="width: 100%; padding: 8px;">';
    echo '</div>';
    echo '<div style="margin-bottom: 10px;">';
    echo '<label for="slug" style="display: block; margin-bottom: 5px;">Slug (URL):</label>';
    echo '<input type="text" id="slug" name="slug" value="test-category-' . rand(1000, 9999) . '" style="width: 100%; padding: 8px;">';
    echo '</div>';
    echo '<div style="margin-bottom: 10px;">';
    echo '<label for="description" style="display: block; margin-bottom: 5px;">Описание:</label>';
    echo '<textarea id="description" name="description" style="width: 100%; padding: 8px; height: 100px;">Описание тестовой категории</textarea>';
    echo '</div>';
    echo '<button type="button" id="submitCategory" style="padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Создать категорию</button>';
    echo '</form>';
    echo '<div id="categoryResult" style="margin-top: 15px;"></div>';
    echo '</div>';
    
    // JavaScript для обработки действий на странице
    echo '
    <script>
        // Создание таблицы категорий
        document.getElementById("createCategoriesTable")?.addEventListener("click", function() {
            fetch("check_shop_tables.php?action=create_categories_table")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Таблица категорий успешно создана!");
                        location.reload();
                    } else {
                        alert("Ошибка при создании таблицы: " + data.error);
                    }
                })
                .catch(error => {
                    alert("Ошибка: " + error);
                });
        });
        
        // Создание таблицы связей
        document.getElementById("createProductCategoryTable")?.addEventListener("click", function() {
            fetch("check_shop_tables.php?action=create_product_category_table")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Таблица связей успешно создана!");
                        location.reload();
                    } else {
                        alert("Ошибка при создании таблицы: " + data.error);
                    }
                })
                .catch(error => {
                    alert("Ошибка: " + error);
                });
        });
        
        // Создание тестовых категорий
        document.getElementById("createTestCategories")?.addEventListener("click", function() {
            fetch("check_shop_tables.php?action=create_test_categories")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Тестовые категории успешно созданы!");
                        location.reload();
                    } else {
                        alert("Ошибка при создании категорий: " + data.error);
                    }
                })
                .catch(error => {
                    alert("Ошибка: " + error);
                });
        });
        
        // Отображение формы для создания категории
        document.getElementById("testCreateCategory")?.addEventListener("click", function() {
            const form = document.getElementById("categoryForm");
            form.style.display = form.style.display === "none" ? "block" : "none";
        });
        
        // Отправка формы создания категории
        document.getElementById("submitCategory")?.addEventListener("click", function() {
            const name = document.getElementById("name").value;
            const slug = document.getElementById("slug").value;
            const description = document.getElementById("description").value;
            
            // Проверка обязательных полей
            if (!name || !slug) {
                alert("Название и slug обязательны!");
                return;
            }
            
            const data = {
                name: name,
                slug: slug,
                description: description
            };
            
            fetch("/cms/backend/api/shop/categories", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                const result = document.getElementById("categoryResult");
                
                if (response.ok) {
                    return response.json().then(data => {
                        result.innerHTML = `
                            <p style="color: green; font-weight: bold;">Категория успешно создана!</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        `;
                    });
                } else {
                    return response.text().then(text => {
                        let error = "Неизвестная ошибка";
                        try {
                            const json = JSON.parse(text);
                            error = json.error || "Ошибка сервера";
                        } catch (e) {
                            error = text || "Ошибка ответа сервера";
                        }
                        
                        result.innerHTML = `
                            <p style="color: red; font-weight: bold;">Ошибка: ${error}</p>
                            <p>Статус: ${response.status}</p>
                        `;
                    });
                }
            })
            .catch(error => {
                document.getElementById("categoryResult").innerHTML = `
                    <p style="color: red; font-weight: bold;">Ошибка: ${error.message}</p>
                `;
            });
        });
    </script>
    ';
    
} catch (\Exception $e) {
    echo '<p class="error">Ошибка при проверке таблиц: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

// Обработка AJAX-запросов
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'create_categories_table':
            $result = createProductCategoriesTable($db);
            if ($result === true) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $result]);
            }
            exit;
            
        case 'create_product_category_table':
            $result = createProductCategoryTable($db);
            if ($result === true) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $result]);
            }
            exit;
            
        case 'create_test_categories':
            try {
                // Создаем 3 тестовые категории
                $categories = [
                    [
                        'name' => 'Электроника',
                        'slug' => 'electronics',
                        'description' => 'Категория электроники: смартфоны, ноутбуки, планшеты',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    [
                        'name' => 'Одежда',
                        'slug' => 'clothing',
                        'description' => 'Мужская и женская одежда',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    [
                        'name' => 'Книги',
                        'slug' => 'books',
                        'description' => 'Книги разных жанров',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ];
                
                foreach ($categories as $category) {
                    $db->insert('product_categories', $category);
                }
                
                echo json_encode(['success' => true]);
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
    }
}

echo '</div>
</body>
</html>';
?> 
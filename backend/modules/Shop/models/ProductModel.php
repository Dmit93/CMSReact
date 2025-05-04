<?php
namespace Modules\Shop\Models;

use API\Database;

/**
 * Модель для работы с товарами
 */
class ProductModel {
    /**
     * @var Database Экземпляр базы данных
     */
    private $db;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
        // Инициализируем таблицу, если она не существует
        $this->initTable();
    }
    
    /**
     * Инициализация таблицы товаров
     */
    private function initTable() {
        try {
            // Проверяем существование таблицы
            $tableExists = $this->db->fetch("SHOW TABLES LIKE 'products'");
            
            if (!$tableExists) {
                error_log("Таблица products не существует, создаем...");
                
                // Создаем таблицу products
                $createQuery = "
                    CREATE TABLE IF NOT EXISTS `products` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `title` varchar(255) NOT NULL,
                        `slug` varchar(255) DEFAULT NULL,
                        `sku` varchar(50) NOT NULL,
                        `description` text DEFAULT NULL,
                        `price` decimal(10,2) NOT NULL DEFAULT 0.00,
                        `sale_price` decimal(10,2) DEFAULT NULL,
                        `stock` int(11) NOT NULL DEFAULT 0,
                        `featured` tinyint(1) NOT NULL DEFAULT 0,
                        `status` varchar(20) NOT NULL DEFAULT 'published',
                        `views` int(11) NOT NULL DEFAULT 0,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `sku` (`sku`),
                        KEY `status` (`status`),
                        KEY `slug` (`slug`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                
                $this->db->query($createQuery);
                
                // Создаем таблицу категорий товаров, если не существует
                $createCategoriesQuery = "
                    CREATE TABLE IF NOT EXISTS `product_categories` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(255) NOT NULL,
                        `slug` varchar(255) NOT NULL,
                        `parent_id` int(11) DEFAULT NULL,
                        `description` text DEFAULT NULL,
                        `status` varchar(20) NOT NULL DEFAULT 'active',
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `slug` (`slug`),
                        KEY `parent_id` (`parent_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                
                $this->db->query($createCategoriesQuery);
                
                // Создаем таблицу связей товаров и категорий
                $createProductCategoryQuery = "
                    CREATE TABLE IF NOT EXISTS `product_category` (
                        `product_id` int(11) NOT NULL,
                        `category_id` int(11) NOT NULL,
                        PRIMARY KEY (`product_id`,`category_id`),
                        KEY `category_id` (`category_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                
                $this->db->query($createProductCategoryQuery);
                
                // Создаем таблицу для связей товаров и медиа
                $createProductMediaQuery = "
                    CREATE TABLE IF NOT EXISTS `product_media` (
                        `product_id` int(11) NOT NULL,
                        `media_id` int(11) NOT NULL,
                        `sort_order` int(11) NOT NULL DEFAULT 0,
                        PRIMARY KEY (`product_id`,`media_id`),
                        KEY `media_id` (`media_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";
                
                $this->db->query($createProductMediaQuery);
                
                error_log("Таблицы для модуля Shop успешно созданы");
                
                // Добавляем примеры товаров
                $this->addSampleProducts();
            } else {
                // Проверяем, есть ли товары в таблице
                $count = $this->db->fetch("SELECT COUNT(*) as cnt FROM products");
                
                if ($count && isset($count['cnt']) && $count['cnt'] == 0) {
                    error_log("Таблица products пуста, добавляем примеры товаров");
                    $this->addSampleProducts();
                }
            }
        } catch (\Exception $e) {
            error_log("Ошибка при инициализации таблицы products: " . $e->getMessage());
        }
    }
    
    /**
     * Добавление тестовых товаров
     */
    private function addSampleProducts() {
        try {
            // Добавляем категории, если их нет
            $categoryCount = $this->db->fetch("SELECT COUNT(*) as cnt FROM product_categories");
            
            if (!$categoryCount || $categoryCount['cnt'] == 0) {
                $categories = [
                    [
                        'name' => 'Электроника',
                        'slug' => 'electronics',
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    [
                        'name' => 'Одежда',
                        'slug' => 'clothing',
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    [
                        'name' => 'Книги',
                        'slug' => 'books',
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ];
                
                $categoryIds = [];
                foreach ($categories as $category) {
                    $categoryIds[] = $this->db->insert('product_categories', $category);
                }
                
                error_log("Добавлено категорий: " . count($categoryIds));
            } else {
                $categoryQuery = "SELECT id FROM product_categories ORDER BY id ASC";
                $categories = $this->db->fetchAll($categoryQuery);
                $categoryIds = array_column($categories, 'id');
                
                error_log("Найдены существующие категории: " . implode(", ", $categoryIds));
            }
            
            // Добавляем товары
            $products = [
                [
                    'title' => 'Смартфон X-Phone Pro',
                    'slug' => 'smartphone-x-phone-pro',
                    'sku' => 'SP-001',
                    'description' => 'Современный смартфон с большим экраном и мощным процессором.',
                    'price' => 799.99,
                    'stock' => 25,
                    'status' => 'published',
                    'featured' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'category_id' => $categoryIds[0] ?? null
                ],
                [
                    'title' => 'Футболка Premium',
                    'slug' => 'premium-tshirt',
                    'sku' => 'TS-002',
                    'description' => 'Качественная хлопковая футболка премиум класса.',
                    'price' => 29.99,
                    'stock' => 100,
                    'status' => 'published',
                    'featured' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'category_id' => $categoryIds[1] ?? null
                ],
                [
                    'title' => 'Учебник по веб-разработке',
                    'slug' => 'web-development-book',
                    'sku' => 'BK-003',
                    'description' => 'Полное руководство по веб-разработке для начинающих и продвинутых.',
                    'price' => 49.99,
                    'stock' => 15,
                    'status' => 'published',
                    'featured' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'category_id' => $categoryIds[2] ?? null
                ]
            ];
            
            foreach ($products as $product) {
                $categoryId = null;
                if (isset($product['category_id'])) {
                    $categoryId = $product['category_id'];
                    unset($product['category_id']);
                }
                
                // Проверяем, существует ли товар с таким SKU
                $existingProduct = $this->db->fetch("SELECT id FROM products WHERE sku = ?", [$product['sku']]);
                
                if (!$existingProduct) {
                    $productId = $this->db->insert('products', $product);
                    
                    // Добавляем связь с категорией
                    if ($productId && $categoryId) {
                        $this->db->insert('product_category', [
                            'product_id' => $productId,
                            'category_id' => $categoryId
                        ]);
                    }
                    
                    error_log("Добавлен товар: {$product['title']} (ID: $productId)");
                } else {
                    error_log("Товар с SKU {$product['sku']} уже существует, пропускаем.");
                }
            }
            
            error_log("Тестовые товары успешно добавлены");
        } catch (\Exception $e) {
            error_log("Ошибка при добавлении тестовых товаров: " . $e->getMessage());
        }
    }
    
    /**
     * Получение всех товаров
     * 
     * @param array $options Параметры выборки (limit, offset, order, filters)
     * @return array Массив товаров
     */
    public function getAll($options = []) {
        // Настройки по умолчанию
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'order' => 'created_at DESC',
            'status' => 'published',
            'withCategories' => false
        ];
        
        // Объединяем с переданными параметрами
        $options = array_merge($defaults, $options);
        
        // Формируем условие WHERE
        $where = "1=1";
        $params = [];
        
        // Для отладки
        error_log("ProductModel::getAll вызван с параметрами: " . json_encode($options));
        
        if (!empty($options['status'])) {
            // Если статус указан явно, используем его для фильтрации
            $where .= " AND status = ?";
            $params[] = $options['status'];
            error_log("Фильтрация по статусу: " . $options['status']);
        } else {
            // Иначе не фильтруем по статусу вообще
            error_log("Фильтрация по статусу отключена");
        }
        
        if (!empty($options['category_id'])) {
            $where .= " AND EXISTS (SELECT 1 FROM product_category pc WHERE pc.product_id = products.id AND pc.category_id = ?)";
            $params[] = $options['category_id'];
            error_log("Фильтрация по категории ID: " . $options['category_id']);
        }
        
        if (!empty($options['search'])) {
            $where .= " AND (title LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $searchTerm = "%{$options['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            error_log("Поиск по запросу: " . $options['search']);
        }
        
        if (isset($options['featured'])) {
            $where .= " AND featured = ?";
            $params[] = $options['featured'] ? 1 : 0;
            error_log("Фильтрация по избранным: " . ($options['featured'] ? 'да' : 'нет'));
        }
        
        // Проверяем наличие таблицы products
        try {
            $tableCheck = $this->db->fetch("SHOW TABLES LIKE 'products'");
            if (!$tableCheck) {
                error_log("ОШИБКА: Таблица products не существует!");
                return [];
            }
        } catch (\Exception $e) {
            error_log("Ошибка при проверке таблицы products: " . $e->getMessage());
            return [];
        }
        
        // Формируем запрос
        $query = "SELECT * FROM products WHERE {$where} ORDER BY {$options['order']} LIMIT ? OFFSET ?";
        $params[] = $options['limit'];
        $params[] = $options['offset'];
        
        error_log("SQL запрос: " . $query);
        error_log("Параметры: " . json_encode($params));
        
        // Получаем товары
        try {
            $products = $this->db->fetchAll($query, $params);
            error_log("Найдено товаров: " . count($products));
            
            // Если нужно получить категории товаров и есть товары
            if ($options['withCategories'] && !empty($products)) {
                $this->loadProductCategories($products);
            }
            
            return $products;
        } catch (\Exception $e) {
            error_log("Ошибка при выполнении запроса: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Загрузка категорий для товаров
     * 
     * @param array &$products Массив товаров
     */
    private function loadProductCategories(&$products) {
        try {
            $productIds = array_column($products, 'id');
            
            if (empty($productIds)) {
                return;
            }
            
            // Формируем строку с плейсхолдерами (?, ?, ...)
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            
            // Запрос для получения категорий
            $query = "
                SELECT pc.product_id, c.* 
                FROM product_category pc
                JOIN product_categories c ON pc.category_id = c.id
                WHERE pc.product_id IN ({$placeholders})
                ORDER BY c.name
            ";
            
            $categoryResult = $this->db->fetchAll($query, $productIds);
            error_log("Загружено категорий: " . count($categoryResult));
            
            // Группируем категории по ID товара
            $productCategories = [];
            foreach ($categoryResult as $category) {
                $productId = $category['product_id'];
                unset($category['product_id']);
                
                if (!isset($productCategories[$productId])) {
                    $productCategories[$productId] = [];
                }
                
                $productCategories[$productId][] = $category;
            }
            
            // Добавляем категории к товарам
            foreach ($products as &$product) {
                $product['categories'] = $productCategories[$product['id']] ?? [];
            }
        } catch (\Exception $e) {
            error_log("Ошибка при загрузке категорий товаров: " . $e->getMessage());
        }
    }
    
    /**
     * Получение товара по ID
     * 
     * @param int $id ID товара
     * @param bool $withCategories Включать категории товара
     * @return array|null Данные товара или null
     */
    public function getById($id, $withCategories = true) {
        // Добавляем логирование для отладки
        error_log("ProductModel::getById вызван с ID: " . json_encode($id));
        
        if (!is_numeric($id) || $id <= 0) {
            error_log("Некорректный ID товара: " . json_encode($id));
            return null;
        }
        
        $query = "SELECT * FROM products WHERE id = ? LIMIT 1";
        $product = $this->db->fetch($query, [$id]);
        
        if (!$product) {
            error_log("Товар с ID " . json_encode($id) . " не найден в базе данных");
            return null;
        } else {
            error_log("Товар с ID " . json_encode($id) . " найден: " . json_encode($product));
        }
        
        // Загружаем категории
        if ($withCategories) {
            $categoryQuery = "
                SELECT c.* 
                FROM product_category pc
                JOIN product_categories c ON pc.category_id = c.id
                WHERE pc.product_id = ?
                ORDER BY c.name
            ";
            
            $product['categories'] = $this->db->fetchAll($categoryQuery, [$id]);
            error_log("Загружены категории для товара: " . json_encode($product['categories']));
            
            // Загружаем галерею изображений
            $mediaQuery = "
                SELECT m.* 
                FROM product_media pm
                JOIN media m ON pm.media_id = m.id
                WHERE pm.product_id = ?
                ORDER BY pm.sort_order
            ";
            
            $product['gallery'] = $this->db->fetchAll($mediaQuery, [$id]);
            error_log("Загружена галерея для товара, количество изображений: " . count($product['gallery']));
        }
        
        return $product;
    }
    
    /**
     * Получение товара по slug
     * 
     * @param string $slug Slug товара
     * @param bool $withCategories Включать категории товара
     * @return array|null Данные товара или null
     */
    public function getBySlug($slug, $withCategories = true) {
        $query = "SELECT * FROM products WHERE slug = ? LIMIT 1";
        $product = $this->db->fetch($query, [$slug]);
        
        if (!$product) {
            return null;
        }
        
        return $this->getById($product['id'], $withCategories);
    }
    
    /**
     * Создание товара
     * 
     * @param array $data Данные товара
     * @return int ID созданного товара или false
     */
    public function create($data) {
        try {
            // Подробное логирование
            error_log("ProductModel::create вызван с данными: " . json_encode($data));
            
            // Добавляем метки времени
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Проверим обязательные поля
            $requiredFields = ['title', 'sku', 'price'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (count($missingFields) > 0) {
                error_log("ProductModel::create - отсутствуют обязательные поля: " . implode(', ', $missingFields));
                return false;
            }
            
            // Проверим, что статус допустим
            if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'])) {
                error_log("ProductModel::create - некорректный статус: " . $data['status'] . ", меняем на 'published'");
                $data['status'] = 'published';
            }
            
            // Извлекаем категории, если они есть
            $categories = [];
            if (isset($data['categories'])) {
                $categories = $data['categories'];
                unset($data['categories']);
                error_log("ProductModel::create - извлечены категории: " . json_encode($categories));
            }
            
            // Извлекаем галерею, если она есть
            $gallery = [];
            if (isset($data['gallery'])) {
                $gallery = $data['gallery'];
                unset($data['gallery']);
                error_log("ProductModel::create - извлечена галерея: " . json_encode($gallery));
            }
            
            // Фильтруем данные, оставляя только поля, существующие в таблице
            $filteredData = [];
            $columns = $this->db->fetchAll("SHOW COLUMNS FROM products");
            $columnNames = array_column($columns, 'Field');
            
            foreach ($data as $key => $value) {
                if (in_array($key, $columnNames)) {
                    $filteredData[$key] = $value;
                    error_log("ProductModel::create - поле $key добавлено");
                } else {
                    error_log("ProductModel::create - поле $key пропущено (не существует в таблице)");
                }
            }
            
            error_log("ProductModel::create - финальные данные для вставки: " . json_encode($filteredData));
            
            // Вставляем запись товара
            $productId = $this->db->insert('products', $filteredData);
            
            if (!$productId) {
                error_log("ProductModel::create - ошибка при вставке товара в БД");
                return false;
            }
            
            error_log("ProductModel::create - товар успешно создан с ID: " . $productId);
            
            // Добавляем связи с категориями
            if (!empty($categories)) {
                foreach ($categories as $categoryId) {
                    $this->db->insert('product_category', [
                        'product_id' => $productId,
                        'category_id' => $categoryId
                    ]);
                    error_log("ProductModel::create - добавлена связь с категорией: " . $categoryId);
                }
            }
            
            // Добавляем изображения в галерею
            if (!empty($gallery)) {
                foreach ($gallery as $index => $mediaId) {
                    $this->db->insert('product_media', [
                        'product_id' => $productId,
                        'media_id' => $mediaId,
                        'sort_order' => $index
                    ]);
                    error_log("ProductModel::create - добавлена связь с медиа: " . $mediaId);
                }
            }
            
            error_log("ProductModel::create - процесс создания товара завершен успешно, возвращаем ID: " . $productId);
            
            // Возвращаем ID созданного товара
            return $productId;
        } catch (\Exception $e) {
            error_log("ProductModel::create - ошибка: " . $e->getMessage());
            error_log("ProductModel::create - trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Обновление товара
     * 
     * @param int $id ID товара
     * @param array $data Данные для обновления
     * @return array Результат операции
     */
    public function update($id, $data) {
        try {
            // Подробное логирование
            error_log("ProductModel::update вызван для ID: " . json_encode($id) . " с данными: " . json_encode($data));
            
            // Проверяем существование товара
            $product = $this->getById($id, false);
            
            if (!$product) {
                error_log("ProductModel::update - товар с ID " . json_encode($id) . " не найден");
                return [
                    'success' => false,
                    'message' => 'Товар не найден'
                ];
            }
            
            // Добавляем метку времени обновления
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Извлекаем категории, если они есть
            $categories = null;
            if (isset($data['categories'])) {
                $categories = $data['categories'];
                unset($data['categories']);
                error_log("ProductModel::update - категории извлечены: " . json_encode($categories));
            }
            
            // Извлекаем галерею, если она есть
            $gallery = null;
            if (isset($data['gallery'])) {
                $gallery = $data['gallery'];
                unset($data['gallery']);
                error_log("ProductModel::update - галерея извлечена: " . json_encode($gallery));
            }
            
            // Фильтруем данные, оставляя только поля, существующие в таблице
            $filteredData = [];
            $columns = $this->db->fetchAll("SHOW COLUMNS FROM products");
            $columnNames = array_column($columns, 'Field');
            
            foreach ($data as $key => $value) {
                if (in_array($key, $columnNames)) {
                    $filteredData[$key] = $value;
                    error_log("ProductModel::update - поле $key добавлено в обновление");
                } else {
                    error_log("ProductModel::update - поле $key пропущено (не существует в таблице)");
                }
            }
            
            // Проверяем, остались ли данные для обновления
            if (empty($filteredData)) {
                error_log("ProductModel::update - нет данных для обновления");
                return [
                    'success' => false,
                    'message' => 'Нет данных для обновления'
                ];
            }
            
            // Обновляем данные товара
            $result = $this->db->update('products', $filteredData, "id = ?", [$id]);
            error_log("ProductModel::update - результат обновления: " . ($result ? 'успешно' : 'ошибка'));
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Ошибка при обновлении товара'
                ];
            }
            
            // Обновляем связи с категориями, если они переданы
            if ($categories !== null) {
                // Удаляем текущие связи
                $this->db->query("DELETE FROM product_category WHERE product_id = ?", [$id]);
                error_log("ProductModel::update - удалены старые связи с категориями");
                
                // Добавляем новые связи
                foreach ($categories as $categoryId) {
                    if (is_numeric($categoryId)) {
                        $this->db->insert('product_category', [
                            'product_id' => $id,
                            'category_id' => $categoryId
                        ]);
                        error_log("ProductModel::update - добавлена связь с категорией $categoryId");
                    } else {
                        error_log("ProductModel::update - некорректный ID категории: " . json_encode($categoryId));
                    }
                }
            }
            
            // Обновляем галерею, если она передана
            if ($gallery !== null) {
                // Удаляем текущие связи
                $this->db->query("DELETE FROM product_media WHERE product_id = ?", [$id]);
                error_log("ProductModel::update - удалены старые связи с медиа");
                
                // Добавляем новые связи
                foreach ($gallery as $index => $mediaId) {
                    if (is_numeric($mediaId)) {
                        $this->db->insert('product_media', [
                            'product_id' => $id,
                            'media_id' => $mediaId,
                            'sort_order' => $index
                        ]);
                        error_log("ProductModel::update - добавлена связь с медиа $mediaId");
                    } else {
                        error_log("ProductModel::update - некорректный ID медиа: " . json_encode($mediaId));
                    }
                }
            }
            
            error_log("ProductModel::update - товар успешно обновлен");
            return [
                'success' => true,
                'message' => 'Товар успешно обновлен',
                'data' => $this->getById($id)
            ];
        } catch (\Exception $e) {
            error_log("ProductModel::update - ошибка: " . $e->getMessage());
            error_log("ProductModel::update - трейс: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении товара: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление товара
     * 
     * @param int $id ID товара
     * @return array Результат операции
     */
    public function delete($id) {
        try {
            // Проверяем существование товара
            $product = $this->getById($id, false);
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Товар не найден'
                ];
            }
            
            // Удаляем связи с категориями
            $this->db->query("DELETE FROM product_category WHERE product_id = ?", [$id]);
            
            // Удаляем связи с медиа
            $this->db->query("DELETE FROM product_media WHERE product_id = ?", [$id]);
            
            // Удаляем товар
            $result = $this->db->query("DELETE FROM products WHERE id = ?", [$id]);
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Ошибка при удалении товара'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Товар успешно удален'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при удалении товара: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Поиск товаров
     * 
     * @param string $query Поисковый запрос
     * @param array $options Дополнительные параметры поиска
     * @return array Массив найденных товаров
     */
    public function search($query, $options = []) {
        $options['search'] = $query;
        return $this->getAll($options);
    }
    
    /**
     * Подсчет общего количества товаров
     * 
     * @param array $filters Фильтры для подсчета
     * @return int Количество товаров
     */
    public function count($filters = []) {
        // Формируем условие WHERE
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $where .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $where .= " AND id IN (SELECT product_id FROM product_category WHERE category_id = ?)";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (title LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (isset($filters['featured'])) {
            $where .= " AND featured = ?";
            $params[] = $filters['featured'] ? 1 : 0;
        }
        
        // Запрос для подсчета
        $query = "SELECT COUNT(*) as total FROM products WHERE {$where}";
        $result = $this->db->fetch($query, $params);
        
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Тестовый метод для диагностики проблем с выборкой товаров
     */
    public function debugQuery() {
        try {
            // Проверим наличие таблицы products
            $tableExists = $this->db->fetch("SHOW TABLES LIKE 'products'");
            if (!$tableExists) {
                return [
                    'error' => 'Таблица products не существует'
                ];
            }
            
            // Получим структуру таблицы
            $columns = $this->db->fetchAll("SHOW COLUMNS FROM products");
            
            // Подсчитаем товары и получим данные о статусах
            $totalCount = $this->db->fetch("SELECT COUNT(*) as total FROM products");
            $statusCounts = $this->db->fetchAll("SELECT status, COUNT(*) as count FROM products GROUP BY status");
            
            // Выполним простой запрос
            $simpleQuery = "SELECT * FROM products";
            $products = $this->db->fetchAll($simpleQuery);
            
            return [
                'table_exists' => true,
                'columns' => $columns,
                'total_count' => $totalCount['total'] ?? 0,
                'status_counts' => $statusCounts,
                'sample_data' => array_slice($products, 0, 3)
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
} 
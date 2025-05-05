<?php
namespace Modules\Shop\Controllers;

use API\Database;
use API\Response;
use API\Request;
use Modules\Shop\Models\ProductModel;

/**
 * Контроллер для API интернет-магазина
 */
class ShopController {
    private $db;
    private $productModel;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->productModel = new ProductModel();
    }
    
    /**
     * Получение списка товаров
     */
    public function getProducts(Request $request = null) {
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
            error_log("ShopController::getProducts создан новый объект Request");
        }
        
        // Добавляем логирование запроса
        error_log("ShopController::getProducts вызван с методом: " . $request->getMethod());
        error_log("ShopController::getProducts параметры запроса: " . json_encode($request->getParams()));
        
        // Определяем параметры для выборки товаров
        $options = [
            'limit' => $request->get('limit', 20),
            'offset' => $request->get('offset', 0),
            'search' => $request->get('search', ''),
            'category_id' => $request->get('category_id', null),
            'status' => 'published', // Изменено с 'published' на 'active'
            'withCategories' => true
        ];
        
        error_log("ShopController::getProducts параметры для выборки: " . json_encode($options));
        
        try {
            // Проверяем, инициализирована ли модель товаров
            if (!$this->productModel) {
                error_log("ShopController::getProducts - модель товаров не инициализирована, создаем новую");
                $this->productModel = new ProductModel();
            }
            
            // Добавляем отладочный запрос для проверки структуры БД
            $debugInfo = $this->productModel->debugQuery();
            error_log("Отладочная информация: " . json_encode($debugInfo));
            
            // Получаем товары
            $products = $this->productModel->getAll($options);
            $totalCount = $this->productModel->count($options);
            
            error_log("ShopController::getProducts найдено " . count($products) . " товаров из $totalCount всего");
            
            // Формируем ответ
            $response = [
                'success' => true,
                'data' => $products,
                'meta' => [
                    'total' => $totalCount,
                    'limit' => $options['limit'],
                    'offset' => $options['offset']
                ],
                'debug_info' => $debugInfo // Добавляем отладочную информацию
            ];
            
            error_log("ShopController::getProducts возвращает успешный ответ");
            
            // Если данные пустые, добавим дополнительную информацию в ответ
            if (empty($products)) {
                $response['debug_info']['message'] = 'Товары не найдены с указанными параметрами';
                error_log("ShopController::getProducts не найдено товаров с указанными параметрами");
            }
            
            return Response::json($response);
        } catch (\Exception $e) {
            error_log("ShopController::getProducts ошибка: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            
            return Response::error('Ошибка при получении списка товаров: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Получение товара по ID
     */
    public function getProduct(Request $request = null, $id = null) {
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
            error_log("ShopController::getProduct создан новый объект Request");
        }
        
        // Добавляем логирование для отладки
        error_log("ShopController::getProduct вызван с ID: " . ($id ?? 'null'));
        error_log("Request params: " . json_encode($request->getParams()));
        
        // Если id не передан как параметр, попробуем получить его из URL или параметров запроса
        if ($id === null) {
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                error_log("ID получен из _GET: " . $id);
            } elseif ($request->get('id')) {
                $id = $request->get('id');
                error_log("ID получен из request: " . $id);
            }
        }
        
        if ($id === null) {
            error_log("ShopController::getProduct - ID не указан");
            return Response::error('ID товара не указан', 400);
        }
        
        try {
            error_log("Получение товара с ID: " . $id);
            $product = $this->productModel->getById($id);
            
            if (!$product) {
                error_log("Товар с ID $id не найден");
                return Response::error('Товар не найден', 404);
            }
            
            error_log("Товар найден: " . json_encode($product));
            return Response::json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            error_log("Ошибка при получении товара: " . $e->getMessage());
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Создание товара
     */
    public function createProduct(Request $request = null) {
        // Включаем отслеживание ошибок
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        // Диагностический вывод для отладки
        error_log("ShopController::createProduct - начало выполнения метода");
        
        try {
            // Создаем объект Request, если он не был передан
            if ($request === null) {
                $request = new Request();
                error_log("ShopController::createProduct создан новый объект Request");
            }
            
            // ДИАГНОСТИКА: Проверяем соединение с базой данных
            try {
                $dbTest = $this->db->fetch("SELECT VERSION() as version");
                error_log("Соединение с БД работает. Версия MySQL: " . ($dbTest['version'] ?? 'unknown'));
            } catch (\Exception $e) {
                error_log("ОШИБКА: Проблема с соединением БД: " . $e->getMessage());
            }
            
            // Получаем данные из запроса напрямую из php://input
            $rawData = file_get_contents('php://input');
            error_log("ShopController::createProduct - сырые данные запроса: " . $rawData);
            
            // Пробуем декодировать JSON сами, без использования метода getJson
            $data = json_decode($rawData, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                error_log("ShopController::createProduct - ошибка декодирования JSON: " . json_last_error_msg());
                
                // Пробуем получить данные с помощью getJson
                $data = $request->getJson();
                error_log("ShopController::createProduct - данные из getJson: " . json_encode($data));
                
                // Если данные все еще пусты, попробуем получить их из POST
                if (empty($data) && !empty($_POST)) {
                    error_log("ShopController::createProduct - используем данные из $_POST");
                    $data = $_POST;
                }
            } else {
                error_log("ShopController::createProduct - JSON данные успешно декодированы напрямую");
            }
            
            // Проверка наличия данных
            if (empty($data)) {
                error_log("ShopController::createProduct - пустые данные");
                return Response::error('Пустые данные товара', 400);
            }
            
            // Проверяем структуру таблицы products
            try {
                $tableInfo = $this->db->fetchAll("SHOW CREATE TABLE products");
                error_log("Структура таблицы products: " . json_encode($tableInfo));
                
                $columns = $this->db->fetchAll("SHOW COLUMNS FROM products");
                error_log("Колонки таблицы products: " . json_encode($columns));
                
                // Преобразуем информацию о колонках в более удобный формат
                $columnInfo = [];
                foreach ($columns as $column) {
                    $columnInfo[$column['Field']] = [
                        'type' => $column['Type'],
                        'null' => $column['Null'],
                        'key' => $column['Key'],
                        'default' => $column['Default'],
                        'extra' => $column['Extra']
                    ];
                }
                
                error_log("Информация о колонках: " . json_encode($columnInfo));
            } catch (\Exception $e) {
                error_log("Ошибка при получении структуры таблицы: " . $e->getMessage());
            }
            
            // Базовые обязательные поля
            $requiredFields = ['title', 'price'];
            
            // Проверяем обязательные поля из структуры таблицы
            foreach ($columnInfo as $field => $info) {
                if ($info['null'] === 'NO' && $info['default'] === null && $field !== 'id') {
                    if (!in_array($field, $requiredFields)) {
                        $requiredFields[] = $field;
                    }
                }
            }
            
            error_log("Обязательные поля: " . json_encode($requiredFields));
            
            // Проверка обязательных полей
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== '0')) {
                    $missingFields[] = $field;
                }
            }
            
            // Если отсутствуют обязательные поля, добавляем значения по умолчанию или возвращаем ошибку
            if (!empty($missingFields)) {
                error_log("Отсутствуют обязательные поля: " . json_encode($missingFields));
                
                // Добавляем значения по умолчанию для некоторых полей
                foreach ($missingFields as $key => $field) {
                    if ($field === 'slug' && isset($data['title'])) {
                        $data['slug'] = $this->generateSlug($data['title']);
                        unset($missingFields[$key]);
                    } elseif ($field === 'author_id') {
                        $data['author_id'] = 1; // ID по умолчанию для автора
                        unset($missingFields[$key]);
                    } elseif ($field === 'status') {
                        $data['status'] = 'published';
                        unset($missingFields[$key]);
                    } elseif ($field === 'created_at' || $field === 'updated_at') {
                        $data[$field] = date('Y-m-d H:i:s');
                        unset($missingFields[$key]);
                    }
                }
                
                // Если все еще остаются отсутствующие поля, возвращаем ошибку
                if (!empty($missingFields)) {
                    $errorMsg = 'Отсутствуют обязательные поля: ' . implode(', ', $missingFields);
                    error_log("ShopController::createProduct - " . $errorMsg);
                    return Response::error($errorMsg, 400);
                }
            }
            
            // Устанавливаем значения по умолчанию для необязательных полей
            if (!isset($data['status'])) {
                $data['status'] = 'published';
            }
            
            if (!isset($data['stock'])) {
                $data['stock'] = 0;
            }
            
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            // Преобразуем поля в соответствии с их типами
            foreach ($data as $field => $value) {
                if (isset($columnInfo[$field])) {
                    $type = $columnInfo[$field]['type'];
                    
                    // Преобразование для числовых типов
                    if (strpos($type, 'int') !== false) {
                        $data[$field] = (int)$value;
                    } elseif (strpos($type, 'decimal') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false) {
                        $data[$field] = (float)$value;
                    } elseif (strpos($type, 'enum') !== false) {
                        // Для enum проверяем допустимые значения
                        preg_match('/enum\((.*)\)/', $type, $matches);
                        if (isset($matches[1])) {
                            $allowedValues = array_map(function($val) {
                                return trim($val, "'\"");
                            }, explode(',', $matches[1]));
                            
                            if (!in_array($value, $allowedValues)) {
                                error_log("Значение $value не допустимо для enum $field. Допустимые значения: " . implode(', ', $allowedValues));
                                $data[$field] = $allowedValues[0]; // Используем первое допустимое значение
                            }
                        }
                    }
                }
            }
            
            error_log("Данные после обработки: " . json_encode($data));
            
            try {
                // Сначала проверим, есть ли автоинкремент в поле id
                $idInfo = $columnInfo['id'] ?? null;
                $hasAutoIncrement = $idInfo && strpos($idInfo['extra'], 'auto_increment') !== false;
                
                if (!$hasAutoIncrement) {
                    error_log("ВНИМАНИЕ: Поле id не имеет AUTO_INCREMENT");
                    
                    // Если нет AUTO_INCREMENT, нужно найти максимальное id и увеличить его
                    if (!isset($data['id'])) {
                        $maxId = $this->db->fetch("SELECT MAX(id) as max_id FROM products");
                        $nextId = isset($maxId['max_id']) ? ((int)$maxId['max_id'] + 1) : 1;
                        $data['id'] = $nextId;
                        error_log("Назначаем ID вручную: " . $nextId);
                    }
                } else if (isset($data['id'])) {
                    // Если есть AUTO_INCREMENT, но ID передан - удаляем его
                    error_log("Удаляем переданный ID, так как таблица использует AUTO_INCREMENT");
                    unset($data['id']);
                }
                
                // Подготавливаем SQL-запрос
                $columns = array_keys($data);
                $placeholders = array_fill(0, count($columns), '?');
                
                $sql = "INSERT INTO products (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                error_log("SQL запрос: " . $sql);
                error_log("Значения: " . json_encode(array_values($data)));
                
                // Выполняем запрос
                $values = array_values($data);
                $this->db->query($sql, $values);
                
                // Получаем ID вставленной записи
                if ($hasAutoIncrement) {
                    $productId = $this->db->getConnection()->lastInsertId();
                } else {
                    $productId = $data['id'];
                }
                
                if (!$productId) {
                    error_log("Ошибка: Не удалось получить ID созданного товара");
                    return Response::error('Ошибка при создании товара: не удалось получить ID', 500);
                }
                
                error_log("Товар успешно создан с ID: " . $productId);
                
                // Получаем данные созданного товара
                $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'Товар успешно создан',
                    'data' => $product
                ]);
            } catch (\Exception $e) {
                error_log("Исключение при вставке записи: " . $e->getMessage());
                error_log("Трассировка: " . $e->getTraceAsString());
                return Response::error('Ошибка при создании товара в базе данных: ' . $e->getMessage(), 500);
            }
        } catch (\Exception $e) {
            error_log("Общее исключение в ShopController::createProduct: " . $e->getMessage());
            error_log("Трассировка: " . $e->getTraceAsString());
            return Response::error('Ошибка при обработке запроса: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Генерация slug из заголовка
     */
    private function generateSlug($title) {
        // Приводим к нижнему регистру
        $slug = mb_strtolower($title);
        
        // Заменяем кириллицу на латиницу
        $slug = $this->transliterate($slug);
        
        // Заменяем все символы, кроме букв и цифр, на дефисы
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Удаляем начальные и конечные дефисы
        $slug = trim($slug, '-');
        
        // Если slug пустой, используем timestamp
        if (empty($slug)) {
            $slug = 'product-' . time();
        }
        
        return $slug;
    }
    
    /**
     * Транслитерация кириллицы
     */
    private function transliterate($string) {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
        ];
        
        return strtr($string, $converter);
    }
    
    /**
     * Обновление товара
     */
    public function updateProduct(Request $request = null, $id = null, $data = null) {
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
            error_log("ShopController::updateProduct создан новый объект Request");
        }
        
        // Проверяем и преобразуем параметр ID
        if (is_object($id) && get_class($id) === 'API\\Request') {
            error_log("ShopController::updateProduct - Получен объект Request вместо ID, извлекаем ID из запроса");
            // Пытаемся извлечь ID из URL в объекте Request
            $id = null;
            
            // Извлекаем ID из URI
            $uri = $_SERVER['REQUEST_URI'];
            if (preg_match('#/shop/products/(\d+)#', $uri, $matches)) {
                $id = $matches[1];
                error_log("ShopController::updateProduct - ID извлечен из URI: " . json_encode($id));
            }
        }
        
        // Подробное логирование запроса
        error_log("ShopController::updateProduct вызван с ID: " . json_encode($id ?? 'null'));
        error_log("ShopController::updateProduct с методом: " . $request->getMethod());
        error_log("ShopController::updateProduct параметры запроса: " . json_encode($request->getParams()));
        error_log("ShopController::updateProduct URL: " . $_SERVER['REQUEST_URI']);
        
        // Если id не передан как параметр, попробуем получить его из URL
        if ($id === null) {
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                error_log("ID получен из _GET: " . json_encode($id));
            } elseif ($request->get('id')) {
                $id = $request->get('id');
                error_log("ID получен из request: " . json_encode($id));
            }
            
            // Попробуем извлечь ID из URI
            $uri = $_SERVER['REQUEST_URI'];
            if (preg_match('#/shop/products/(\d+)#', $uri, $matches)) {
                $id = $matches[1];
                error_log("ID извлечен из URI: " . json_encode($id));
            }
        }
        
        if ($id === null) {
            error_log("ShopController::updateProduct - ID не указан");
            return Response::error('ID товара не указан', 400);
        }
        
        // Убедимся, что ID - это число
        $id = intval($id);
        error_log("ShopController::updateProduct - Итоговый ID для поиска (преобразованный в int): " . json_encode($id));
        
        // Получаем данные из тела запроса или из переданного параметра
        if ($data === null) {
            $data = $request->getJson();
            error_log("Данные получены из тела запроса: " . json_encode($data));
        } else {
            error_log("Данные получены из параметра функции: " . json_encode($data));
        }
        
        try {
            // Проверяем базу данных
            $debugInfo = $this->productModel->debugQuery();
            error_log("Отладочная информация базы данных: " . json_encode($debugInfo));
            
            // Выполняем прямой запрос к БД для проверки
            $product_check = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);
            if ($product_check) {
                error_log("Прямой SQL-запрос нашел товар: " . json_encode($product_check));
            } else {
                error_log("Прямой SQL-запрос НЕ нашел товар с ID " . json_encode($id));
            }
            
            // Проверяем, существует ли товар через модель
            error_log("Проверка существования товара с ID: " . json_encode($id));
            $product = $this->productModel->getById($id);
            
            if (!$product) {
                error_log("Товар с ID " . json_encode($id) . " не найден через модель ProductModel");
                
                // Дополнительная информация о таблице
                $count = $this->db->fetch("SELECT COUNT(*) as cnt FROM products");
                error_log("Всего товаров в базе: " . ($count ? $count['cnt'] : 'ошибка запроса'));
                
                return Response::error('Товар не найден', 404);
            }
            
            error_log("Товар найден, обновляем...");
            
            // Подготавливаем и очищаем данные перед обновлением
            // Устанавливаем правильное значение статуса, если оно передано
            if (isset($data['status'])) {
                // Проверяем, что статус допустим (enum в базе данных: 'draft', 'published', 'archived', 'active')
                if (!in_array($data['status'], ['draft', 'published', 'archived'])) {
                    error_log("Некорректный статус: " . json_encode($data['status']) . ", устанавливаем 'published'");
                    $data['status'] = 'published';
                }
            }
            
            // Обновляем товар
            $result = $this->productModel->update($id, $data);
            
            if (!$result['success']) {
                error_log("Ошибка при обновлении товара: " . json_encode($result['message']));
                return Response::error($result['message'], 400);
            }
            
            error_log("Товар успешно обновлен");
            return Response::json([
                'success' => true,
                'message' => $result['message'],
                'data' => $this->productModel->getById($id) // Возвращаем обновленные данные товара
            ]);
        } catch (\Exception $e) {
            error_log("Исключение при обновлении товара: " . $e->getMessage());
            error_log("Трейс: " . $e->getTraceAsString());
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Удаление товара
     */
    public function deleteProduct(Request $request = null, $id = null) {
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
        }
        
        // Если id не передан как параметр, попробуем получить его из URL
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if ($id === null) {
            return Response::error('ID товара не указан', 400);
        }
        
        try {
            $product = $this->productModel->getById($id);
            
            if (!$product) {
                return Response::error('Товар не найден', 404);
            }
            
            $result = $this->productModel->delete($id);
            
            if (!$result['success']) {
                return Response::error($result['message'], 400);
            }
            
            return Response::json([
                'success' => true,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Получение списка категорий
     */
    public function getCategories(Request $request = null) {
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
        }
        
        // Добавляем логирование запроса
        error_log("ShopController::getCategories called with params: " . json_encode($request->getParams()));
        
        try {
            $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM product_category pc WHERE pc.category_id = c.id) as products_count 
                     FROM product_categories c ORDER BY c.name ASC";
            
            $categories = $this->db->fetchAll($query);
            
            error_log("ShopController::getCategories found " . count($categories) . " categories");
            
            return Response::json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            error_log("ShopController::getCategories error: " . $e->getMessage());
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Получение категории по ID
     */
    public function getCategory(Request $request = null, $id = null) {
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
        }
        
        // Если id не передан как параметр, попробуем получить его из URL
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if ($id === null) {
            return Response::error('ID категории не указан', 400);
        }
        
        try {
            $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM product_category pc WHERE pc.category_id = c.id) as products_count 
                     FROM product_categories c WHERE c.id = ? LIMIT 1";
            
            $category = $this->db->fetch($query, [$id]);
            
            if (!$category) {
                return Response::error('Категория не найдена', 404);
            }
            
            return Response::json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Создание категории
     */
    public function createCategory(Request $request) {
        $data = $request->getJson();
        
        // Проверка обязательных полей
        $requiredFields = ['name', 'slug'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return Response::error("Поле {$field} обязательно", 400);
            }
        }
        
        try {
            // Проверка уникальности slug
            $checkQuery = "SELECT id FROM product_categories WHERE slug = ? LIMIT 1";
            $existing = $this->db->fetch($checkQuery, [$data['slug']]);
            
            if ($existing) {
                return Response::error("Категория с URL {$data['slug']} уже существует", 400);
            }
            
            // Добавление метки времени
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Вставка в базу данных
            $id = $this->db->insert('product_categories', $data);
            
            if (!$id) {
                return Response::error('Ошибка при создании категории', 500);
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Категория успешно создана',
                'data' => ['id' => $id]
            ], 201);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Обновление категории
     */
    public function updateCategory(Request $request, $id) {
        $data = $request->getJson();
        
        try {
            // Логирование входящих данных для отладки
            error_log("ShopController::updateCategory - ID: {$id}, данные: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Проверка существования категории
            $checkQuery = "SELECT id FROM product_categories WHERE id = ? LIMIT 1";
            $existing = $this->db->fetch($checkQuery, [$id]);
            
            if (!$existing) {
                return Response::error('Категория не найдена', 404);
            }
            
            // Проверка уникальности slug, если он был изменен
            if (isset($data['slug'])) {
                $checkSlugQuery = "SELECT id FROM product_categories WHERE slug = ? AND id != ? LIMIT 1";
                $existingSlug = $this->db->fetch($checkSlugQuery, [$data['slug'], $id]);
                
                if ($existingSlug) {
                    return Response::error("Категория с URL {$data['slug']} уже существует", 400);
                }
            }
            
            // Обработка image (ранее было image_id)
            if (isset($data['image']) && is_array($data['image'])) {
                // Если передан массив, берем только URL изображения
                if (isset($data['image']['url'])) {
                    $data['image'] = $data['image']['url'];
                } else {
                    $data['image'] = null;
                }
            }
            
            // Добавление метки времени
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Обновление в базе данных
            $updated = $this->db->update('product_categories', $data, 'id = ?', [$id]);
            
            if (!$updated) {
                return Response::error('Ошибка при обновлении категории', 500);
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Категория успешно обновлена'
            ]);
        } catch (\Exception $e) {
            error_log("ShopController::updateCategory - ошибка: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Удаление категории
     */
    public function deleteCategory(Request $request, $id) {
        try {
            // Проверка существования категории
            $checkQuery = "SELECT id FROM product_categories WHERE id = ? LIMIT 1";
            $existing = $this->db->fetch($checkQuery, [$id]);
            
            if (!$existing) {
                return Response::error('Категория не найдена', 404);
            }
            
            // Проверка наличия дочерних категорий
            $checkChildrenQuery = "SELECT id FROM product_categories WHERE parent_id = ? LIMIT 1";
            $hasChildren = $this->db->fetch($checkChildrenQuery, [$id]);
            
            if ($hasChildren) {
                return Response::error('Невозможно удалить категорию, содержащую подкатегории', 400);
            }
            
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Удаляем связи с товарами
            $this->db->delete('product_category', ['category_id' => $id]);
            
            // Удаляем саму категорию
            $deleted = $this->db->delete('product_categories', ['id' => $id]);
            
            if (!$deleted) {
                $this->db->rollback();
                return Response::error('Ошибка при удалении категории', 500);
            }
            
            // Фиксируем транзакцию
            $this->db->commit();
            
            return Response::json([
                'success' => true,
                'message' => 'Категория успешно удалена'
            ]);
        } catch (\Exception $e) {
            // Откатываем транзакцию в случае ошибки
            $this->db->rollback();
            return Response::error($e->getMessage(), 500);
        }
    }
} 
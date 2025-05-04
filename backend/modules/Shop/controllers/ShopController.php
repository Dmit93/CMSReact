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
        // Диагностический вывод для отладки
        error_log("ShopController::createProduct - начало выполнения метода");
        error_log("Тип Request: " . (is_object($request) ? get_class($request) : gettype($request)));
        
        // Создаем объект Request, если он не был передан
        if ($request === null) {
            $request = new Request();
            error_log("ShopController::createProduct создан новый объект Request");
        }
        
        // Получаем данные из запроса
        $data = $request->getJson();
        error_log("ShopController::createProduct получены данные: " . json_encode($data));
        
        // Проверка наличия данных
        if (!$data || empty($data)) {
            error_log("ShopController::createProduct - пустые данные");
            return Response::error('Пустые данные товара', 400);
        }
        
        // Проверка обязательных полей
        $requiredFields = ['title', 'sku', 'price'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            $errorMsg = 'Отсутствуют обязательные поля: ' . implode(', ', $missingFields);
            error_log("ShopController::createProduct - " . $errorMsg);
            return Response::error($errorMsg, 400);
        }
        
        try {
            // Устанавливаем значения по умолчанию для необязательных полей
            if (!isset($data['status']) || empty($data['status'])) {
                $data['status'] = 'published';
            }
            
            if (!isset($data['stock'])) {
                $data['stock'] = 0;
            }
            
            // Специальная проверка для enum поля status
            if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'])) {
                error_log("ShopController::createProduct - некорректный статус: {$data['status']}, устанавливаем 'published'");
                $data['status'] = 'published';
            }
            
            // Создаем товар через модель
            $result = $this->productModel->create($data);
            
            // Проверка результата операции
            if (!$result || !is_numeric($result)) {
                error_log("ShopController::createProduct - ошибка создания товара: " . json_encode($result));
                return Response::error('Ошибка при создании товара', 500);
            }
            
            error_log("ShopController::createProduct - товар успешно создан с ID: {$result}");
            
            // Получаем данные созданного товара
            $product = $this->productModel->getById($result);
            
            return Response::json([
                'success' => true,
                'message' => 'Товар успешно создан',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            error_log("ShopController::createProduct - исключение: " . $e->getMessage());
            error_log("ShopController::createProduct - trace: " . $e->getTraceAsString());
            return Response::error('Ошибка при создании товара: ' . $e->getMessage(), 500);
        }
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
            
            // Добавление метки времени
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Обновление в базе данных
            $updated = $this->db->update('product_categories', $data, ['id' => $id]);
            
            if (!$updated) {
                return Response::error('Ошибка при обновлении категории', 500);
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Категория успешно обновлена'
            ]);
        } catch (\Exception $e) {
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
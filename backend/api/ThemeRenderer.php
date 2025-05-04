<?php
namespace API;

/**
 * Класс для управления рендерингом тем и предоставления API для шаблонов
 */
class ThemeRenderer {
    /**
     * @var \API\Database Инстанс базы данных
     */
    private $db;
    
    /**
     * @var array Глобальные данные для всех шаблонов
     */
    private $data = [];
    
    /**
     * @var string Активная тема
     */
    private $activeTheme = 'default';
    
    /**
     * @var string Путь к директории темы
     */
    private $themePath;
    
    /**
     * @var array Конфигурация темы
     */
    private $themeConfig = [];
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadActiveTheme();
        $this->loadSiteSettings();
    }
    
    /**
     * Загружает активную тему
     */
    private function loadActiveTheme() {
        $themeQuery = "SELECT value FROM settings WHERE name = 'active_theme'";
        $themeResult = $this->db->fetch($themeQuery);
        $this->activeTheme = $themeResult && isset($themeResult['value']) ? $themeResult['value'] : 'default';
        
        // Проверяем существование директории темы
        $this->themePath = ROOT_DIR . '/backend/themes/' . $this->activeTheme;
        if (!is_dir($this->themePath)) {
            // Если директория темы не существует, используем тему по умолчанию
            $this->activeTheme = 'default';
            $this->themePath = ROOT_DIR . '/backend/themes/default';
        }
        
        // Загружаем конфигурацию темы
        $themeConfigFile = $this->themePath . '/theme.json';
        if (file_exists($themeConfigFile)) {
            $this->themeConfig = json_decode(file_get_contents($themeConfigFile), true) ?: [];
        }
        
        // Добавляем данные темы в глобальные данные
        $this->data['active_theme'] = $this->activeTheme;
        $this->data['theme_path'] = '/cms/backend/themes/' . $this->activeTheme;
        $this->data['theme_config'] = $this->themeConfig;
    }
    
    /**
     * Загружает основные настройки сайта
     */
    private function loadSiteSettings() {
        $settingsQuery = "SELECT name, value FROM settings WHERE name IN ('site_title', 'site_description')";
        $settingsResult = $this->db->fetchAll($settingsQuery);
        
        $settings = [];
        foreach ($settingsResult as $row) {
            $settings[$row['name']] = $row['value'];
        }
        
        $this->data['site_title'] = $settings['site_title'] ?? 'Universal CMS';
        $this->data['site_description'] = $settings['site_description'] ?? 'Описание сайта на базе CMS';
        
        // Загружаем страницы для меню
        $pagesQuery = "SELECT id, title, slug FROM content WHERE type = 'page' AND status = 'published'";
        $this->data['pages'] = $this->db->fetchAll($pagesQuery) ?: [];
    }
    
    /**
     * Устанавливает тему для предпросмотра
     * 
     * @param string $theme Имя темы для предпросмотра
     * @return bool Успешность установки темы
     */
    public function setPreviewTheme($theme) {
        $previewThemePath = ROOT_DIR . '/backend/themes/' . $theme;
        if (is_dir($previewThemePath)) {
            $this->activeTheme = $theme;
            $this->themePath = $previewThemePath;
            
            // Перезагружаем конфигурацию темы
            $themeConfigFile = $this->themePath . '/theme.json';
            if (file_exists($themeConfigFile)) {
                $this->themeConfig = json_decode(file_get_contents($themeConfigFile), true) ?: [];
            }
            
            // Обновляем данные темы
            $this->data['active_theme'] = $this->activeTheme;
            $this->data['theme_path'] = '/cms/backend/themes/' . $this->activeTheme;
            $this->data['theme_config'] = $this->themeConfig;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Добавляет данные для шаблона
     * 
     * @param string $key Ключ данных
     * @param mixed $value Значение
     */
    public function addData($key, $value) {
        $this->data[$key] = $value;
    }
    
    /**
     * Добавляет массив данных для шаблона
     * 
     * @param array $data Массив данных
     */
    public function addDataArray($data) {
        $this->data = array_merge($this->data, $data);
    }
    
    /**
     * Получает данные из определенного типа контента
     * 
     * @param string $type Системное имя типа контента
     * @param array $options Дополнительные опции запроса
     * @return array Массив записей
     */
    public function getContent($type, $options = []) {
        // Настройки по умолчанию
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'order' => 'id DESC',
            'where' => "status = 'published'",
            'fields' => '*'
        ];
        
        // Объединяем опции по умолчанию с переданными опциями
        $options = array_merge($defaults, $options);
        
        // Формируем запрос
        $query = "SELECT {$options['fields']} FROM content 
                 WHERE type = '{$type}' AND {$options['where']} 
                 ORDER BY {$options['order']} 
                 LIMIT {$options['limit']} OFFSET {$options['offset']}";
        
        return $this->db->fetchAll($query) ?: [];
    }
    
    /**
     * Получает одну запись контента по slug или ID
     * 
     * @param string|int $identifier Slug или ID записи
     * @param string $type Системное имя типа контента (опционально)
     * @return array|null Запись или null, если не найдена
     */
    public function getSingleContent($identifier, $type = null) {
        $whereType = $type ? "AND type = '{$type}'" : "";
        
        // Если идентификатор - число, ищем по ID, иначе - по slug
        if (is_numeric($identifier)) {
            $query = "SELECT * FROM content WHERE id = {$identifier} {$whereType} LIMIT 1";
        } else {
            $query = "SELECT * FROM content WHERE slug = '{$identifier}' {$whereType} LIMIT 1";
        }
        
        return $this->db->fetch($query);
    }
    
    /**
     * Рендерит шаблон темы
     * 
     * @param string $template Имя шаблона (без расширения .php)
     * @param array $data Дополнительные данные для шаблона
     */
    public function render($template = 'index', $data = []) {
        // Объединяем глобальные данные с переданными данными
        $mergedData = array_merge($this->data, $data);
        
        // Определяем путь к файлу шаблона
        $templateFile = $this->themePath . '/templates/' . $template . '.php';
        
        // Проверяем существование файла шаблона
        if (!file_exists($templateFile)) {
            // Если файл шаблона не существует, используем шаблон index.php
            $templateFile = $this->themePath . '/templates/index.php';
            
            // Если и его нет, показываем ошибку
            if (!file_exists($templateFile)) {
                echo "Шаблон {$template}.php не найден в теме {$this->activeTheme}";
                return;
            }
        }
        
        // Извлекаем переменные из массива данных, чтобы они были доступны в шаблоне
        extract($mergedData);
        
        // Включаем шаблон
        include $templateFile;
    }
    
    /**
     * Получает список товаров из модуля магазина
     * 
     * @param array $options Параметры выборки
     * @return array Массив товаров
     */
    public function getProducts($options = []) {
        // Проверяем, активирован ли модуль магазина
        $moduleQuery = "SELECT id FROM modules WHERE slug = 'shop' AND status = 'active' LIMIT 1";
        $moduleResult = $this->db->fetch($moduleQuery);
        
        if (!$moduleResult) {
            return [];
        }
        
        // Настройки по умолчанию
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'order' => 'created_at DESC',
            'status' => 'published'
        ];
        
        // Объединяем с переданными параметрами
        $options = array_merge($defaults, $options);
        
        // Формируем условие WHERE
        $where = "status = ?";
        $params = [$options['status']];
        
        // Добавляем фильтр по категории, если он указан
        if (!empty($options['category_id'])) {
            $where .= " AND id IN (SELECT product_id FROM product_category WHERE category_id = ?)";
            $params[] = $options['category_id'];
        }
        
        // Добавляем фильтр по наличию скидки
        if (isset($options['has_sale']) && $options['has_sale']) {
            $where .= " AND sale_price IS NOT NULL AND sale_price > 0";
        }
        
        // Добавляем фильтр по минимальной цене
        if (isset($options['price_min']) && is_numeric($options['price_min'])) {
            $where .= " AND price >= ?";
            $params[] = $options['price_min'];
        }
        
        // Добавляем фильтр по максимальной цене
        if (isset($options['price_max']) && is_numeric($options['price_max'])) {
            $where .= " AND price <= ?";
            $params[] = $options['price_max'];
        }
        
        // Добавляем фильтр по наличию
        if (isset($options['in_stock']) && $options['in_stock']) {
            $where .= " AND stock > 0";
        }
        
        // Добавляем поиск по тексту
        if (isset($options['search']) && !empty($options['search'])) {
            $where .= " AND (title LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $searchTerm = '%' . $options['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Формируем запрос
        $query = "SELECT * FROM products WHERE {$where} ORDER BY {$options['order']} LIMIT ? OFFSET ?";
        $params[] = (int)$options['limit'];
        $params[] = (int)$options['offset'];
        
        return $this->db->fetchAll($query, $params) ?: [];
    }
    
    /**
     * Получает данные товара по ID
     * 
     * @param int $id ID товара
     * @return array|null Данные товара или null, если не найден
     */
    public function getProduct($id) {
        // Проверяем, активирован ли модуль магазина
        $moduleQuery = "SELECT id FROM modules WHERE slug = 'shop' AND status = 'active' LIMIT 1";
        $moduleResult = $this->db->fetch($moduleQuery);
        
        if (!$moduleResult) {
            return null;
        }
        
        $query = "SELECT * FROM products WHERE id = ? LIMIT 1";
        $product = $this->db->fetch($query, [(int)$id]);
        
        if (!$product) {
            return null;
        }
        
        // Загружаем категории товара
        $categoryQuery = "
            SELECT c.* 
            FROM product_category pc
            JOIN product_categories c ON pc.category_id = c.id
            WHERE pc.product_id = ?
        ";
        $product['categories'] = $this->db->fetchAll($categoryQuery, [(int)$id]) ?: [];
        
        // Загружаем галерею изображений
        $mediaQuery = "
            SELECT m.* 
            FROM product_media pm
            JOIN media m ON pm.media_id = m.id
            WHERE pm.product_id = ?
            ORDER BY pm.sort_order
        ";
        $product['gallery'] = $this->db->fetchAll($mediaQuery, [(int)$id]) ?: [];
        
        return $product;
    }
    
    /**
     * Получает данные товара по slug
     * 
     * @param string $slug Slug товара
     * @return array|null Данные товара или null, если не найден
     */
    public function getProductBySlug($slug) {
        // Проверяем, активирован ли модуль магазина
        $moduleQuery = "SELECT id FROM modules WHERE slug = 'shop' AND status = 'active' LIMIT 1";
        $moduleResult = $this->db->fetch($moduleQuery);
        
        if (!$moduleResult) {
            return null;
        }
        
        $query = "SELECT id FROM products WHERE slug = ? LIMIT 1";
        $result = $this->db->fetch($query, [$slug]);
        
        if (!$result) {
            return null;
        }
        
        return $this->getProduct($result['id']);
    }
    
    /**
     * Получает категории товаров
     * 
     * @param int|null $parentId ID родительской категории (null для корневых категорий)
     * @return array Массив категорий
     */
    public function getCategories($parentId = null) {
        // Проверяем, активирован ли модуль магазина
        $moduleQuery = "SELECT id FROM modules WHERE slug = 'shop' AND status = 'active' LIMIT 1";
        $moduleResult = $this->db->fetch($moduleQuery);
        
        if (!$moduleResult) {
            return [];
        }
        
        $where = $parentId === null ? "parent_id IS NULL" : "parent_id = ?";
        $params = $parentId === null ? [] : [(int)$parentId];
        
        $query = "SELECT * FROM product_categories WHERE {$where} ORDER BY name";
        return $this->db->fetchAll($query, $params) ?: [];
    }
    
    /**
     * Получает содержимое корзины текущего пользователя
     * 
     * @return array Данные корзины
     */
    public function getCartContents() {
        // Проверяем, активирован ли модуль магазина
        $moduleQuery = "SELECT id FROM modules WHERE slug = 'shop' AND status = 'active' LIMIT 1";
        $moduleResult = $this->db->fetch($moduleQuery);
        
        if (!$moduleResult) {
            return ['items' => [], 'total' => 0];
        }
        
        // Получаем ID корзины из сессии
        $cartId = $_SESSION['cart_id'] ?? null;
        
        if (!$cartId) {
            return ['items' => [], 'total' => 0];
        }
        
        // Получаем элементы корзины
        $query = "
            SELECT ci.*, p.title, p.slug, p.sku, p.featured_image, p.price, p.sale_price
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
        ";
        $items = $this->db->fetchAll($query, [(int)$cartId]) ?: [];
        
        // Рассчитываем общую сумму
        $total = 0;
        foreach ($items as &$item) {
            $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
            $item['price'] = $price;
            $total += $price * $item['quantity'];
            
            // Преобразуем товар для удобства использования в шаблоне
            $item['product'] = [
                'id' => $item['product_id'],
                'title' => $item['title'],
                'slug' => $item['slug'],
                'sku' => $item['sku'],
                'featured_image' => $item['featured_image'],
                'price' => $item['price'],
                'sale_price' => $item['sale_price']
            ];
        }
        
        return [
            'items' => $items,
            'total' => $total,
            'item_count' => count($items)
        ];
    }
} 
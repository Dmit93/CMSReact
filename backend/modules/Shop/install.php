<?php
namespace Modules\Shop;

use API\Database;
use Core\Logger;

/**
 * Класс установки модуля Shop
 */
class Install {
    /**
     * Экземпляр базы данных
     */
    protected $db;
    
    /**
     * Конструктор
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Метод установки модуля
     * 
     * @return bool Результат установки
     */
    public function install() {
        try {
            // Создаем записи для примера категорий
            $this->createSampleCategories();
            
            // Создаем записи для примера товаров
            $this->createSampleProducts();
            
            // Логируем успешную установку
            Logger::getInstance()->info('Shop module successfully installed');
            
            return true;
        } catch (\Exception $e) {
            Logger::getInstance()->error('Error during shop module installation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создание примеров категорий
     */
    private function createSampleCategories() {
        $now = date('Y-m-d H:i:s');
        
        $categories = [
            [
                'name' => 'Электроника',
                'slug' => 'electronics',
                'description' => 'Компьютеры, смартфоны, планшеты и аксессуары',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Одежда',
                'slug' => 'clothing',
                'description' => 'Мужская и женская одежда, обувь и аксессуары',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Книги',
                'slug' => 'books',
                'description' => 'Художественная и учебная литература, журналы',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        
        foreach ($categories as $category) {
            $this->db->insert('product_categories', $category);
        }
    }
    
    /**
     * Создание примеров товаров
     */
    private function createSampleProducts() {
        $now = date('Y-m-d H:i:s');
        
        // Получаем ID категорий
        $categoriesQuery = "SELECT id, slug FROM product_categories LIMIT 3";
        $categories = $this->db->fetchAll($categoriesQuery);
        
        if (empty($categories)) {
            return;
        }
        
        // Индексируем категории по slug для удобства
        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[$category['slug']] = $category['id'];
        }
        
        // Примеры товаров
        $products = [
            [
                'title' => 'Смартфон XYZ Pro',
                'slug' => 'smartphone-xyz-pro',
                'description' => 'Мощный смартфон с большим экраном и отличной камерой',
                'content' => '<p>Характеристики:</p><ul><li>Процессор: Octa-core</li><li>RAM: 8GB</li><li>Экран: 6.5"</li><li>Камера: 48MP</li></ul>',
                'price' => 29999.00,
                'sale_price' => 27999.00,
                'sku' => 'SP-XYZ-001',
                'stock' => 25,
                'status' => 'published',
                'featured' => true,
                'meta_title' => 'Смартфон XYZ Pro - Купить',
                'meta_description' => 'Купить смартфон XYZ Pro с доставкой. Гарантия качества.',
                'author_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $now,
                'category_id' => $categoryIds['electronics'] ?? null
            ],
            [
                'title' => 'Футболка Classic Fit',
                'slug' => 'tshirt-classic-fit',
                'description' => 'Классическая футболка из 100% хлопка',
                'content' => '<p>Особенности:</p><ul><li>Материал: 100% хлопок</li><li>Размеры: S, M, L, XL</li><li>Цвета: белый, черный, серый</li></ul>',
                'price' => 1200.00,
                'sale_price' => null,
                'sku' => 'CF-TSH-001',
                'stock' => 100,
                'status' => 'published',
                'featured' => false,
                'meta_title' => 'Футболка Classic Fit - Купить',
                'meta_description' => 'Купить футболку Classic Fit с доставкой. Высокое качество.',
                'author_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $now,
                'category_id' => $categoryIds['clothing'] ?? null
            ],
            [
                'title' => 'Роман "Великий Гэтсби"',
                'slug' => 'great-gatsby',
                'description' => 'Знаменитый роман Ф. Скотта Фицджеральда',
                'content' => '<p>О книге:</p><p>Классический роман американской литературы, написанный Фрэнсисом Скоттом Фицджеральдом и опубликованный в 1925 году.</p>',
                'price' => 750.00,
                'sale_price' => 650.00,
                'sku' => 'BK-GG-001',
                'stock' => 50,
                'status' => 'published',
                'featured' => true,
                'meta_title' => 'Роман "Великий Гэтсби" - Купить',
                'meta_description' => 'Купить роман "Великий Гэтсби" Ф. Скотта Фицджеральда с доставкой.',
                'author_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $now,
                'category_id' => $categoryIds['books'] ?? null
            ]
        ];
        
        // Добавляем товары
        foreach ($products as $product) {
            $categoryId = $product['category_id'];
            unset($product['category_id']);
            
            $productId = $this->db->insert('products', $product);
            
            if ($productId && $categoryId) {
                // Связываем товар с категорией
                $this->db->insert('product_category', [
                    'product_id' => $productId,
                    'category_id' => $categoryId
                ]);
            }
        }
    }
} 
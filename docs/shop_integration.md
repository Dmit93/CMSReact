# Интеграция модуля интернет-магазина с темами

Данная документация описывает, как интегрировать модуль интернет-магазина с темами в CMS.

## Обзор

Модуль интернет-магазина предоставляет функциональность для:
- Отображения каталога товаров
- Отображения отдельных товаров
- Фильтрации и поиска товаров
- Корзины покупок
- Оформления заказов

## Основные шаблоны

В директории темы вы можете создать следующие шаблоны для работы с магазином:

| Шаблон | Описание |
|--------|----------|
| `shop.php` | Основной шаблон для отображения каталога товаров |
| `product.php` | Шаблон для отображения отдельного товара |
| `category.php` | Шаблон для отображения категории товаров |
| `cart.php` | Шаблон для отображения корзины |
| `checkout.php` | Шаблон для оформления заказа |

## Переменные и данные

В шаблонах магазина доступны следующие переменные:

| Переменная | Доступность | Описание |
|------------|-------------|----------|
| `$shop_enabled` | Все шаблоны | `true`, если модуль магазина активирован |
| `$shop_config` | Все шаблоны | Массив с настройками магазина |
| `$products` | shop.php, category.php | Массив товаров |
| `$product` | product.php | Массив данных текущего товара |
| `$categories` | Все шаблоны | Массив категорий товаров |
| `$cart` | Все шаблоны | Объект с данными корзины |
| `$pagination` | shop.php, category.php | Данные для пагинации |

## Примеры использования

### Отображение списка товаров

```php
<?php if ($shop_enabled && !empty($products)): ?>
    <div class="products-grid">
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($product['featured_image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['featured_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                            
                            <div class="price mb-3">
                                <?php if ($product['sale_price']): ?>
                                    <span class="text-muted text-decoration-line-through"><?php echo number_format($product['price'], 2); ?> <?php echo $shop_config['currency_symbol']; ?></span>
                                    <span class="text-danger"><?php echo number_format($product['sale_price'], 2); ?> <?php echo $shop_config['currency_symbol']; ?></span>
                                <?php else: ?>
                                    <span><?php echo number_format($product['price'], 2); ?> <?php echo $shop_config['currency_symbol']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            
                            <div class="d-flex justify-content-between">
                                <a href="/product/<?php echo htmlspecialchars($product['slug']); ?>" class="btn btn-outline-primary">Подробнее</a>
                                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">В корзину</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <p>Товары не найдены.</p>
    </div>
<?php endif; ?>
```

### Отображение корзины

```php
<?php if ($shop_enabled && isset($cart) && !empty($cart['items'])): ?>
    <div class="cart-table">
        <h2>Корзина</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart['items'] as $item): ?>
                    <tr>
                        <td>
                            <a href="/product/<?php echo htmlspecialchars($item['product']['slug']); ?>">
                                <?php echo htmlspecialchars($item['product']['title']); ?>
                            </a>
                        </td>
                        <td><?php echo number_format($item['price'], 2); ?> <?php echo $shop_config['currency_symbol']; ?></td>
                        <td>
                            <input type="number" class="form-control quantity-input" 
                                   data-product-id="<?php echo $item['product_id']; ?>" 
                                   value="<?php echo $item['quantity']; ?>" min="1" max="99">
                        </td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> <?php echo $shop_config['currency_symbol']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger remove-from-cart" 
                                    data-product-id="<?php echo $item['product_id']; ?>">
                                Удалить
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Итого:</strong></td>
                    <td><?php echo number_format($cart['total'], 2); ?> <?php echo $shop_config['currency_symbol']; ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="d-flex justify-content-between">
            <a href="/shop" class="btn btn-outline-secondary">Продолжить покупки</a>
            <a href="/checkout" class="btn btn-success">Оформить заказ</a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <p>Ваша корзина пуста.</p>
        <a href="/shop" class="btn btn-primary mt-3">Перейти в магазин</a>
    </div>
<?php endif; ?>
```

## События и хуки

Модуль магазина предоставляет следующие события (хуки), которые можно использовать в темах:

| Событие | Описание |
|---------|----------|
| `shop.product.view` | Срабатывает при просмотре товара |
| `shop.cart.add` | Срабатывает при добавлении товара в корзину |
| `shop.cart.update` | Срабатывает при обновлении товара в корзине |
| `shop.cart.remove` | Срабатывает при удалении товара из корзины |
| `shop.order.create` | Срабатывает при создании заказа |

## Функции API

Для взаимодействия с магазином доступны следующие API-функции:

| Функция | Описание |
|---------|----------|
| `$themeRenderer->getProducts($options)` | Получение списка товаров |
| `$themeRenderer->getProduct($id)` | Получение данных товара по ID |
| `$themeRenderer->getProductBySlug($slug)` | Получение данных товара по slug |
| `$themeRenderer->getCategories($parent_id = null)` | Получение категорий товаров |
| `$themeRenderer->getCartContents()` | Получение содержимого корзины |

Пример использования:

```php
<?php
// Получаем последние 5 товаров со скидкой
$saleProducts = $themeRenderer->getProducts([
    'limit' => 5,
    'has_sale' => true,
    'order' => 'created_at DESC'
]);

// Выводим товары
foreach ($saleProducts as $product) {
    echo '<h2>' . htmlspecialchars($product['title']) . '</h2>';
    echo '<p>Цена: <s>' . number_format($product['price'], 2) . '</s> ' . number_format($product['sale_price'], 2) . ' ' . $shop_config['currency_symbol'] . '</p>';
}
?>
```

## Настройка маршрутов

Для интеграции с темой модуль магазина использует следующие URL-маршруты:

| URL | Шаблон | Описание |
|-----|--------|----------|
| `/shop` | shop.php | Главная страница магазина |
| `/shop/category/{slug}` | category.php | Страница категории |
| `/shop/product/{slug}` | product.php | Страница товара |
| `/shop/cart` | cart.php | Страница корзины |
| `/shop/checkout` | checkout.php | Страница оформления заказа |
| `/shop/order/{id}` | order.php | Страница заказа |

## Настройка виджетов

Модуль магазина предоставляет следующие виджеты для использования в темах:

| Виджет | Описание |
|--------|----------|
| `shop_categories` | Виджет с деревом категорий товаров |
| `shop_featured` | Виджет с рекомендуемыми товарами |
| `shop_cart_summary` | Виджет с кратким содержимым корзины |
| `shop_search` | Виджет поиска по товарам | 
<?php
// Начинаем буферизацию вывода
ob_start();
?>

<div class="shop-container">
    <div class="row">
        <!-- Боковая панель с категориями -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">Категории товаров</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($categories) && !empty($categories)): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($categories as $category): ?>
                                <li class="mb-2">
                                    <a href="/shop/category/<?php echo htmlspecialchars($category['slug']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Нет доступных категорий</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="h5 mb-0">Фильтры</h3>
                </div>
                <div class="card-body">
                    <form action="/shop" method="get" id="shop-filter-form">
                        <div class="mb-3">
                            <label for="price-min" class="form-label">Цена от:</label>
                            <input type="number" class="form-control" id="price-min" name="price_min" 
                                value="<?php echo isset($_GET['price_min']) ? (int)$_GET['price_min'] : ''; ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="price-max" class="form-label">Цена до:</label>
                            <input type="number" class="form-control" id="price-max" name="price_max" 
                                value="<?php echo isset($_GET['price_max']) ? (int)$_GET['price_max'] : ''; ?>" min="0">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="in-stock" name="in_stock" 
                                <?php if (isset($_GET['in_stock']) && $_GET['in_stock'] == '1') echo 'checked'; ?> value="1">
                            <label class="form-check-label" for="in-stock">Только в наличии</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="on-sale" name="on_sale" 
                                <?php if (isset($_GET['on_sale']) && $_GET['on_sale'] == '1') echo 'checked'; ?> value="1">
                            <label class="form-check-label" for="on-sale">Со скидкой</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Применить</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Основное содержимое каталога товаров -->
        <div class="col-md-9">
            <h1 class="mb-4">Каталог товаров</h1>
            
            <?php if (isset($search_query) && !empty($search_query)): ?>
                <div class="alert alert-info">
                    Результаты поиска для: <strong><?php echo htmlspecialchars($search_query); ?></strong>
                </div>
            <?php endif; ?>
            
            <!-- Сортировка -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <?php if (isset($products) && !empty($products)): ?>
                        <p class="mb-0">Найдено товаров: <?php echo $total_products ?? count($products); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <form id="sort-form" class="d-flex">
                        <label for="sort-select" class="me-2 pt-1">Сортировать:</label>
                        <select id="sort-select" class="form-select" name="sort" onchange="document.getElementById('sort-form').submit()">
                            <option value="price_asc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') echo 'selected'; ?>>По цене (возрастание)</option>
                            <option value="price_desc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') echo 'selected'; ?>>По цене (убывание)</option>
                            <option value="name_asc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'name_asc') echo 'selected'; ?>>По названию (А-Я)</option>
                            <option value="name_desc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'name_desc') echo 'selected'; ?>>По названию (Я-А)</option>
                            <option value="newest" <?php if (!isset($_GET['sort']) || $_GET['sort'] == 'newest') echo 'selected'; ?>>Сначала новые</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Список товаров -->
            <?php if (isset($products) && !empty($products)): ?>
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
                                        <a href="/shop/product/<?php echo htmlspecialchars($product['slug']); ?>" class="btn btn-outline-primary">Подробнее</a>
                                        <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">В корзину</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Пагинация -->
                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                    <nav aria-label="Навигация по страницам">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo isset($search_query) ? '&q=' . urlencode($search_query) : ''; ?>" aria-label="Предыдущая">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?php if ($i == $pagination['current_page']) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($search_query) ? '&q=' . urlencode($search_query) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo isset($search_query) ? '&q=' . urlencode($search_query) : ''; ?>" aria-label="Следующая">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <p>Товары не найдены.</p>
                    <?php if (isset($search_query) && !empty($search_query)): ?>
                        <p>Попробуйте изменить поисковый запрос или параметры фильтрации.</p>
                        <a href="/shop" class="btn btn-primary">Сбросить фильтры</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JavaScript для обработки добавления в корзину -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Находим все кнопки добавления в корзину
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    // Добавляем обработчик для каждой кнопки
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            // Отправляем AJAX-запрос для добавления товара в корзину
            fetch('/api/shop/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Товар добавлен в корзину!');
                    // Обновляем мини-корзину, если она есть на странице
                    if (typeof updateMiniCart === 'function') {
                        updateMiniCart();
                    }
                } else {
                    alert(data.message || 'Ошибка при добавлении товара в корзину');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при добавлении товара в корзину');
            });
        });
    });
});
</script>

<?php
// Получаем содержимое буфера
$content = ob_get_clean();

// Подключаем базовый шаблон, передавая данные через область видимости
include 'base.php';
?> 
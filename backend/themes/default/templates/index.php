<?php
// Начинаем буферизацию вывода
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <h1 class="display-4 mb-4">Добро пожаловать на сайт</h1>
        <p class="lead"><?php echo htmlspecialchars($site_description); ?></p>
        
        <hr class="my-4">
        <?php if (isset($latestContent) && !empty($latestContent)): ?>
            <h2 class="mb-4">Последние публикации</h2>
            
            <div class="row">
                <?php foreach ($latestContent as $item): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($item['featured_image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['featured_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                
                                <?php if (!empty($item['description'])): ?>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                                
                                <a href="/p/<?php echo htmlspecialchars($item['slug']); ?>" class="btn btn-primary">Читать далее</a>
                            </div>
                            
                            <div class="card-footer text-muted">
                                <?php echo date('d.m.Y', strtotime($item['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>Добро пожаловать в Universal CMS!</p>
                <p>Это демонстрационная страница активной темы <strong><?php echo htmlspecialchars($active_theme); ?></strong>.</p>
                <p>Чтобы добавить публикации, воспользуйтесь <a href="/cms/admin" class="alert-link">панелью администратора</a>.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h5 mb-0">Страницы сайта</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <?php if (isset($pages) && !empty($pages)): ?>
                        <?php foreach ($pages as $page): ?>
                            <li class="mb-2">
                                <a href="/p/<?php echo htmlspecialchars($page['slug']); ?>">
                                    <?php echo htmlspecialchars($page['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Нет созданных страниц</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="h5 mb-0">О сайте</h3>
            </div>
            <div class="card-body">
                <p><?php echo htmlspecialchars($site_description); ?></p>
                <p>Этот сайт создан с использованием Universal CMS - современной и гибкой системы управления контентом.</p>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="/cms/admin" class="btn btn-primary">Панель администратора</a>
                    <?php if (isset($theme_config['demo_url'])): ?>
                    <a href="<?php echo htmlspecialchars($theme_config['demo_url']); ?>" class="btn btn-outline-secondary" target="_blank">Демо-пример</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Получаем содержимое буфера
$content = ob_get_clean();

// Подключаем базовый шаблон, передавая данные через область видимости
include 'base.php';
?> 
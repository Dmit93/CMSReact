<?php
// Устанавливаем заголовок страницы
$pageTitle = $item['title'] . ' - ' . $contentType['name'] . ' - ' . $siteTitle;
$pageDescription = $item['description'] ?? '';

// Начинаем буферизацию вывода
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <!-- Хлебные крошки -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Главная</a></li>
                <li class="breadcrumb-item"><a href="/content-type/<?php echo $contentType['id']; ?>"><?php echo htmlspecialchars($contentType['name']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($item['title']); ?></li>
            </ol>
        </nav>
        
        <!-- Заголовок и метаданные -->
        <header class="content-header">
            <h1 class="mb-2"><?php echo htmlspecialchars($item['title']); ?></h1>
            <div class="content-meta">
                <span class="me-3"><i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($item['created_at'])); ?></span>
                <?php if (isset($item['author']) && !empty($item['author'])): ?>
                    <span class="me-3"><i class="far fa-user"></i> <?php echo htmlspecialchars($item['author']); ?></span>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Изображение (если есть) -->
        <?php if (!empty($item['featured_image'])): ?>
            <img src="<?php echo htmlspecialchars($item['featured_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="content-image img-fluid mb-4">
        <?php endif; ?>
        
        <!-- Описание -->
        <?php if (!empty($item['description'])): ?>
            <div class="lead mb-4">
                <?php echo htmlspecialchars($item['description']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Основное содержимое -->
        <div class="content-body">
            <?php 
            // Выводим основное содержимое
            $content = isset($item['custom_fields']['content']) ? $item['custom_fields']['content'] : '';
            echo $content; 
            ?>
        </div>
        
        <!-- Навигация между записями -->
        <nav class="post-navigation mt-5 py-3 border-top border-bottom">
            <div class="row">
                <div class="col-6">
                    <?php if ($prevItem): ?>
                        <a href="/content/<?php echo $contentType['id']; ?>/<?php echo $prevItem['id']; ?>" class="nav-link">
                            <i class="fas fa-arrow-left"></i> Предыдущая
                            <div class="mt-1"><small><?php echo htmlspecialchars($prevItem['title']); ?></small></div>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-6 text-end">
                    <?php if ($nextItem): ?>
                        <a href="/content/<?php echo $contentType['id']; ?>/<?php echo $nextItem['id']; ?>" class="nav-link">
                            Следующая <i class="fas fa-arrow-right"></i>
                            <div class="mt-1"><small><?php echo htmlspecialchars($nextItem['title']); ?></small></div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>
    
    <div class="col-md-4">
        <!-- Сайдбар -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h5 mb-0">Другие записи в разделе</h3>
            </div>
            <div class="card-body">
                <!-- Динамическая загрузка контента через API -->
                <div data-dynamic-content data-type-id="<?php echo $contentType['id']; ?>" data-limit="5"></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="h5 mb-0">О разделе</h3>
            </div>
            <div class="card-body">
                <p><?php echo htmlspecialchars($contentType['description']); ?></p>
                <a href="/content-type/<?php echo $contentType['id']; ?>" class="btn btn-outline-primary btn-sm">
                    Все записи раздела
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Получаем содержимое буфера
$content = ob_get_clean();

// Подключаем базовый шаблон
include 'base.php';
?> 
<?php
// Шаблон для новостей
// Указываем заголовок страницы
$pageTitle = $item['title'] . ' - Новости';

// Начинаем буферизацию вывода
ob_start();
?>

<div class="news-article">
    <header class="news-header">
        <h1 class="display-4 mb-3"><?php echo htmlspecialchars($item['title']); ?></h1>
        <div class="news-meta mb-4">
            <span class="news-date badge bg-primary"><?php echo date('d.m.Y', strtotime($item['created_at'])); ?></span>
            <span class="news-author ms-2"><?php echo htmlspecialchars($item['author'] ?? 'Администратор'); ?></span>
        </div>
    </header>

    <?php if (!empty($item['image'])): ?>
        <div class="news-image mb-4">
            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="img-fluid rounded">
        </div>
    <?php endif; ?>

    <?php if (!empty($item['description'])): ?>
        <div class="lead mb-4">
            <?php echo htmlspecialchars($item['description']); ?>
        </div>
    <?php endif; ?>

    <div class="news-content mb-5">
        <?php echo $item['custom_fields']['content'] ?? ''; ?>
    </div>
    
    <?php if (!empty($item['custom_fields']['tags'])): ?>
        <div class="news-tags mb-4">
            <strong>Теги:</strong>
            <?php 
            $tags = explode(',', $item['custom_fields']['tags']);
            foreach ($tags as $tag): 
                $tag = trim($tag);
                if (!empty($tag)):
            ?>
                <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($tag); ?></span>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    <?php endif; ?>

    <!-- Навигация между записями -->
    <nav class="post-navigation mt-5 py-3 border-top border-bottom">
        <div class="row">
            <div class="col-6">
                <?php if ($prevItem): ?>
                    <a href="/content/<?php echo $contentType['id']; ?>/<?php echo $prevItem['id']; ?>" class="nav-link">
                        <i class="fas fa-arrow-left"></i> Предыдущая новость
                        <div class="mt-1"><small><?php echo htmlspecialchars($prevItem['title']); ?></small></div>
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <?php if ($nextItem): ?>
                    <a href="/content/<?php echo $contentType['id']; ?>/<?php echo $nextItem['id']; ?>" class="nav-link">
                        Следующая новость <i class="fas fa-arrow-right"></i>
                        <div class="mt-1"><small><?php echo htmlspecialchars($nextItem['title']); ?></small></div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Кнопка возврата к списку -->
    <div class="text-center mt-4">
        <a href="/content-type/<?php echo $contentType['id']; ?>" class="btn btn-outline-primary">
            Вернуться к списку новостей
        </a>
    </div>
</div>

<?php
// Получаем содержимое буфера и очищаем его
$content = ob_get_clean();

// Подключаем базовый шаблон
include __DIR__ . '/base.php';
?> 
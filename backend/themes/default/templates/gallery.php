<?php
// Шаблон для галереи изображений
// Указываем заголовок страницы
$pageTitle = $item['title'] . ' - Галерея';

// Начинаем буферизацию вывода
ob_start();
?>

<div class="gallery-container">
    <header class="gallery-header mb-4">
        <h1 class="display-4"><?php echo htmlspecialchars($item['title']); ?></h1>
        <?php if (!empty($item['description'])): ?>
            <div class="lead mb-4">
                <?php echo htmlspecialchars($item['description']); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php
    // Обработка поля gallery, которое должно содержать список изображений (JSON массив)
    $galleryImages = [];
    if (!empty($item['custom_fields']['gallery'])) {
        $galleryImages = json_decode($item['custom_fields']['gallery'], true);
    }
    // Если есть основное изображение, добавим и его
    if (!empty($item['image'])) {
        array_unshift($galleryImages, [
            'url' => $item['image'],
            'title' => $item['title'],
            'description' => $item['description'] ?? ''
        ]);
    }
    ?>

    <?php if (!empty($galleryImages)): ?>
        <div class="gallery-grid row">
            <?php foreach ($galleryImages as $image): ?>
                <div class="col-md-4 mb-4">
                    <div class="card gallery-item h-100">
                        <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                             alt="<?php echo htmlspecialchars($image['title'] ?? ''); ?>" 
                             class="card-img-top gallery-image" 
                             data-full="<?php echo htmlspecialchars($image['url']); ?>"
                             data-title="<?php echo htmlspecialchars($image['title'] ?? ''); ?>"
                             data-description="<?php echo htmlspecialchars($image['description'] ?? ''); ?>">
                        <?php if (!empty($image['title']) || !empty($image['description'])): ?>
                            <div class="card-body">
                                <?php if (!empty($image['title'])): ?>
                                    <h5 class="card-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                <?php endif; ?>
                                <?php if (!empty($image['description'])): ?>
                                    <p class="card-text"><?php echo htmlspecialchars($image['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            В этой галерее пока нет изображений.
        </div>
    <?php endif; ?>

    <!-- Дополнительная информация о галерее -->
    <?php if (!empty($item['custom_fields']['content'])): ?>
        <div class="gallery-description mt-5">
            <?php echo $item['custom_fields']['content']; ?>
        </div>
    <?php endif; ?>

    <!-- Навигация между записями -->
    <nav class="post-navigation mt-5 py-3 border-top border-bottom">
        <div class="row">
            <div class="col-6">
                <?php if ($prevItem): ?>
                    <a href="/content/<?php echo $contentType['id']; ?>/<?php echo $prevItem['id']; ?>" class="nav-link">
                        <i class="fas fa-arrow-left"></i> Предыдущая галерея
                        <div class="mt-1"><small><?php echo htmlspecialchars($prevItem['title']); ?></small></div>
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <?php if ($nextItem): ?>
                    <a href="/content/<?php echo $contentType['id']; ?>/<?php echo $nextItem['id']; ?>" class="nav-link">
                        Следующая галерея <i class="fas fa-arrow-right"></i>
                        <div class="mt-1"><small><?php echo htmlspecialchars($nextItem['title']); ?></small></div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Кнопка возврата к списку -->
    <div class="text-center mt-4">
        <a href="/content-type/<?php echo $contentType['id']; ?>" class="btn btn-outline-primary">
            Вернуться к списку галерей
        </a>
    </div>

    <!-- Модальное окно для просмотра изображений -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="galleryModalImage" class="img-fluid">
                    <p id="galleryModalDescription" class="mt-3"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript для галереи -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик клика по изображению
    const galleryImages = document.querySelectorAll('.gallery-image');
    const modal = new bootstrap.Modal(document.getElementById('galleryModal'));
    
    galleryImages.forEach(image => {
        image.addEventListener('click', function() {
            document.getElementById('galleryModalTitle').textContent = this.dataset.title;
            document.getElementById('galleryModalImage').src = this.dataset.full;
            document.getElementById('galleryModalDescription').textContent = this.dataset.description;
            modal.show();
        });
    });
});
</script>

<?php
// Получаем содержимое буфера и очищаем его
$content = ob_get_clean();

// Подключаем базовый шаблон
include __DIR__ . '/base.php';
?> 
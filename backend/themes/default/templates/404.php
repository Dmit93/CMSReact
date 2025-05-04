<?php
// Устанавливаем заголовок страницы
$pageTitle = 'Страница не найдена (404)';
$pageDescription = 'Запрошенная страница не найдена';

// Начинаем буферизацию вывода
ob_start();
?>

<div class="row">
    <div class="col-md-8 mx-auto text-center">
        <h1 class="display-1 text-muted">404</h1>
        <h2 class="mb-4">Страница не найдена</h2>
        <p class="lead mb-5">Запрошенная вами страница не существует или была перемещена.</p>
        
        <div class="mb-5">
            <a href="/" class="btn btn-primary">Вернуться на главную</a>
        </div>
        
        <div class="card mt-5">
            <div class="card-header">
                <h3 class="h5 mb-0">Возможно, вам будет интересно</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <?php if (isset($contentTypes) && is_array($contentTypes)): ?>
                        <?php foreach ($contentTypes as $type): ?>
                            <li class="mb-2">
                                <a href="/content-type/<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Нет доступных разделов</li>
                    <?php endif; ?>
                </ul>
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
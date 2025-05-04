<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    
    <!-- Подключение внешних библиотек CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Подключение стилей темы -->
    <link rel="stylesheet" href="<?php echo $theme_path; ?>/assets/css/style.css">
    
    <?php if (isset($customCss)): ?>
    <style>
        <?php echo $customCss; ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <!-- Шапка сайта -->
    <header class="site-header">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="/"><?php echo $site_title; ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">Главная</a>
                        </li>
                        <?php if (isset($pages) && is_array($pages)): ?>
                            <?php foreach ($pages as $page): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/p/<?php echo htmlspecialchars($page['slug']); ?>">
                                        <?php echo htmlspecialchars($page['title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/cms/admin">Администрирование</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Основное содержимое -->
    <main class="site-main">
        <div class="container py-4">
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <!-- Подвал сайта -->
    <footer class="site-footer bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $site_title; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Powered by Universal CMS | Активная тема: <?php echo $active_theme; ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Скрипты -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $theme_path; ?>/assets/js/script.js"></script>
    
    <?php if (isset($customJs)): ?>
    <script>
        <?php echo $customJs; ?>
    </script>
    <?php endif; ?>
</body>
</html> 
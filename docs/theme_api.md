# Документация по API для тем в Universal CMS

## Введение

Этот документ описывает API для работы с темами в Universal CMS. Система использует класс `ThemeRenderer` для рендеринга шаблонов и предоставления доступа к данным из базы данных.

## Доступные переменные

В каждом шаблоне доступны следующие переменные:

- `$site_title` - название сайта
- `$site_description` - описание сайта
- `$active_theme` - имя активной темы
- `$theme_path` - путь к директории активной темы
- `$theme_config` - массив с конфигурацией темы из файла theme.json
- `$pages` - массив опубликованных страниц для навигации

## Методы и функции для работы с контентом

### Получение списка записей

В любом шаблоне темы вы можете использовать следующий код для получения записей:

```php
<?php
// Получаем последние 5 записей блога
$latestPosts = $themeRenderer->getContent('post', [
    'limit' => 5,
    'order' => 'created_at DESC'
]);

// Выводим записи
foreach ($latestPosts as $post) {
    echo '<h2>' . htmlspecialchars($post['title']) . '</h2>';
    echo '<p>' . htmlspecialchars($post['description']) . '</p>';
}
?>
```

### Параметры метода getContent

Метод `getContent` принимает следующие параметры:

| Параметр | Тип | Описание | Значение по умолчанию |
|----------|-----|----------|----------------------|
| $type    | string | Системное имя типа контента | - |
| $options | array | Массив опций | [] |

Опции:

| Опция   | Описание | Значение по умолчанию |
|---------|----------|----------------------|
| limit   | Количество записей | 10 |
| offset  | Смещение (для пагинации) | 0 |
| order   | Сортировка (SQL-формат) | 'id DESC' |
| where   | Дополнительные условия WHERE (SQL-формат) | "status = 'published'" |
| fields  | Поля для выборки (SQL-формат) | '*' |

### Получение одной записи

```php
<?php
// Получаем запись по ID
$post = $themeRenderer->getSingleContent(5);

// Получаем запись по slug
$page = $themeRenderer->getSingleContent('about', 'page');

// Выводим содержимое
if ($post) {
    echo '<h1>' . htmlspecialchars($post['title']) . '</h1>';
    echo '<div class="content">' . $post['content'] . '</div>';
}
?>
```

## Структура шаблонов

Система тем использует следующую структуру:

```
backend/themes/[theme_name]/
  ├── assets/
  │   ├── css/
  │   │   └── style.css
  │   ├── js/
  │   │   └── script.js
  │   └── images/
  ├── templates/
  │   ├── index.php     # Главная страница
  │   ├── base.php      # Базовый шаблон (header, footer)
  │   ├── page.php      # Шаблон для страниц
  │   ├── post.php      # Шаблон для записей блога
  │   └── 404.php       # Страница ошибки 404
  └── theme.json        # Конфигурация темы
```

## Пример создания шаблона страницы

```php
<?php
// Начинаем буферизацию вывода
ob_start();

// Получаем страницу по slug из URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$page = $themeRenderer->getSingleContent($slug, 'page');

if ($page):
?>
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
    </div>
    
    <div class="page-content">
        <?php echo $page['content']; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <p>Страница не найдена</p>
    </div>
<?php endif;

// Получаем содержимое буфера
$content = ob_get_clean();

// Подключаем базовый шаблон
include 'base.php';
?>
```

## Пример создания шаблона для пользовательского типа контента

```php
<?php
// Начинаем буферизацию вывода
ob_start();

// Получаем проекты
$projects = $themeRenderer->getContent('project', [
    'limit' => 12,
    'order' => 'created_at DESC'
]);
?>

<div class="projects-grid">
    <h1>Наши проекты</h1>
    
    <div class="row">
        <?php foreach ($projects as $project): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if (!empty($project['image'])): ?>
                        <img src="<?php echo htmlspecialchars($project['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($project['title']); ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($project['description']); ?></p>
                        <a href="/project/<?php echo htmlspecialchars($project['slug']); ?>" class="btn btn-primary">Подробнее</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// Получаем содержимое буфера
$content = ob_get_clean();

// Подключаем базовый шаблон
include 'base.php';
?>
```

## Конфигурация темы (theme.json)

```json
{
  "name": "Default Theme",
  "description": "Стандартная тема для Universal CMS",
  "version": "1.0.0",
  "author": "Universal CMS Team",
  "demo_url": "https://example.com/demo",
  "settings": [
    {
      "id": "primary_color",
      "label": "Основной цвет",
      "type": "color",
      "default": "#007bff"
    },
    {
      "id": "show_search",
      "label": "Показывать поиск",
      "type": "boolean",
      "default": true
    }
  ]
}
``` 
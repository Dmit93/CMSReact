# Руководство по использованию шаблонов и предпросмотру тем

## Содержание

1. [Настройка шаблонов](#настройка-шаблонов)
2. [Привязка шаблонов к контенту](#привязка-шаблонов-к-контенту)
3. [Предпросмотр тем](#предпросмотр-тем)
4. [API для управления шаблонами](#api-для-управления-шаблонами)

## Настройка шаблонов

Каждая тема может содержать несколько шаблонов для отображения контента. Шаблоны хранятся в директории `templates` внутри папки темы.

### Стандартные шаблоны

Каждая тема должна включать следующие стандартные шаблоны:

- **base.php** - базовый шаблон, содержащий общую структуру HTML
- **index.php** - шаблон главной страницы
- **content_type.php** - шаблон для отображения списка записей определенного типа
- **content_item.php** - шаблон для отображения отдельной записи
- **404.php** - шаблон для страницы "Не найдено"

### Создание кастомных шаблонов

Вы можете создавать дополнительные шаблоны для специфических типов контента или отдельных записей. Например:

```php
<?php
// Шаблон для новостей (article_news.php)
// Указываем заголовок страницы
$pageTitle = $item['title'] . ' - Новости';

// Начинаем буферизацию вывода
ob_start();
?>

<div class="news-article">
    <header class="news-header">
        <h1><?php echo htmlspecialchars($item['title']); ?></h1>
        <div class="news-meta">
            <span class="news-date"><?php echo date('d.m.Y', strtotime($item['created_at'])); ?></span>
        </div>
    </header>

    <?php if (!empty($item['image'])): ?>
        <div class="news-image">
            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
        </div>
    <?php endif; ?>

    <div class="news-content">
        <?php echo $item['custom_fields']['content'] ?? ''; ?>
    </div>
</div>

<?php
// Получаем содержимое буфера и очищаем его
$content = ob_get_clean();

// Подключаем базовый шаблон
include __DIR__ . '/base.php';
?>
```

## Привязка шаблонов к контенту

Вы можете привязать определенный шаблон к конкретному элементу контента. Это позволяет использовать разные макеты для разных статей или записей.

### Через API

```http
PUT /api/content/123/template
Content-Type: application/json

{
    "template_name": "article_news"
}
```

### Через административную панель

1. Перейдите в раздел "Контент" в административной панели
2. Выберите запись для редактирования
3. В разделе "Настройки отображения" выберите шаблон из выпадающего списка
4. Нажмите "Сохранить"

## Предпросмотр тем

Система поддерживает предварительный просмотр тем до их активации, а также предпросмотр контента с различными шаблонами.

### URL для предпросмотра

- **Главная страница**: `/preview/theme/{theme_name}`
- **Страница типа контента**: `/preview/theme/{theme_name}/content-type/{type_id}`
- **Страница записи**: `/preview/theme/{theme_name}/content/{type_id}/{item_id}`
- **С конкретным шаблоном**: `/preview/theme/{theme_name}/template/{item_id}/{template_name}`

### Пример использования

1. Для предпросмотра темы "modern":  
   `/preview/theme/modern`

2. Для предпросмотра контента с ID 123 в теме "modern" с шаблоном "article_news":  
   `/preview/theme/modern/template/123/article_news`

## API для управления шаблонами

### Получение списка доступных шаблонов

```http
GET /api/content/{content_id}/templates
```

**Пример ответа**:

```json
{
    "success": true,
    "data": {
        "templates": ["content_item", "article", "news", "gallery"],
        "current_template": "news"
    }
}
```

### Установка шаблона для контента

```http
PUT /api/content/{content_id}/template
Content-Type: application/json

{
    "template_name": "gallery"
}
```

**Пример ответа**:

```json
{
    "success": true,
    "message": "Шаблон контента успешно обновлен",
    "data": {
        "template_name": "gallery"
    }
}
``` 
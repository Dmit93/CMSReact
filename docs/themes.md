# Документация по системе тем CMS

## Содержание

1. [Введение](#введение)
2. [Структура темы](#структура-темы)
3. [Создание темы](#создание-темы)
4. [API темы](#api-темы)
5. [Шаблоны](#шаблоны)
6. [Работа с JavaScript](#работа-с-javascript)
7. [Стили и CSS](#стили-и-css)
8. [Лучшие практики](#лучшие-практики)

## Введение

Система тем CMS позволяет полностью изменять внешний вид и поведение сайта без изменения кодовой базы CMS. Вы можете создавать собственные темы, активировать их через админ-панель и настраивать под свои потребности.

Темы хранятся в каталоге `backend/themes/` и каждая тема находится в отдельной папке.

## Структура темы

Каждая тема должна иметь следующую структуру каталогов:

```
theme_name/
├── theme.json            # Конфигурация темы
├── templates/            # Шаблоны страниц
│   ├── base.php          # Базовый шаблон (обязательный)
│   ├── index.php         # Шаблон главной страницы (обязательный)
│   ├── content_type.php  # Шаблон страницы типа контента (обязательный)
│   ├── content_item.php  # Шаблон страницы отдельной записи (обязательный)
│   ├── 404.php           # Шаблон страницы 404 (обязательный)
│   └── ... другие шаблоны
├── assets/               # Ресурсы темы
│   ├── css/              # CSS стили
│   │   └── style.css     # Основной файл стилей (обязательный)
│   ├── js/               # JavaScript файлы
│   │   └── script.js     # Основной файл скриптов
│   └── images/           # Изображения темы
└── screenshots/          # Скриншоты темы для админ-панели
    └── screenshot.jpg    # Основной скриншот темы
```

## Создание темы

### 1. Создайте структуру каталогов

Создайте новую папку в директории `backend/themes/` с именем вашей темы (например, `modern`).

### 2. Создайте файл конфигурации `theme.json`

```json
{
    "title": "Название темы",
    "description": "Описание темы",
    "version": "1.0.0",
    "author": "Ваше имя",
    "supports": {
        "contentTypes": true,
        "customFields": true,
        "widgets": true,
        "comments": true
    },
    "templates": {
        "index": "Главная страница",
        "content_type": "Страница типа контента",
        "content_item": "Страница отдельной записи",
        "404": "Страница 404"
    }
}
```

### 3. Создайте основные шаблоны

Минимальный набор шаблонов для работы темы:
- `templates/base.php` - базовый шаблон, содержащий общую структуру страницы
- `templates/index.php` - шаблон главной страницы
- `templates/content_type.php` - шаблон страницы типа контента
- `templates/content_item.php` - шаблон страницы отдельной записи
- `templates/404.php` - шаблон страницы 404

### 4. Добавьте стили и скрипты

- `assets/css/style.css` - основной файл стилей темы
- `assets/js/script.js` - основной файл скриптов темы

## API темы

Для взаимодействия с CMS из шаблонов темы доступны следующие возможности:

### Объект Theme

```php
$theme = new \Core\Theme();

// Получить URL темы для формирования ссылок на ресурсы
$themeUrl = $theme->getThemeUrl();

// Отобразить шаблон с данными
$content = $theme->render('template_name', [
    'key' => 'value',
    'items' => $items
]);
```

### Объект Content

```php
$contentModel = new \Models\ContentModel();

// Получить список записей определенного типа
$items = $contentModel->getList([
    'content_type_id' => $typeId,
    'status' => 'published',
    'limit' => 10,
    'order' => 'created_at DESC'
]);

// Получить отдельную запись по ID
$item = $contentModel->getById($itemId);

// Получить запись по слагу
$item = $contentModel->getBySlug($slug);
```

### Объект ContentType

```php
$contentTypeModel = new \Models\ContentTypeModel();

// Получить список всех типов контента
$contentTypes = $contentTypeModel->getList();

// Получить тип контента по ID
$contentType = $contentTypeModel->getById($typeId);
```

### JavaScript API

В теме доступен JavaScript клиент API, который можно использовать для динамического взаимодействия с CMS:

```javascript
// Создание экземпляра API клиента
const cmsApi = new CmsApi();

// Получить список типов контента
cmsApi.getContentTypes().then(response => {
    if (response.success) {
        const contentTypes = response.data;
        // Используем данные
    }
});

// Получить список записей определенного типа
cmsApi.getContentList(typeId, { limit: 5, status: 'published' }).then(response => {
    if (response.success) {
        const items = response.data;
        // Используем данные
    }
});
```

## Шаблоны

### Базовый шаблон (base.php)

Базовый шаблон содержит общую структуру HTML-страницы, включая `<head>`, навигацию, подвал и подключение скриптов. Остальные шаблоны встраиваются в него.

```php
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $themeUrl; ?>/assets/css/style.css">
</head>
<body>
    <header>
        <!-- Шапка сайта -->
    </header>

    <main>
        <?php echo $content; ?>
    </main>

    <footer>
        <!-- Подвал сайта -->
    </footer>

    <script src="<?php echo $themeUrl; ?>/assets/js/script.js"></script>
</body>
</html>
```

### Шаблон страницы (пример)

```php
<?php
// Установка заголовка страницы
$pageTitle = 'Заголовок страницы';

// Начало буферизации вывода
ob_start();
?>

<!-- HTML содержимое шаблона -->
<h1>Заголовок страницы</h1>
<p>Содержимое страницы...</p>

<?php
// Получение содержимого буфера
$content = ob_get_clean();

// Подключение базового шаблона
include 'base.php';
?>
```

## Работа с JavaScript

### Структура JavaScript файла

```javascript
/**
 * Скрипт темы
 */

// Код, выполняемый при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация функций
    initSliders();
    setupSearchForm();
    // ...
});

// Функция инициализации слайдеров
function initSliders() {
    // Код инициализации
}

// Функция настройки формы поиска
function setupSearchForm() {
    // Код настройки
}
```

### Взаимодействие с API через JavaScript

```javascript
// Получить динамический контент через API
function loadDynamicContent(typeId, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    cmsApi.getContentList(typeId, { limit: 5, status: 'published' })
        .then(response => {
            if (response.success && response.data) {
                renderContentItems(container, response.data);
            } else {
                container.innerHTML = '<p>Нет доступного контента</p>';
            }
        })
        .catch(error => {
            console.error('Error loading content:', error);
            container.innerHTML = '<p>Ошибка загрузки контента</p>';
        });
}
```

## Стили и CSS

### Структура файла стилей

```css
/**
 * Основные стили темы
 */

/* Общие стили */
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    color: #333;
}

/* Шапка сайта */
.site-header {
    /* Стили шапки */
}

/* Основное содержимое */
.site-main {
    /* Стили основного содержимого */
}

/* Подвал сайта */
.site-footer {
    /* Стили подвала */
}

/* Медиа-запросы для адаптивного дизайна */
@media (max-width: 768px) {
    /* Стили для экранов меньше 768px */
}
```

## Лучшие практики

1. **Наследуйте от темы по умолчанию**: Используйте тему `default` как основу для своей темы.

2. **Используйте общие компоненты**: Выделяйте повторяющиеся элементы в отдельные шаблоны.

3. **Оптимизируйте производительность**: Минимизируйте количество запросов к API и базе данных.

4. **Добавляйте документацию**: Документируйте особенности вашей темы в README.md файле.

5. **Проверяйте совместимость**: Убедитесь, что ваша тема работает во всех основных браузерах.

6. **Следуйте стандартам безопасности**: Экранируйте вывод данных с помощью `htmlspecialchars()`.

7. **Используйте предзагрузку**: Для критических ресурсов используйте атрибуты `preload`.

8. **Оптимизируйте изображения**: Используйте оптимизированные форматы изображений и указывайте размеры.

9. **Используйте кэширование**: Добавьте заголовки кэширования для статических ресурсов. 
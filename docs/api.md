# Документация по API сайта CMS

## Содержание

1. [Введение](#введение)
2. [API конечные точки](#api-конечные-точки)
   - [Типы контента](#типы-контента)
   - [Контент](#контент)
   - [Аутентификация](#аутентификация)
   - [Темы](#темы)
   - [Настройки](#настройки)
3. [Модели данных](#модели-данных)
4. [Использование API на фронтенде](#использование-api-на-фронтенде)
5. [Примеры запросов](#примеры-запросов)
6. [Расширение API](#расширение-api)

## Введение

API сайта CMS предоставляет интерфейс для взаимодействия с данными CMS через HTTP-запросы. API основан на REST архитектуре и позволяет получать, создавать, обновлять и удалять контент, а также управлять настройками сайта и темами.

**Базовый URL API**: `/api`

**Формат ответа**: Все ответы API возвращаются в формате JSON и имеют следующую структуру:

```json
{
    "success": true,
    "message": "Сообщение о результате",
    "data": { ... } // Данные (присутствуют только при успешном выполнении)
}
```

## API конечные точки

### Типы контента

#### Получить список типов контента

```
GET /api/content-types
```

Пример ответа:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Статьи",
            "slug": "articles",
            "description": "Статьи для блога",
            "fields": [...]
        },
        ...
    ]
}
```

#### Получить тип контента по ID

```
GET /api/content-types/{id}
```

Пример ответа:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Статьи",
        "slug": "articles",
        "description": "Статьи для блога",
        "fields": [...]
    }
}
```

#### Создать новый тип контента

```
POST /api/content-types
```

Тело запроса:
```json
{
    "name": "Новости",
    "slug": "news",
    "description": "Новости компании",
    "fields": [...]
}
```

#### Обновить тип контента

```
PUT /api/content-types/{id}
```

Тело запроса: такое же, как для создания.

#### Удалить тип контента

```
DELETE /api/content-types/{id}
```

### Контент

#### Получить список записей определенного типа

```
GET /api/content-types/{typeId}/content
```

Дополнительные параметры запроса:
- `limit` - количество записей (по умолчанию 10)
- `offset` - смещение (для пагинации)
- `status` - статус записей (published, draft, archived)
- `order` - сортировка (например, created_at DESC)
- `search` - поиск по контенту

Пример ответа:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "content_type_id": 1,
            "title": "Заголовок записи",
            "slug": "post-slug",
            "description": "Краткое описание",
            "status": "published",
            "created_at": "2023-05-01 10:00:00",
            "updated_at": "2023-05-01 10:00:00",
            "custom_fields": {...}
        },
        ...
    ],
    "total": 25,
    "page": 1,
    "pages": 3
}
```

#### Получить запись по ID

```
GET /api/content-types/{typeId}/content/{id}
```

#### Создать новую запись

```
POST /api/content-types/{typeId}/content
```

Тело запроса:
```json
{
    "title": "Заголовок записи",
    "slug": "post-slug",
    "description": "Краткое описание",
    "status": "draft",
    "custom_fields": {...}
}
```

#### Обновить запись

```
PUT /api/content-types/{typeId}/content/{id}
```

Тело запроса: такое же, как для создания.

#### Удалить запись

```
DELETE /api/content-types/{typeId}/content/{id}
```

### Аутентификация

#### Вход в систему

```
POST /api/auth/login
```

Тело запроса:
```json
{
    "username": "user",
    "password": "password"
}
```

Пример ответа:
```json
{
    "success": true,
    "message": "Успешный вход",
    "data": {
        "user": {
            "id": 1,
            "username": "user",
            "email": "user@example.com",
            "role": "admin"
        },
        "token": "JWT-токен" // Опционально, в зависимости от реализации
    }
}
```

#### Регистрация

```
POST /api/auth/register
```

Тело запроса:
```json
{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "password",
    "password_confirm": "password"
}
```

#### Получить текущего пользователя

```
GET /api/auth/me
```

#### Выход из системы

```
POST /api/auth/logout
```

### Темы

#### Получить список доступных тем

```
GET /api/themes
```

Пример ответа:
```json
{
    "success": true,
    "data": [
        {
            "name": "default",
            "title": "Стандартная тема",
            "description": "Стандартная тема для CMS",
            "version": "1.0.0",
            "author": "CMS Team",
            "active": true
        },
        ...
    ]
}
```

#### Получить информацию о теме

```
GET /api/themes/{name}
```

Пример ответа:
```json
{
    "success": true,
    "data": {
        "name": "default",
        "title": "Стандартная тема",
        "description": "Стандартная тема для CMS",
        "version": "1.0.0",
        "author": "CMS Team",
        "active": true,
        "screenshots": [...],
        "templates": [...]
    }
}
```

#### Активировать тему

```
POST /api/themes/activate
```

Тело запроса:
```json
{
    "theme_name": "modern"
}
```

### Настройки

#### Получить все настройки

```
GET /api/settings
```

Пример ответа:
```json
{
    "success": true,
    "data": {
        "site_title": "Мой сайт",
        "site_description": "Описание сайта",
        "active_theme": "default",
        "posts_per_page": "10",
        ...
    }
}
```

#### Обновить настройки

```
PUT /api/settings
```

Тело запроса:
```json
{
    "site_title": "Новое название сайта",
    "site_description": "Новое описание"
}
```

## Модели данных

### Тип контента (ContentType)

| Поле | Тип | Описание |
|------|-----|----------|
| id | integer | Уникальный идентификатор |
| name | string | Название типа контента |
| slug | string | Уникальный слаг для URL |
| description | string | Описание типа контента |
| fields | json | Описание полей типа контента |
| created_at | datetime | Дата создания |
| updated_at | datetime | Дата обновления |

### Запись контента (Content)

| Поле | Тип | Описание |
|------|-----|----------|
| id | integer | Уникальный идентификатор |
| content_type_id | integer | ID типа контента |
| title | string | Заголовок записи |
| slug | string | Уникальный слаг для URL |
| description | string | Краткое описание |
| status | string | Статус (published, draft, archived) |
| created_at | datetime | Дата создания |
| updated_at | datetime | Дата обновления |
| custom_fields | json | Пользовательские поля |

### Пользователь (User)

| Поле | Тип | Описание |
|------|-----|----------|
| id | integer | Уникальный идентификатор |
| username | string | Имя пользователя |
| email | string | Email пользователя |
| password | string | Хеш пароля |
| role | string | Роль (admin, editor, user) |
| created_at | datetime | Дата создания |
| updated_at | datetime | Дата обновления |

## Использование API на фронтенде

### JavaScript API клиент

В темах доступен JavaScript клиент для работы с API:

```javascript
// Создание экземпляра API клиента
const cmsApi = new CmsApi();

// Получение списка типов контента
cmsApi.getContentTypes().then(response => {
    if (response.success) {
        // Использование данных
        const contentTypes = response.data;
        // ...
    }
});

// Получение списка записей определенного типа
cmsApi.getContentList(typeId, {
    limit: 5,
    status: 'published',
    order: 'created_at DESC'
}).then(response => {
    if (response.success) {
        // Использование данных
        const items = response.data;
        // ...
    }
});
```

## Примеры запросов

### Получение всех опубликованных статей

```javascript
// С использованием API клиента
cmsApi.getContentList(1, { status: 'published' })
    .then(response => {
        if (response.success) {
            const articles = response.data;
            // Обработка данных
        }
    });

// С использованием Fetch API
fetch('/api/content-types/1/content?status=published', {
    method: 'GET',
    headers: {
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        const articles = data.data;
        // Обработка данных
    }
});
```

### Создание новой записи

```javascript
// С использованием API клиента
const newPost = {
    title: 'Новая статья',
    slug: 'new-article',
    description: 'Описание новой статьи',
    status: 'draft',
    custom_fields: {
        content: '<p>Содержимое статьи...</p>',
        featured_image: '/uploads/image.jpg'
    }
};

cmsApi.createContent(1, newPost)
    .then(response => {
        if (response.success) {
            const createdPost = response.data;
            // Обработка результата
        }
    });

// С использованием Fetch API
fetch('/api/content-types/1/content', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify(newPost)
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        const createdPost = data.data;
        // Обработка результата
    }
});
```

## Расширение API

Для расширения API и добавления новых конечных точек необходимо:

1. Создать новый контроллер в директории `backend/controllers/`.
2. Добавить маршруты в файл `backend/api/routes.php`.

### Пример добавления нового контроллера

```php
<?php
namespace Controllers;

class SearchController {
    public function search($query) {
        // Логика поиска
        // ...
        
        return [
            'success' => true,
            'data' => $results
        ];
    }
}
```

### Пример добавления новых маршрутов

```php
// В файле backend/api/routes.php
$router->get('/search', [SearchController::class, 'search']);
```

### Создание пользовательского плагина с API

Чтобы добавить API для пользовательского плагина:

1. Создайте директорию плагина в `backend/modules/your_plugin/`.
2. Создайте контроллер для API в `backend/modules/your_plugin/controllers/`.
3. Добавьте маршруты в `backend/api/routes.php`:

```php
// API для вашего плагина
$router->get('/plugins/your_plugin/data', [Modules\YourPlugin\Controllers\ApiController::class, 'getData']);
``` 
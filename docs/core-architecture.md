# Документация CMS системы

## Архитектура ядра

### Обзор системы
CMS система построена на модульной архитектуре с событийной моделью. Основные компоненты ядра:

1. **Core** - центральный класс, управляющий всей системой
2. **EventManager** - система управления событиями
3. **ModuleManager** - система управления модулями
4. **Logger** - система логирования
5. **Autoloader** - автозагрузчик классов

### Система событий
В основе архитектуры лежит событийная модель, которая позволяет модулям взаимодействовать между собой без прямых зависимостей.

```php
// Пример регистрации обработчика события
$eventManager = \Core\EventManager::getInstance();
$eventManager->on('content.after_save', function($content) {
    // Обработка события
});

// Пример вызова события
$core = \Core\Core::getInstance();
$core->trigger('content.after_save', [$content]);
```

### Основные события системы
- **core.before_init** - перед инициализацией ядра
- **core.after_init** - после инициализации ядра
- **api.register_route** - регистрация маршрута API
- **api.before_request** - перед обработкой запроса API
- **api.after_request** - после обработки запроса API
- **api.error** - при ошибке API
- **content_type.before_save** - перед сохранением типа контента
- **content_type.after_save** - после сохранения типа контента
- **content_type.before_delete** - перед удалением типа контента
- **content_type.after_delete** - после удаления типа контента
- **content.before_save** - перед сохранением контента
- **content.after_save** - после сохранения контента
- **content.before_delete** - перед удалением контента
- **content.after_delete** - после удаления контента

### Модульная система
Система поддерживает расширение через модули. Каждый модуль представляет собой отдельный компонент с определенной функциональностью.

Структура модуля:
```
modules/
  example/
    module.php      - информация о модуле
    init.php        - инициализация модуля
    Module.php      - основной класс модуля
```

Пример файла module.php:
```php
return [
    'id' => 'example',
    'name' => 'Пример модуля',
    'description' => 'Описание функциональности модуля',
    'version' => '1.0.0',
    'author' => 'Разработчик',
    'requires' => [] // Зависимости
];
```

Пример основного класса модуля:
```php
namespace Modules\Example;

use Core\BaseModule;

class Module extends BaseModule {
    public function init() {
        // Регистрация обработчиков событий
        $this->registerEventHandler('content.after_save', [$this, 'onContentSave']);
        
        // Регистрация маршрутов API
        $this->registerApiRoute('GET', '/example/data', [$this, 'getData']);
    }
    
    public function onContentSave($content) {
        // Обработка события
    }
    
    public function getData() {
        // Обработка API запроса
        return ['success' => true, 'data' => []];
    }
}
```

### API система
CMS предоставляет RESTful API для взаимодействия с контентом. Маршруты API:

#### Типы контента
- `GET /content-types` - получение всех типов контента
- `GET /content-types/{id}` - получение типа контента по ID
- `POST /content-types` - создание нового типа контента
- `PUT /content-types/{id}` - обновление типа контента
- `DELETE /content-types/{id}` - удаление типа контента

#### Поля типов контента
- `GET /content-types/{id}/fields` - получение полей типа контента
- `POST /content-types/{id}/fields` - создание нового поля
- `PUT /content-types/fields/{id}` - обновление поля
- `DELETE /content-types/fields/{id}` - удаление поля

#### Контент
- `GET /content/{type}` - получение контента определенного типа
- `GET /content/{type}/{id}` - получение записи контента по ID
- `POST /content/{type}` - создание новой записи контента
- `PUT /content/{type}/{id}` - обновление записи контента
- `DELETE /content/{type}/{id}` - удаление записи контента

### Система логирования
CMS имеет встроенную систему логирования для отслеживания ошибок и важных событий.

```php
$logger = \Core\Logger::getInstance();
$logger->info('Информационное сообщение');
$logger->debug('Отладочная информация', ['context' => 'data']);
$logger->warning('Предупреждение');
$logger->error('Ошибка', ['exception' => $e]);
```

### Конфигурация ядра
Конфигурация ядра находится в файле `backend/config/core.php` и содержит различные настройки:

```php
return [
    'debug' => true,
    'base_path' => '/cms/',
    'modules_paths' => [
        // Пути к модулям
    ],
    'events' => [
        // Список доступных событий
    ],
    'logging' => [
        'enabled' => true,
        'path' => '/path/to/logs',
        'level' => 'debug'
    ]
];
```

## Модель данных

### Таблицы базы данных
CMS использует следующие основные таблицы:

1. **content_types** - типы контента
   - id - уникальный идентификатор
   - name - машинное имя типа контента
   - label - отображаемое имя
   - description - описание
   - slug - URL-сегмент
   - icon - иконка
   - menu_position - позиция в меню
   - is_active - статус активности

2. **content_type_fields** - поля типов контента
   - id - уникальный идентификатор
   - content_type_id - ID типа контента
   - name - машинное имя поля
   - label - отображаемое имя
   - field_type - тип поля (text, textarea, number, select, etc.)
   - description - описание
   - placeholder - плейсхолдер
   - default_value - значение по умолчанию
   - options - настройки поля (JSON)
   - is_required - обязательное ли поле
   - validation - правила валидации (JSON)
   - order - порядок отображения

3. **content** - записи контента
   - id - уникальный идентификатор
   - content_type_id - ID типа контента
   - title - заголовок
   - slug - URL-сегмент
   - status - статус (draft, published, etc.)
   - author_id - ID автора
   - created_at - дата создания
   - updated_at - дата обновления
   - published_at - дата публикации

4. **content_field_values** - значения полей контента
   - id - уникальный идентификатор
   - content_id - ID записи контента
   - field_id - ID поля
   - value - значение поля

## Типы полей

CMS поддерживает различные типы полей для хранения контента:

1. **Текстовые поля**
   - Однострочное текстовое поле (text)
   - Многострочное текстовое поле (textarea)
   - Визуальный редактор (wysiwyg)

2. **Числовые поля**
   - Число (number)
   - Диапазон (range)

3. **Выбор из списка**
   - Выпадающий список (select)
   - Флажки (checkbox)
   - Радиокнопки (radio)

4. **Дата и время**
   - Дата (date)
   - Время (time)
   - Дата и время (datetime)

5. **Файлы и медиа**
   - Изображение (image)
   - Файл (file)
   - Галерея (gallery)

## Интеграция и расширение

### Создание нового модуля

1. Создайте директорию для модуля в `backend/modules/`
2. Создайте файлы структуры модуля:
   - module.php - информация о модуле
   - init.php - инициализация модуля
   - Module.php - основной класс модуля

3. Пример класса модуля:

```php
namespace Modules\MyModule;

use Core\BaseModule;

class Module extends BaseModule {
    public function init() {
        // Инициализация модуля
        $this->setInfo(require __DIR__ . '/module.php');
        
        // Регистрация обработчиков событий
        $this->registerEventHandler('content.after_save', [$this, 'onContentSave']);
        
        // Регистрация API-маршрутов
        $this->registerApiRoute('GET', '/my-module/data', [$this, 'getData']);
    }
    
    public function onContentSave($content) {
        // Обработка события сохранения контента
    }
    
    public function getData() {
        // Метод API
        return [
            'success' => true,
            'data' => ['key' => 'value']
        ];
    }
}
```

### Расширение API

Для добавления новых маршрутов в API используйте событие `api.register_route`:

```php
$core->getEventManager()->on('api.register_route', function($params) {
    if ($params['method'] === 'GET' && $params['endpoint'] === '/custom/route') {
        return function() {
            return ['success' => true, 'data' => 'Custom data'];
        };
    }
    return null;
});
```

### Кастомизация интерфейса администратора

Интерфейс администратора можно расширять, добавляя собственные компоненты React. Новые страницы и элементы управления можно интегрировать через систему роутинга React. 
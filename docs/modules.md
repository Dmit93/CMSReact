# Модульная система Universal CMS

Модульная система позволяет расширять функциональность CMS с помощью подключаемых модулей. В этой документации описана структура модулей, API для работы с ними и интеграция с сайтом.

## Оглавление
1. [Структура модуля](#структура-модуля)
2. [Создание модуля](#создание-модуля)
3. [Жизненный цикл модуля](#жизненный-цикл-модуля)
4. [Хуки и события](#хуки-и-события)
5. [API модулей](#api-модулей)
6. [Интеграция с темами](#интеграция-с-темами)
7. [Управление модулями в админ-панели](#управление-модулями-в-админ-панели)

## Структура модуля

Все модули располагаются в директории `backend/modules/`. Каждый модуль имеет свою отдельную директорию с уникальным именем. Минимальная структура модуля выглядит так:

```
backend/modules/MyModule/
  ├── module.php         # Описание модуля
  ├── Module.php         # Основной класс модуля
  ├── init.php           # Скрипт инициализации
  ├── migrations/        # Миграции для базы данных
  │    └── 001_create_tables.php
  ├── models/            # Модели данных
  ├── controllers/       # Контроллеры
  ├── views/             # Шаблоны представлений
  ├── assets/            # Статические файлы (CSS, JS, изображения)
  ├── install.php        # Скрипт установки (опционально)
  ├── uninstall.php      # Скрипт удаления (опционально)
  ├── activate.php       # Скрипт активации (опционально)
  └── deactivate.php     # Скрипт деактивации (опционально)
```

## Создание модуля

### 1. Описание модуля (module.php)

Этот файл должен возвращать массив с описанием модуля:

```php
<?php
return [
    'id' => 'my-module',           // Уникальный идентификатор модуля (slug)
    'name' => 'Мой модуль',        // Отображаемое имя модуля
    'description' => 'Описание модуля для примера',
    'version' => '1.0.0',          // Версия модуля
    'author' => 'Имя разработчика',
    'author_url' => 'https://example.com',
    'dependencies' => [           // Зависимости модуля (другие модули)
        'core'                    // Например, зависимость от модуля 'core'
    ],
    'config' => [                 // Настройки модуля по умолчанию
        'setting1' => 'value1',
        'setting2' => true
    ]
];
```

### 2. Основной класс модуля (Module.php)

```php
<?php
namespace Modules\MyModule;

use Core\BaseModule;
use Core\Logger;

/**
 * Основной класс модуля
 */
class Module extends BaseModule {
    /**
     * Инициализация модуля
     */
    public function init() {
        // Загружаем информацию о модуле
        $moduleInfo = include __DIR__ . '/module.php';
        $this->setInfo($moduleInfo);
        
        // Регистрируем обработчики событий
        $this->registerEventHandlers();
        
        // Подключаем модели и контроллеры
        $this->includeModels();
        
        // Логируем загрузку
        Logger::getInstance()->info('MyModule initialized', [
            'version' => $this->moduleInfo['version']
        ]);
    }
    
    /**
     * Регистрация обработчиков событий
     */
    private function registerEventHandlers() {
        // Подключение к хукам
        $this->registerEventHandler('theme.render.content', [$this, 'onRenderContent']);
        $this->registerEventHandler('admin.menu.build', [$this, 'onAdminMenuBuild']);
    }
    
    /**
     * Обработчик события рендеринга контента
     */
    public function onRenderContent($params) {
        // Логика обработки события
        return $params;
    }
    
    /**
     * Обработчик события построения меню админ-панели
     */
    public function onAdminMenuBuild($menu) {
        // Добавляем пункт меню для модуля
        $menu['items'][] = [
            'id' => 'my-module',
            'title' => 'Мой модуль',
            'icon' => 'puzzle-piece',
            'order' => 100,
            'link' => '/admin/my-module'
        ];
        
        return $menu;
    }
}
```

### 3. Инициализация модуля (init.php)

```php
<?php
namespace Modules\MyModule;

// Подключаем класс модуля
require_once __DIR__ . '/Module.php';

// Создаем экземпляр модуля
$module = new Module();

return $module;
```

### 4. Миграции для базы данных

Миграции используются для создания и изменения таблиц базы данных, необходимых для модуля. Все миграции должны быть в каталоге `migrations/`.

```php
<?php
namespace Modules\MyModule\Migrations;

use Core\ModuleMigration;

/**
 * Миграция для создания таблиц модуля
 */
class CreateTables extends ModuleMigration {
    /**
     * Применение миграции
     */
    public function up() {
        $table = "
        CREATE TABLE IF NOT EXISTS `my_module_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $this->execute($table);
    }
    
    /**
     * Откат миграции
     */
    public function down() {
        $dropTable = "DROP TABLE IF EXISTS `my_module_items`;";
        return $this->execute($dropTable);
    }
}
```

## Жизненный цикл модуля

### Установка модуля

При установке модуля происходит следующее:
1. Модуль копируется в директорию `backend/modules/`
2. Запускаются миграции из директории `migrations/`
3. Выполняется скрипт `install.php` (если существует)
4. Информация о модуле добавляется в таблицу `modules` со статусом "inactive"

### Активация модуля

При активации модуля:
1. Статус модуля в таблице `modules` меняется на "active"
2. Выполняется скрипт `activate.php` (если существует)
3. Модуль будет запускаться при каждом запросе к сайту

### Деактивация модуля

При деактивации:
1. Статус модуля в таблице `modules` меняется на "inactive"
2. Выполняется скрипт `deactivate.php` (если существует)
3. Модуль больше не будет запускаться при запросах к сайту

### Удаление модуля

При удалении:
1. Выполняется скрипт `uninstall.php` (если существует)
2. Выполняются миграции отката (down метод)
3. Информация о модуле удаляется из таблицы `modules`

## Хуки и события

Система хуков позволяет модулям взаимодействовать с ядром CMS и другими модулями. Вот основные хуки:

### Хуки ядра

- `core.init` - Вызывается при инициализации ядра
- `core.shutdown` - Вызывается перед завершением работы приложения

### Хуки админ-панели

- `admin.init` - Инициализация админ-панели
- `admin.menu.build` - Построение меню админ-панели
- `admin.dashboard.widgets` - Добавление виджетов на дашборд
- `admin.header` - Вывод в заголовке админки
- `admin.footer` - Вывод в подвале админки

### Хуки фронтенда

- `frontend.init` - Инициализация фронтенда
- `theme.render.before` - Перед рендерингом темы
- `theme.render.content` - При рендеринге контента
- `theme.render.after` - После рендеринга темы
- `theme.header` - Вывод в заголовке сайта
- `theme.footer` - Вывод в подвале сайта

### Хуки API

- `api.before_register_routes` - Перед регистрацией маршрутов API
- `api.after_register_routes` - После регистрации маршрутов API
- `api.before_request` - Перед обработкой запроса API
- `api.after_request` - После обработки запроса API
- `api.error` - При ошибке в API

### Регистрация обработчика события

```php
// В классе модуля
$this->registerEventHandler('event.name', [$this, 'methodName']);

// Или напрямую через EventManager
\Core\EventManager::getInstance()->on('event.name', function($params) {
    // Обработка события
    return $params;
});
```

### Вызов события

```php
// Вызов события с параметрами
$result = \Core\Core::getInstance()->trigger('event.name', [
    'param1' => 'value1',
    'param2' => 'value2'
]);
```

## API модулей

### Получение экземпляра модуля

```php
// Получение экземпляра модуля по ID
$moduleManager = \Core\ModuleManager::getInstance();
$myModule = $moduleManager->getModule('my-module');

// Проверка, активирован ли модуль
if ($myModule) {
    // Модуль активен, можно использовать его методы
    $result = $myModule->someMethod();
} else {
    // Модуль не активен или не установлен
}
```

### API для работы с данными модуля

В ваших модулях вы можете создавать собственные модели для работы с данными:

```php
<?php
namespace Modules\MyModule\Models;

use API\Database;

/**
 * Модель для работы с данными модуля
 */
class MyItemModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($options = []) {
        $query = "SELECT * FROM my_module_items ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }
    
    public function getById($id) {
        $query = "SELECT * FROM my_module_items WHERE id = ? LIMIT 1";
        return $this->db->fetch($query, [$id]);
    }
    
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('my_module_items', $data);
    }
    
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('my_module_items', $data, "id = ?", [$id]);
    }
    
    public function delete($id) {
        return $this->db->query("DELETE FROM my_module_items WHERE id = ?", [$id]);
    }
}
```

### Доступ к настройкам модуля

```php
// В классе модуля
$setting = $this->getConfig('setting_name', 'default_value');

// Или через ModuleManager
$moduleManager = \Core\ModuleManager::getInstance();
$module = $moduleManager->getModule('my-module');
$setting = $module->getConfig('setting_name', 'default_value');
```

### Обновление настроек модуля

```php
// В классе модуля
$this->setConfig('setting_name', 'new_value');

// Сохранение настроек в базу данных
$this->saveConfig();
```

## Интеграция с темами

### Добавление шаблонов темы для модуля

Чтобы интегрировать модуль с темой, вы можете создать шаблоны для темы в директории:

```
backend/themes/default/templates/my-module/
  ├── index.php       # Основной шаблон
  ├── item.php        # Шаблон для отдельного элемента
  └── category.php    # Шаблон для категории
```

### Регистрация маршрутов для фронтенда

```php
// В методе init() модуля
public function init() {
    // ...
    
    // Регистрация маршрутов фронтенда
    $this->registerEventHandler('frontend.routes', [$this, 'registerRoutes']);
}

public function registerRoutes($routes) {
    $routes[] = [
        'path' => '/my-module',
        'handler' => 'Modules\MyModule\Controllers\FrontendController@index'
    ];
    
    $routes[] = [
        'path' => '/my-module/item/{id}',
        'handler' => 'Modules\MyModule\Controllers\FrontendController@item'
    ];
    
    return $routes;
}
```

### Добавление данных для ThemeRenderer

```php
// В обработчике события theme.render.content
public function onRenderContent($params) {
    if (isset($params['renderer'])) {
        $renderer = $params['renderer'];
        
        // Добавляем глобальную переменную для проверки наличия модуля
        $renderer->addData('my_module_enabled', true);
        
        // Добавляем данные модуля
        $renderer->addData('my_module_data', $this->getSomeData());
    }
    
    return $params;
}
```

### Использование данных модуля в шаблоне темы

```php
<?php if (isset($my_module_enabled) && $my_module_enabled): ?>
    <div class="my-module-container">
        <h2>Данные из моего модуля</h2>
        
        <?php if (isset($my_module_data) && !empty($my_module_data)): ?>
            <ul>
                <?php foreach ($my_module_data as $item): ?>
                    <li>
                        <a href="/my-module/item/<?php echo $item['id']; ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Нет данных для отображения</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

## Управление модулями в админ-панели

Админ-панель Universal CMS имеет встроенный раздел для управления модулями, который позволяет:

1. Просматривать список установленных модулей
2. Устанавливать новые модули
3. Активировать и деактивировать модули
4. Удалять модули

### Добавление страниц модуля в админ-панель

```php
// В обработчике события admin.menu.build
public function onAdminMenuBuild($menu) {
    $menu['items'][] = [
        'id' => 'my-module',
        'title' => 'Мой модуль',
        'icon' => 'puzzle-piece',
        'order' => 100,
        'submenu' => [
            [
                'id' => 'my-module-items',
                'title' => 'Элементы',
                'link' => '/admin/my-module/items'
            ],
            [
                'id' => 'my-module-settings',
                'title' => 'Настройки',
                'link' => '/admin/my-module/settings'
            ]
        ]
    ];
    
    return $menu;
}
```

### Создание страницы администрирования модуля

Для добавления страниц администрирования в админ-панель, создайте файлы в:

```
frontend/src/pages/MyModule/
  ├── Items.tsx       # Страница списка элементов
  ├── ItemForm.tsx    # Форма редактирования элемента
  └── Settings.tsx    # Страница настроек модуля
```

И добавьте маршруты для этих страниц в `App.tsx`:

```jsx
<Route path="my-module/items" element={<MyModuleItems />} />
<Route path="my-module/items/new" element={<MyModuleItemForm />} />
<Route path="my-module/items/:id/edit" element={<MyModuleItemForm />} />
<Route path="my-module/settings" element={<MyModuleSettings />} />
```

## Рекомендации и лучшие практики

1. **Уникальные префиксы**: Используйте префиксы для таблиц базы данных и CSS-классов, чтобы избежать конфликтов с другими модулями.

2. **Валидация зависимостей**: Перед установкой модуля проверяйте, установлены ли все необходимые зависимости.

3. **Чистая деинсталляция**: При удалении модуля убедитесь, что все созданные им данные и таблицы также удаляются, чтобы не оставлять "мусор" в базе данных.

4. **Использование хуков**: Используйте хуки для расширения функциональности, вместо прямого изменения файлов ядра.

5. **Кэширование данных**: Для оптимизации производительности кэшируйте результаты запросов к базе данных.

6. **Логирование ошибок**: Используйте систему логирования для отладки проблем в работе модуля.

7. **Документация модуля**: Создавайте документацию для вашего модуля, объясняющую его назначение, установку и использование.

## Пример полного модуля

Для примера полной структуры модуля вы можете изучить модуль Shop, который является частью базовой поставки Universal CMS. Он демонстрирует использование моделей, миграций, хуков и интеграцию с темами.

Путь к модулю: `backend/modules/Shop/` 
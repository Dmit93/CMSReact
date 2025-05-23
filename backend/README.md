# Universal CMS - Бэкенд

Это серверная часть универсальной модульной CMS с REST API на PHP.

## Возможности

- **Модульная архитектура**: Легко расширяемая система с разделением на модули
- **REST API**: Полноценный REST API для взаимодействия с фронтендом
- **Аутентификация и авторизация**: JWT-токены для безопасной аутентификации пользователей
- **Управление пользователями**: Полный функционал управления пользователями с ролями и правами
- **Управление контентом**: Гибкая система для создания различных типов контента
- **Медиа-библиотека**: Работа с медиа-файлами (изображения, документы, видео)
- **SEO**: Встроенные инструменты для оптимизации SEO
- **CRM**: Функциональность для работы с клиентами и заказами
- **Интернет-магазин**: Управление товарами, категориями, заказами
- **Настраиваемые поля**: Возможность создавать различные типы записей с кастомными полями
- **Меню и страницы**: Управление меню сайта и страницами
- **Интеграции**: Возможность интеграции с внешними сервисами

## Требования

- PHP 8 или выше
- MySQL 5.7 или выше
- Composer
- Apache или Nginx с mod_rewrite

## Установка

1. Клонировать репозиторий:
```
git clone https://github.com/yourusername/universal-cms.git
cd universal-cms/backend
```

2. Настроить виртуальный хост Apache/Nginx для директории /backend/api, указав ее как корневую директорию.

3. Создать базу данных и импортировать структуру:
```
mysql -u root -p
CREATE DATABASE cms_database;
exit;
mysql -u root -p cms_database < database.sql
```

4. Настроить конфигурационные файлы:
   - Отредактировать `config/database.php` для настройки подключения к базе данных
   - Отредактировать `config/app.php` для основных настроек приложения (ключ JWT, URL и т.д.)

5. Убедиться, что директория `/uploads` доступна для записи веб-сервером:
```
chmod 755 uploads
```

## Структура проекта

- `/api` - Основные файлы API и точка входа
- `/config` - Конфигурационные файлы
- `/controllers` - Контроллеры для обработки запросов
- `/models` - Модели для работы с данными
- `/uploads` - Директория для загружаемых файлов

## API Endpoints

### Аутентификация
- `POST /api/login` - Вход в систему
- `POST /api/register` - Регистрация нового пользователя
- `GET /api/me` - Получение информации о текущем пользователе

### Пользователи
- `GET /api/users` - Список пользователей (только для админа)
- `GET /api/users/{id}` - Информация о пользователе
- `POST /api/users` - Создание нового пользователя (только для админа)
- `PUT /api/users/{id}` - Обновление пользователя
- `DELETE /api/users/{id}` - Удаление пользователя (только для админа)

## Безопасность

- Все пароли хешируются с использованием PHP `password_hash`
- Для аутентификации используются JWT токены
- Реализована защита от CSRF и XSS атак
- Реализована система ролей и прав доступа

## Расширение функциональности

Для добавления новых модулей необходимо:
1. Создать новую модель в директории `/models`
2. Создать контроллер в директории `/controllers`
3. Добавить новые маршруты в файл `/api/index.php`
4. При необходимости добавить новые таблицы в базу данных

## Разработка и поддержка

Для обновления CMS регулярно проверяйте репозиторий на наличие новых версий. 
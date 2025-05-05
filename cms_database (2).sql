-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 05 2025 г., 21:47
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cms_database`
--

-- --------------------------------------------------------

--
-- Структура таблицы `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `content_type_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `author_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `template_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `content`
--

INSERT INTO `content` (`id`, `content_type_id`, `title`, `slug`, `status`, `content`, `author_id`, `created_at`, `updated_at`, `published_at`, `template_name`) VALUES
(1, 2, 'Прямое обновление - 18:50:44', 'direct-update-1746291044', 'published', '{\"html\":\"Test update at 2025-05-02 19:49:04\",\"timestamp\":\"2025-05-02 21:18:47\",\"force_update_681501b2301fe\":true,\"force_update_681501b9be853\":true,\"force_update_681501bf51438\":true,\"force_update_681501c8f037a\":true,\"force_update_681501d74e5c1\":true,\"force_update_681501dcc178f\":true,\"force_update_681502eacd7ac\":true,\"force_update_681502f3325af\":true,\"force_update_681504d6d8885\":true,\"force_update_6815088c5813f\":true,\"force_update_681508a359388\":true,\"force_update_6815099e47f32\":true,\"force_update_681509d52d94b\":true,\"force_update_681509f9e5365\":true,\"force_update_68150a49ed4a8\":true,\"force_update_68150bf6a8872\":true,\"force_update_68150c7858b48\":true,\"force_update_68150c87ec100\":true}', 1, '2025-05-02 18:08:52', '2025-05-03 18:50:44', '2025-05-02 18:08:52', 'content_item'),
(2, 2, 'test (15:35:34)', 'test2', 'draft', '{\"html\":\"Test update at 2025-05-02 19:49:32\",\"timestamp\":\"2025-05-02 20:54:50\",\"force_update_6815019364570\":true,\"force_update_681504e628643\":true,\"force_update_6815050c1ef54\":true,\"force_update_6815069c900bc\":true,\"force_update_681506eac0bf2\":true}', 1, '2025-05-02 18:28:51', '2025-05-03 15:35:34', NULL, 'content_item'),
(3, 1, 'test', 'test', 'draft', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-02 18:35:39', '2025-05-02 18:35:39', NULL, 'content_item'),
(6, 1, 'hg12 (21:42:43)', 'hhhh', 'draft', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-02 20:32:27', '2025-05-03 20:42:43', NULL, 'content_item'),
(7, 2, 'thny12', 'thny123333', 'draft', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-02 20:54:46', '2025-05-02 20:54:46', NULL, 'content_item'),
(17, 2, 'tt23', 'tt23', 'draft', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-03 15:34:50', '2025-05-03 15:34:51', NULL, 'content_item'),
(21, 1, 'ууаау (19:02:31)', 'da detka', 'published', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-03 15:54:06', '2025-05-04 18:02:31', NULL, 'content_item'),
(23, 1, 'Обновленная запись 2025-05-03 15:06:29 (21:43:36)', 'test-1746277589', 'draft', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-03 15:06:29', '2025-05-03 20:43:36', NULL, 'content_item'),
(24, 1, 'новый ага', 't123', 'published', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-05 09:06:52', '2025-05-05 09:06:54', '2025-05-05 09:06:52', NULL),
(25, 1, 'ura123 (12:06:18)', 'g45t', 'draft', '{\"html\":\"<p>Содержимое записи<\\/p>\"}', 1, '2025-05-05 10:44:41', '2025-05-05 11:06:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `content_field_values`
--

CREATE TABLE `content_field_values` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID контента',
  `field_id` int(11) NOT NULL COMMENT 'ID поля',
  `value` text DEFAULT NULL COMMENT 'Значение поля',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `content_field_values`
--

INSERT INTO `content_field_values` (`id`, `content_id`, `field_id`, `value`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Прямое обновление поля 18:50:44', '2025-05-02 19:08:52', '2025-05-03 18:50:44'),
(2, 2, 2, '', '2025-05-02 19:28:51', '2025-05-02 19:28:51'),
(3, 3, 5, '', '2025-05-02 19:35:39', '2025-05-02 19:35:39'),
(4, 3, 6, '', '2025-05-02 19:35:39', '2025-05-02 19:35:39'),
(7, 6, 5, '12323', '2025-05-02 21:32:27', '2025-05-03 20:42:43'),
(8, 6, 6, 'hh123', '2025-05-02 21:32:27', '2025-05-03 20:42:43'),
(9, 7, 2, '', '2025-05-02 21:54:46', '2025-05-02 21:54:46'),
(31, 17, 2, 'tt23', '2025-05-03 15:34:50', '2025-05-03 15:34:50'),
(32, 17, 7, 'tt23', '2025-05-03 15:34:50', '2025-05-03 15:34:50'),
(39, 21, 5, 'ty krut', '2025-05-03 15:54:06', '2025-05-04 18:02:31'),
(40, 21, 6, '1', '2025-05-03 15:54:06', '2025-05-04 18:02:31'),
(41, 23, 8, 'Обновленное значение для тестового поля 15:06:29', '2025-05-03 15:06:29', '2025-05-03 20:43:36'),
(42, 23, 9, 'Обновленное второе тестовое поле', '2025-05-03 15:06:29', '2025-05-03 20:43:36'),
(43, 23, 5, 'Обновленное описание 15:06:29', '2025-05-03 15:06:29', '2025-05-03 20:43:36'),
(44, 23, 10, 'Совершенно новое поле 15:06:29', '2025-05-03 15:06:29', '2025-05-03 20:43:36'),
(45, 21, 8, '2', '2025-05-03 20:31:56', '2025-05-04 18:02:31'),
(46, 21, 9, '3', '2025-05-03 20:31:56', '2025-05-04 18:02:31'),
(47, 21, 10, '4', '2025-05-03 20:31:56', '2025-05-04 18:02:31'),
(48, 6, 8, '', '2025-05-03 20:42:43', '2025-05-03 20:42:43'),
(49, 6, 9, '', '2025-05-03 20:42:43', '2025-05-03 20:42:43'),
(50, 6, 10, '', '2025-05-03 20:42:43', '2025-05-03 20:42:43'),
(51, 23, 6, '', '2025-05-03 20:43:36', '2025-05-03 20:43:36'),
(52, 24, 5, '', '2025-05-05 09:06:53', '2025-05-05 09:06:53'),
(53, 24, 6, '', '2025-05-05 09:06:53', '2025-05-05 09:06:53'),
(54, 24, 8, '', '2025-05-05 09:06:53', '2025-05-05 09:06:53'),
(55, 24, 9, '', '2025-05-05 09:06:53', '2025-05-05 09:06:53'),
(56, 24, 10, '', '2025-05-05 09:06:53', '2025-05-05 09:06:53'),
(57, 25, 5, '', '2025-05-05 10:44:41', '2025-05-05 11:06:18'),
(58, 25, 6, '', '2025-05-05 10:44:41', '2025-05-05 11:06:18'),
(59, 25, 8, '', '2025-05-05 10:44:41', '2025-05-05 11:06:18'),
(60, 25, 9, '', '2025-05-05 10:44:41', '2025-05-05 11:06:18'),
(61, 25, 10, '', '2025-05-05 10:44:41', '2025-05-05 11:06:18');

-- --------------------------------------------------------

--
-- Структура таблицы `content_tag`
--

CREATE TABLE `content_tag` (
  `content_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `content_types`
--

CREATE TABLE `content_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `label` varchar(128) NOT NULL COMMENT '''Отображаемое название типа контента''',
  `icon` varchar(64) NOT NULL COMMENT 'Иконка для типа контента',
  `is_active` tinyint(1) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields`)),
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `menu_position` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `content_types`
--

INSERT INTO `content_types` (`id`, `name`, `label`, `icon`, `is_active`, `slug`, `description`, `fields`, `created_at`, `updated_at`, `menu_position`) VALUES
(1, 'news', 'Новости', 'file-text', 1, 'news', '', '[]', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(2, 'projects', 'Проекты', 'file-text', 1, 'projects', '', '[]', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `content_type_fields`
--

CREATE TABLE `content_type_fields` (
  `id` int(11) NOT NULL,
  `content_type_id` int(11) NOT NULL COMMENT 'ID типа контента',
  `name` varchar(64) NOT NULL COMMENT 'Машинное имя поля',
  `label` varchar(128) NOT NULL COMMENT 'Отображаемое название поля',
  `field_type` varchar(32) NOT NULL COMMENT 'Тип поля (text, textarea, number, select, etc.)',
  `description` text DEFAULT NULL COMMENT 'Описание поля',
  `placeholder` varchar(255) DEFAULT NULL COMMENT 'Плейсхолдер для поля',
  `default_value` text DEFAULT NULL COMMENT 'Значение по умолчанию',
  `options` text DEFAULT NULL COMMENT 'Опции поля в JSON формате',
  `is_required` tinyint(1) DEFAULT 0 COMMENT 'Обязательное ли поле',
  `validation` text DEFAULT NULL COMMENT 'Правила валидации в JSON формате',
  `order` int(11) DEFAULT 0 COMMENT 'Порядок отображения поля',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `content_type_fields`
--

INSERT INTO `content_type_fields` (`id`, `content_type_id`, `name`, `label`, `field_type`, `description`, `placeholder`, `default_value`, `options`, `is_required`, `validation`, `order`, `created_at`, `updated_at`) VALUES
(2, 2, 'h12', 'Заголовок1', 'textarea', '', '', '', '{\"rows\":4}', 0, '[]', 0, '2025-05-01 17:59:51', '2025-05-02 21:23:14'),
(5, 1, 'description', 'Описание1', 'textarea', '', '', '', '{\"rows\":4}', 0, '[]', 0, '2025-05-02 08:50:23', '2025-05-03 14:46:32'),
(6, 1, 'test', 'test', 'wysiwyg', '', '', '', '[]', 0, '[]', 0, '2025-05-02 10:11:37', '2025-05-02 10:11:37'),
(7, 2, 'description', 'Description', 'text', NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-03 16:34:50', '2025-05-03 16:34:50'),
(8, 1, 'test_field', 'Test_field', 'text', NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-03 17:06:29', '2025-05-03 17:06:29'),
(9, 1, 'test_field2', 'Test_field2', 'text', NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-03 17:06:29', '2025-05-03 17:06:29'),
(10, 1, 'new_field', 'New_field23', 'text', '', '', '', '[]', 0, '[]', 0, '2025-05-03 17:06:29', '2025-05-03 21:05:05');

-- --------------------------------------------------------

--
-- Структура таблицы `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1.0.0',
  `installed_at` datetime DEFAULT NULL,
  `dependencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Зависимости модуля от других модулей' CHECK (json_valid(`dependencies`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `modules`
--

INSERT INTO `modules` (`id`, `name`, `slug`, `description`, `status`, `config`, `created_at`, `updated_at`, `version`, `installed_at`, `dependencies`) VALUES
(1, 'Default Fields', 'DefaultFields', 'Автоматически добавляет стандартные поля к типам контента', 'inactive', '[]', '2025-05-04 12:47:57', '2025-05-04 17:06:53', '1.0.0', '2025-05-04 12:47:57', NULL),
(2, 'Core Module', 'core', 'Базовый модуль системы, предоставляющий основные функции и API', 'active', '[]', '2025-05-04 17:04:21', '2025-05-04 17:56:11', '1.0.0', '2025-05-04 17:04:21', NULL),
(3, 'Shop', 'Shop', 'Интернет-магазин', 'active', '[]', '2025-05-04 17:04:38', '2025-05-05 15:02:40', '1.0.0', '2025-05-04 17:04:38', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `shipping` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `shipping_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shipping_address`)),
  `billing_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`billing_address`)),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `template` varchar(100) DEFAULT 'default',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `author_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `title`, `slug`, `description`, `content`, `price`, `sale_price`, `sku`, `category_id`, `stock`, `status`, `featured`, `meta_title`, `meta_description`, `author_id`, `created_at`, `updated_at`, `published_at`) VALUES
(1, 'нуа23123123123213', 'smartphone-xyz-pro', 'Мощный смартфон с большим экраном и отличной камерой', '<p>Характеристики:</p><ul><li>Процессор: Octa-core</li><li>RAM: 8GB</li><li>Экран: 6.5\"</li><li>Камера: 48MP</li></ul>', 2933999.00, 27999.00, 'SP-XYZ-0013123123', NULL, 25, 'published', 1, 'Смартфон XYZ Pro - Купить', 'Купить смартфон XYZ Pro с доставкой. Гарантия качества.', 1, '2025-05-04 17:04:38', '2025-05-05 15:15:17', '2025-05-04 17:04:38'),
(2, 'Футболка Classic Fit', 'tshirt-classic-fit', 'Классическая футболка из 100% хлопка', '<p>Особенности:</p><ul><li>Материал: 100% хлопок</li><li>Размеры: S, M, L, XL</li><li>Цвета: белый, черный, серый</li></ul>', 1200.00, NULL, 'CF-TSH-001', NULL, 100, 'published', 0, 'Футболка Classic Fit - Купить', 'Купить футболку Classic Fit с доставкой. Высокое качество.', 1, '2025-05-04 17:04:38', '2025-05-04 17:04:38', '2025-05-04 17:04:38'),
(3, 'Пиздеж', 'great-gatsby', 'Знаменитый роман Ф. Скотта Фицджеральда', '<p>О книге:</p><p>Классический роман американской литературы, написанный Фрэнсисом Скоттом Фицджеральдом и опубликованный в 1925 году.</p>', 7510.00, 650.00, 'BK-GG-001', NULL, 501, 'published', 1, 'Роман \"Великий Гэтсби\" - Купить', 'Купить роман \"Великий Гэтсби\" Ф. Скотта Фицджеральда с доставкой.', 1, '2025-05-04 17:04:38', '2025-05-04 20:43:50', '2025-05-04 17:04:38'),
(33, 'Тестовый товар 1746447799', 'test-product-1746447799', 'Описание тестового товара12', NULL, 1000.00, NULL, 'TEST5200', NULL, 50, 'published', 0, NULL, NULL, 1, '2025-05-05 14:23:19', '2025-05-05 21:43:11', NULL),
(34, '213', '213', '', NULL, 3.00, NULL, '123', 3, 4, 'published', 0, NULL, NULL, 1, '2025-05-05 12:29:42', '2025-05-05 12:29:42', NULL),
(35, 'УРРАРАРРА', 'urrararra', '', NULL, 10.00, NULL, 'ura', 2, 20, 'published', 0, NULL, NULL, 1, '2025-05-05 12:30:03', '2025-05-05 12:30:03', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `status`, `parent_id`, `image`, `featured`, `sort_order`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Электроника', 'electronics', 'Компьютеры, смартфоны, планшеты и аксессуары', 'active', NULL, NULL, 0, 0, NULL, NULL, '2025-05-04 17:04:38', '2025-05-04 17:04:38'),
(2, 'Одежда', 'clothing', 'Мужская и женская одежда, обувь и аксессуары', 'active', NULL, NULL, 0, 0, NULL, NULL, '2025-05-04 17:04:38', '2025-05-04 17:04:38'),
(3, 'Книги', 'books', 'Художественная и учебная литература, журналы', 'active', NULL, NULL, 0, 0, NULL, NULL, '2025-05-04 17:04:38', '2025-05-05 17:35:21'),
(4, 'Тестовая категория 9241', 'test-category-7854', 'Описание тестовой категории', 'active', 3, NULL, 0, 0, NULL, NULL, '2025-05-05 15:36:35', '2025-05-05 17:32:55'),
(5, 'test2', 'test1', '123412', '', 3, NULL, 0, 0, NULL, NULL, '2025-05-05 17:22:26', '2025-05-05 17:32:08');

-- --------------------------------------------------------

--
-- Структура таблицы `product_category`
--

CREATE TABLE `product_category` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `product_category`
--

INSERT INTO `product_category` (`product_id`, `category_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(33, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `product_media`
--

CREATE TABLE `product_media` (
  `product_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'CMS Site', 'Название сайта', '2025-05-03 18:09:46', '2025-05-03 19:07:51'),
(2, 'site_description', 'Описание сайта на базе CMS', 'Описание сайта', '2025-05-03 18:09:46', '2025-05-03 18:09:46'),
(3, 'active_theme', 'default', 'Активная тема сайта', '2025-05-03 18:09:46', '2025-05-03 18:09:46'),
(4, 'posts_per_page', '102', 'Количество записей на страницу', '2025-05-03 18:09:46', '2025-05-03 18:52:27'),
(5, 'allow_comments', '1', 'Разрешить комментарии (0 - нет, 1 - да)', '2025-05-03 18:09:46', '2025-05-03 18:09:46'),
(6, 'timezone', 'Europe/Moscow', 'Часовой пояс сайта', '2025-05-03 18:09:46', '2025-05-03 18:09:46');

-- --------------------------------------------------------

--
-- Структура таблицы `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','editor','user') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$/mTU7mSKiJGp1jhbo13.weneEMPwmk/etzGMVJqK81uQSHa/s20BC', 'admin', 'active', '2025-05-04 17:58:27', '2025-05-01 00:53:19', '2025-05-01 00:53:19');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_slug_unique` (`content_type_id`,`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- Индексы таблицы `content_field_values`
--
ALTER TABLE `content_field_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_field` (`content_id`,`field_id`),
  ADD KEY `fk_value_field` (`field_id`);

--
-- Индексы таблицы `content_tag`
--
ALTER TABLE `content_tag`
  ADD PRIMARY KEY (`content_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Индексы таблицы `content_types`
--
ALTER TABLE `content_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `content_type_fields`
--
ALTER TABLE `content_type_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_type_field_name` (`content_type_id`,`name`);

--
-- Индексы таблицы `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_email_unique` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Индексы таблицы `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `image_id` (`image`);

--
-- Индексы таблицы `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`product_id`,`media_id`),
  ADD KEY `media_id` (`media_id`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `content_field_values`
--
ALTER TABLE `content_field_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT для таблицы `content_types`
--
ALTER TABLE `content_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `content_type_fields`
--
ALTER TABLE `content_type_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT для таблицы `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_ibfk_1` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `content_field_values`
--
ALTER TABLE `content_field_values`
  ADD CONSTRAINT `fk_value_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_value_field` FOREIGN KEY (`field_id`) REFERENCES `content_type_fields` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `content_tag`
--
ALTER TABLE `content_tag`
  ADD CONSTRAINT `content_tag_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `content_type_fields`
--
ALTER TABLE `content_type_fields`
  ADD CONSTRAINT `fk_field_content_type` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pages_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `product_category`
--
ALTER TABLE `product_category`
  ADD CONSTRAINT `product_category_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `product_media`
--
ALTER TABLE `product_media`
  ADD CONSTRAINT `product_media_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_media_ibfk_2` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

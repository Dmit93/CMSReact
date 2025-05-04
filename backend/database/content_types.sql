-- Таблица типов контента
CREATE TABLE IF NOT EXISTS `content_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT 'Машинное имя типа контента',
  `label` varchar(128) NOT NULL COMMENT 'Отображаемое название типа контента',
  `description` text COMMENT 'Описание типа контента',
  `slug` varchar(64) NOT NULL COMMENT 'URL сегмент для типа контента',
  `icon` varchar(64) DEFAULT NULL COMMENT 'Иконка для типа контента',
  `menu_position` int(11) DEFAULT '0' COMMENT 'Позиция в меню',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Активен ли тип контента',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица полей типов контента
CREATE TABLE IF NOT EXISTS `content_type_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type_id` int(11) NOT NULL COMMENT 'ID типа контента',
  `name` varchar(64) NOT NULL COMMENT 'Машинное имя поля',
  `label` varchar(128) NOT NULL COMMENT 'Отображаемое название поля',
  `field_type` varchar(32) NOT NULL COMMENT 'Тип поля (text, textarea, number, select, etc.)',
  `description` text COMMENT 'Описание поля',
  `placeholder` varchar(255) DEFAULT NULL COMMENT 'Плейсхолдер для поля',
  `default_value` text COMMENT 'Значение по умолчанию',
  `options` text COMMENT 'Опции поля в JSON формате',
  `is_required` tinyint(1) DEFAULT '0' COMMENT 'Обязательное ли поле',
  `validation` text COMMENT 'Правила валидации в JSON формате',
  `order` int(11) DEFAULT '0' COMMENT 'Порядок отображения поля',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_type_field_name` (`content_type_id`, `name`),
  CONSTRAINT `fk_field_content_type` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица контента
CREATE TABLE IF NOT EXISTS `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type_id` int(11) NOT NULL COMMENT 'ID типа контента',
  `title` varchar(255) NOT NULL COMMENT 'Заголовок контента',
  `slug` varchar(255) NOT NULL COMMENT 'URL-сегмент',
  `status` varchar(32) NOT NULL DEFAULT 'draft' COMMENT 'Статус (draft, published, etc.)',
  `author_id` int(11) NOT NULL COMMENT 'ID автора',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL COMMENT 'Дата публикации',
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_type_slug` (`content_type_id`, `slug`),
  CONSTRAINT `fk_content_type` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`),
  CONSTRAINT `fk_content_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица значений полей контента
CREATE TABLE IF NOT EXISTS `content_field_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT 'ID контента',
  `field_id` int(11) NOT NULL COMMENT 'ID поля',
  `value` text COMMENT 'Значение поля',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_field` (`content_id`, `field_id`),
  CONSTRAINT `fk_value_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_value_field` FOREIGN KEY (`field_id`) REFERENCES `content_type_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
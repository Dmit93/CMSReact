-- Таблица настроек системы
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Базовые настройки
INSERT INTO `settings` (`name`, `value`, `description`) VALUES
('site_title', 'CMS Site', 'Название сайта'),
('site_description', 'Описание сайта на базе CMS', 'Описание сайта'),
('active_theme', 'default', 'Активная тема сайта'),
('posts_per_page', '10', 'Количество записей на страницу'),
('allow_comments', '1', 'Разрешить комментарии (0 - нет, 1 - да)'),
('timezone', 'Europe/Moscow', 'Часовой пояс сайта')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`); 
-- Добавление дополнительных полей в таблицу modules
ALTER TABLE `modules` 
ADD COLUMN `version` VARCHAR(20) NOT NULL DEFAULT '1.0.0' AFTER `description`,
ADD COLUMN `installed_at` DATETIME NULL AFTER `updated_at`,
ADD COLUMN `dependencies` JSON NULL COMMENT 'Зависимости модуля от других модулей' AFTER `config`;

-- Обновление существующих записей
UPDATE `modules` SET 
`version` = '1.0.0',
`installed_at` = `created_at`,
`dependencies` = '[]'
WHERE `version` IS NULL; 
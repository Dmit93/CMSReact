-- Добавление столбца menu_position в таблицу content_types
ALTER TABLE `content_types` ADD COLUMN `menu_position` INT DEFAULT 0 NOT NULL;

-- Обновление существующих записей, устанавливая menu_position равным id
UPDATE `content_types` SET `menu_position` = `id`; 
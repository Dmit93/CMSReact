-- Добавление поля template_name в таблицу content для хранения имени шаблона
ALTER TABLE content ADD COLUMN template_name VARCHAR(100) DEFAULT NULL;

-- Обновление существующих записей 
UPDATE content SET template_name = 'content_item' WHERE template_name IS NULL; 
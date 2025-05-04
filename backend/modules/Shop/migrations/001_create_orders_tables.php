<?php
namespace Modules\Shop\Migrations;

use Core\ModuleMigration;

/**
 * Миграция для создания таблиц заказов
 */
class CreateOrdersTables extends ModuleMigration {
    /**
     * Применение миграции
     */
    public function up() {
        // Таблица заказов
        $ordersTable = "
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL UNIQUE,
            `user_id` INT NULL,
            `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
            `total` DECIMAL(10, 2) NOT NULL DEFAULT 0,
            `discount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
            `tax` DECIMAL(10, 2) NOT NULL DEFAULT 0,
            `shipping_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0,
            `payment_method` VARCHAR(50) NULL,
            `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            `customer_notes` TEXT NULL,
            `admin_notes` TEXT NULL,
            `customer_name` VARCHAR(100) NOT NULL,
            `customer_email` VARCHAR(100) NOT NULL,
            `customer_phone` VARCHAR(20) NULL,
            `shipping_address` TEXT NULL,
            `billing_address` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            `completed_at` DATETIME NULL,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Таблица элементов заказа
        $orderItemsTable = "
        CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NULL,
            `name` VARCHAR(255) NOT NULL,
            `sku` VARCHAR(100) NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `price` DECIMAL(10, 2) NOT NULL,
            `options` JSON NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Таблица платежей
        $paymentsTable = "
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `amount` DECIMAL(10, 2) NOT NULL,
            `provider` VARCHAR(50) NOT NULL,
            `transaction_id` VARCHAR(255) NULL,
            `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            `payment_data` JSON NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Выполняем создание таблиц
        $result = $this->execute($ordersTable) &&
                  $this->execute($orderItemsTable) &&
                  $this->execute($paymentsTable);
                  
        return $result;
    }
    
    /**
     * Откат миграции
     */
    public function down() {
        // Удаляем таблицы в обратном порядке из-за внешних ключей
        $dropPayments = "DROP TABLE IF EXISTS `payments`;";
        $dropOrderItems = "DROP TABLE IF EXISTS `order_items`;";
        $dropOrders = "DROP TABLE IF EXISTS `orders`;";
        
        $result = $this->execute($dropPayments) &&
                  $this->execute($dropOrderItems) &&
                  $this->execute($dropOrders);
                  
        return $result;
    }
} 
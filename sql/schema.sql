-- ============================================================
-- Rentora - Database Schema
-- phpMyAdmin / cPanel SQL Command
-- Database name: rentora_db (তুমি cPanel থেকে তৈরি করবে)
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `equipment_listings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `owner_name` VARCHAR(100) NOT NULL,
    `owner_email` VARCHAR(100) NOT NULL,
    `owner_phone` VARCHAR(20) DEFAULT NULL,
    `equip_name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `location` VARCHAR(200) DEFAULT NULL,
    `status` ENUM('active','rented','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rental_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT DEFAULT NULL,
    `requested_item` VARCHAR(150) NOT NULL,
    `renter_name` VARCHAR(100) NOT NULL,
    `renter_email` VARCHAR(100) NOT NULL,
    `renter_phone` VARCHAR(20) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`equipment_id`) REFERENCES `equipment_listings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(200) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

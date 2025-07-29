-- Создание базы данных для сервиса коротких ссылок
CREATE DATABASE IF NOT EXISTS `yii2basic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `yii2basic`;

-- Таблица коротких ссылок
CREATE TABLE `short_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `original_url` text NOT NULL COMMENT 'Оригинальная ссылка',
    `short_code` varchar(10) NOT NULL COMMENT 'Короткий код',
    `qr_code_path` varchar(255) DEFAULT NULL COMMENT 'Путь к QR коду',
    `clicks_count` int(11) DEFAULT 0 COMMENT 'Количество переходов',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_short_links_short_code` (`short_code`),
    UNIQUE KEY `idx_short_links_original_url_unique` (`original_url`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Таблица коротких ссылок';

-- Таблица логов переходов
CREATE TABLE `link_clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `short_link_id` int(11) NOT NULL COMMENT 'ID короткой ссылки',
    `ip_address` varchar(45) NOT NULL COMMENT 'IP адрес',
    `user_agent` text DEFAULT NULL COMMENT 'User Agent',
    `referer` text DEFAULT NULL COMMENT 'Реферер',
    `clicked_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Время перехода',
    PRIMARY KEY (`id`),
    KEY `idx_link_clicks_short_link_id` (`short_link_id`),
    KEY `idx_link_clicks_ip_address` (`ip_address`),
    KEY `idx_link_clicks_clicked_at` (`clicked_at`),
    CONSTRAINT `fk_link_clicks_short_link_id` FOREIGN KEY (`short_link_id`) REFERENCES `short_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Таблица логов переходов по ссылкам'; 
-- db/schema.sql - MySQL schema for logging operations (no passwords stored)
CREATE DATABASE IF NOT EXISTS `password_checker` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `password_checker`;

CREATE TABLE IF NOT EXISTS `psc_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action_url` VARCHAR(1024) NULL,
  `score` TINYINT UNSIGNED NOT NULL,
  `rules_json` JSON NOT NULL,
  `checks_json` JSON NOT NULL,
  `user_agent` VARCHAR(512) NULL,
  `ip_hash` CHAR(64) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Store ONLY HMAC hashes of suggested passwords to enforce uniqueness
CREATE TABLE IF NOT EXISTS `psc_suggestions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash` CHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
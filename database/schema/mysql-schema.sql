/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `authorization_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorization_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `authorization_tokens_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_auto_sell_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_auto_sell_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_lender_client_id` bigint unsigned NOT NULL,
  `effective_start` date NOT NULL,
  `effective_end` date NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_with_effective_start_and_end` (`company_lender_client_id`,`effective_start`,`effective_end`),
  CONSTRAINT `client_auto_sell_periods_company_lender_client_id_foreign` FOREIGN KEY (`company_lender_client_id`) REFERENCES `company_lender_clients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commodity_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commodity_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unique_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `min_price` decimal(64,2) NOT NULL,
  `max_price` decimal(64,2) NOT NULL,
  `volume_sellable_unit` decimal(64,5) NOT NULL,
  `currency_id` bigint unsigned NOT NULL,
  `measurement_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `commodity_type_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commodity_items_company_id_foreign` (`company_id`),
  KEY `commodity_items_currency_id_foreign` (`currency_id`),
  KEY `commodity_items_measurement_id_foreign` (`measurement_id`),
  KEY `commodity_items_commodity_type_id_foreign` (`commodity_type_id`),
  CONSTRAINT `commodity_items_commodity_type_id_foreign` FOREIGN KEY (`commodity_type_id`) REFERENCES `commodity_types` (`id`),
  CONSTRAINT `commodity_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `commodity_items_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  CONSTRAINT `commodity_items_measurement_id_foreign` FOREIGN KEY (`measurement_id`) REFERENCES `measurements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commodity_type_statistics_view`;
/*!50001 DROP VIEW IF EXISTS `commodity_type_statistics_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `commodity_type_statistics_view` AS SELECT 
 1 AS `commodity_type_id`,
 1 AS `available_value`,
 1 AS `reserved_value`,
 1 AS `total_value`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `commodity_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commodity_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `unique_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lynk' COMMENT 'Provider of the commodity type, e.g., lynk, bursam',
  `status` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '1 => active  , 2 => inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `commodity_types_name_unique` (`name`),
  UNIQUE KEY `commodity_types_unique_name_unique` (`unique_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint unsigned NOT NULL DEFAULT '1',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unique_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_unique_name_type_unique` (`unique_name`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_commodity_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_commodity_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `commodity_type_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_commodity_types_company_id_foreign` (`company_id`),
  KEY `company_commodity_types_commodity_type_id_foreign` (`commodity_type_id`),
  CONSTRAINT `company_commodity_types_commodity_type_id_foreign` FOREIGN KEY (`commodity_type_id`) REFERENCES `commodity_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_commodity_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_lender_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_lender_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `national_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '1: Business, 2: Individual',
  `auto_complete_sell` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_lender_clients_company_id_foreign` (`company_id`),
  CONSTRAINT `company_lender_clients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_lender_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_lender_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `company_cr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contract_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notifications_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_market_type` tinyint unsigned DEFAULT NULL,
  `does_order_require_approval` tinyint(1) NOT NULL DEFAULT '0',
  `default_contract_sign_time_limit` int unsigned DEFAULT NULL COMMENT 'default measurement unit (hours)',
  `require_initiate_trade_request` tinyint(1) NOT NULL DEFAULT '0',
  `internal_status_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `public_status_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `auto_complete_murabaha_order` tinyint(1) NOT NULL DEFAULT '0',
  `token_expire_in` int DEFAULT NULL COMMENT 'Token expiration time in seconds',
  `token_version` int unsigned NOT NULL DEFAULT '1',
  `allowed_financing_order_types` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `trading_mode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'automatic',
  `force_unique_reference_number` tinyint(1) NOT NULL DEFAULT '0',
  `webhook_secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `allow_preferred_commodity_in_order` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_lender_details_company_cr_unique` (`company_cr`),
  UNIQUE KEY `company_lender_details_company_id_unique` (`company_id`),
  CONSTRAINT `company_lender_details_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_lender_order_allowed_commodity_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_lender_order_allowed_commodity_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `commodity_type_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloa_company_commodity_unique` (`company_id`,`commodity_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_supplier_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_supplier_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `market_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1 => local  , 2 => international',
  `status` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '1 => active  , 2 => inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commodity_suppliers_company_id_foreign` (`company_id`),
  CONSTRAINT `commodity_suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domains` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domains_domain_unique` (`domain`),
  KEY `domains_company_id_foreign` (`company_id`),
  CONSTRAINT `domains_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `edaat_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edaat_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `creator_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(64,0) NOT NULL,
  `currency` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `status` tinyint unsigned NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edaat_invoices_company_id_foreign` (`company_id`),
  KEY `edaat_invoices_creator_id_foreign` (`creator_id`),
  CONSTRAINT `edaat_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `edaat_invoices_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `user_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enquiries_user_id_foreign` (`user_id`),
  KEY `enquiries_role_id_foreign` (`role_id`),
  CONSTRAINT `enquiries_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enquiries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enquiry_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enquiry_replies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enquiry_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enquiry_replies_enquiry_id_foreign` (`enquiry_id`),
  KEY `enquiry_replies_user_id_foreign` (`user_id`),
  KEY `enquiry_replies_role_id_foreign` (`role_id`),
  CONSTRAINT `enquiry_replies_enquiry_id_foreign` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enquiry_replies_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `enquiry_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financing_order_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financing_order_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `status` tinyint unsigned NOT NULL,
  `creator_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financing_order_histories_order_id_created_at_index` (`order_id`,`created_at`),
  KEY `financing_order_histories_status_index` (`status`),
  KEY `financing_order_histories_creator_id_foreign` (`creator_id`),
  CONSTRAINT `financing_order_histories_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financing_order_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `financing_orders` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financing_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financing_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `type` tinyint NOT NULL DEFAULT '1',
  `contract_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(64,0) NOT NULL,
  `cost_with_vat` decimal(64,0) DEFAULT NULL,
  `cost_without_vat` decimal(64,0) DEFAULT NULL,
  `currency` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `selling_price` decimal(64,0) NOT NULL,
  `status` tinyint unsigned NOT NULL,
  `status_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lender_type` tinyint NOT NULL,
  `lender_identifier` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `borrower_type` tinyint NOT NULL,
  `borrower_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verification_required` tinyint(1) NOT NULL DEFAULT '1',
  `assignable_id` bigint unsigned DEFAULT NULL,
  `approver_id` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `creator_id` bigint unsigned DEFAULT NULL,
  `commodity_type_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `charged_trader_orders_count` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `financing_orders_company_id_foreign` (`company_id`),
  KEY `financing_orders_approver_id_foreign` (`approver_id`),
  KEY `financing_orders_creator_type_creator_id_index` (`creator_id`),
  KEY `financing_orders_assignable_id_foreign` (`assignable_id`),
  KEY `financing_orders_commodity_type_id_foreign` (`commodity_type_id`),
  CONSTRAINT `financing_orders_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `financing_orders_assignable_id_foreign` FOREIGN KEY (`assignable_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financing_orders_commodity_type_id_foreign` FOREIGN KEY (`commodity_type_id`) REFERENCES `commodity_types` (`id`),
  CONSTRAINT `financing_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_eligible_quantities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_eligible_quantities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `eligible_quantity` int NOT NULL,
  `touched_by` bigint unsigned DEFAULT NULL COMMENT 'Helper column for managing inventory lock and release during find-and-hold operations',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_company_unique` (`inventory_id`,`company_id`),
  KEY `company_quantity_idx` (`company_id`,`inventory_id`,`eligible_quantity`),
  CONSTRAINT `local_market_eligible_quantities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `local_market_eligible_quantities_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `local_market_inventories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `commodity_item_id` bigint unsigned NOT NULL,
  `commodity_type_id` bigint unsigned DEFAULT NULL,
  `supplier_location_id` bigint unsigned NOT NULL,
  `reserved_items` double NOT NULL DEFAULT '0',
  `available_quantity` double NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0|1|2',
  `is_editable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Indicates whether this inventory is editable during purchasing operations',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventories_commodity_item_id_foreign` (`commodity_item_id`),
  KEY `inventories_supplier_location_id_foreign` (`supplier_location_id`),
  KEY `inventories_company_id_index` (`company_id`),
  KEY `inventories_available_quantity_index` (`available_quantity`),
  KEY `idx_inventory_pk_only` (`id`),
  KEY `idx_inventory_stock_filter` (`available_quantity`,`reserved_items`,`status`),
  KEY `idx_inventory_hash` (`id`),
  KEY `local_market_inventories_commodity_type_id_index` (`commodity_type_id`),
  CONSTRAINT `inventories_commodity_item_id_foreign` FOREIGN KEY (`commodity_item_id`) REFERENCES `commodity_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventories_supplier_location_id_foreign` FOREIGN KEY (`supplier_location_id`) REFERENCES `supplier_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `local_market_inventories_commodity_type_id_foreign` FOREIGN KEY (`commodity_type_id`) REFERENCES `commodity_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_inventory_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_inventory_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `local_market_inventory_id` bigint unsigned NOT NULL,
  `commodity_item_id` bigint unsigned NOT NULL,
  `qr_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` smallint NOT NULL DEFAULT '0' COMMENT 'FREE=>1|RESERVED=>2',
  `current_owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_owner_type` smallint NOT NULL,
  `previous_owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_owner_type` smallint DEFAULT NULL,
  `last_completed_order_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `hold_for` bigint unsigned DEFAULT '0',
  `last_purchasing_order_id` bigint unsigned DEFAULT NULL,
  `last_action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_company_id_owners` json DEFAULT NULL,
  `previous_company_id_owner_0` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`previous_company_id_owners`,_utf8mb4'$[0]'))) STORED,
  `previous_company_id_owner_1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`previous_company_id_owners`,_utf8mb4'$[1]'))) STORED,
  `previous_company_id_owner_2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`previous_company_id_owners`,_utf8mb4'$[2]'))) STORED,
  `previous_company_id_owner_3` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`previous_company_id_owners`,_utf8mb4'$[3]'))) STORED,
  PRIMARY KEY (`id`,`local_market_inventory_id`),
  KEY `local_market_inventory_units_last_completed_order_id_foreign` (`last_completed_order_id`),
  KEY `inventory_units_hold_for_index` (`hold_for`,`id`,`local_market_inventory_id`,`deleted_at`),
  KEY `inventory_units_eligibility_index` (`local_market_inventory_id`,`status`,`hold_for`,`deleted_at`,`previous_company_id_owner_0`,`previous_company_id_owner_1`,`previous_company_id_owner_2`,`previous_company_id_owner_3`),
  KEY `units_completion_idx` (`hold_for`,`local_market_inventory_id`,`status`),
  KEY `units_ownership_idx` (`hold_for`,`current_owner`,`current_owner_type`),
  KEY `idx_units_count_covering` (`local_market_inventory_id`,`status`,`deleted_at`),
  KEY `idx_units_status_optimized` (`status`,`local_market_inventory_id`,`deleted_at`),
  KEY `idx_hold_for_id` (`hold_for`,`id`),
  KEY `local_market_inventory_units_last_purchasing_order_id_index` (`last_purchasing_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
/*!50100 PARTITION BY HASH (`local_market_inventory_id`)
PARTITIONS 8 */;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%`*/ /*!50003 TRIGGER `after_unit_ownership_change` AFTER UPDATE ON `local_market_inventory_units` FOR EACH ROW BEGIN
                IF (NEW.current_owner != OLD.current_owner OR NEW.current_owner_type != OLD.current_owner_type) THEN
                    INSERT INTO local_market_unit_ownership (
                        unit_id,
                        previous_owner,
                        previous_owner_type,
                        current_owner,
                        current_owner_type,
                        local_market_order_id,
                        action,
                        created_at,
                        updated_at
                    ) VALUES (
                        NEW.id,
                        OLD.current_owner,
                        OLD.current_owner_type,
                        NEW.current_owner,
                        NEW.current_owner_type,
                        OLD.hold_for,
                        NEW.last_action,
                        NOW(),
                        NOW()
                    );
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `local_market_order_has_cancel_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_order_has_cancel_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL COMMENT 'local_market_order_id',
  `cancelled_by` int NOT NULL,
  `cancel_reason` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local_market_order_has_cancel_reasons_order_id_foreign` (`order_id`),
  CONSTRAINT `local_market_order_has_cancel_reasons_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `local_market_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_order_has_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_order_has_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `local_market_order_id` bigint unsigned NOT NULL,
  `local_market_inventory_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(64,0) NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local_market_order_has_inventories_inventory_id_foreign` (`local_market_inventory_id`),
  KEY `local_market_order_has_inventories_supplier_id_foreign` (`supplier_id`),
  KEY `order_inventory_relation_idx` (`local_market_order_id`,`local_market_inventory_id`),
  CONSTRAINT `local_market_order_has_inventories_local_market_order_id_foreign` FOREIGN KEY (`local_market_order_id`) REFERENCES `local_market_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_order_has_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_order_has_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `local_market_order_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local_market_order_has_units_unit_id_foreign` (`unit_id`),
  KEY `local_market_order_has_units_inventory_id_foreign` (`inventory_id`),
  KEY `local_market_order_has_units_local_market_order_id_foreign` (`local_market_order_id`),
  CONSTRAINT `local_market_order_has_units_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `local_market_inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `local_market_order_has_units_local_market_order_id_foreign` FOREIGN KEY (`local_market_order_id`) REFERENCES `local_market_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_order_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_order_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `local_market_order_id` bigint unsigned NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local_market_order_histories_local_market_order_id_foreign` (`local_market_order_id`),
  CONSTRAINT `local_market_order_histories_local_market_order_id_foreign` FOREIGN KEY (`local_market_order_id`) REFERENCES `local_market_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(64,2) NOT NULL,
  `national_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lender_identifier` bigint unsigned DEFAULT NULL,
  `borrower_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` smallint unsigned NOT NULL DEFAULT '0',
  `preferred_commodity_type` json DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `external_order_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `buying_uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selling_uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_commodities_settled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `local_market_orders_company_id_foreign` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `local_market_unit_ownership`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_market_unit_ownership` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `local_market_order_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `current_owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_owner_type` smallint NOT NULL COMMENT 'Supplier => 1, Company => 2, Customer => 3',
  `previous_owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_owner_type` smallint DEFAULT NULL COMMENT 'Supplier => 1, Company => 2, Customer => 3',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `action` smallint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local_market_unit_ownership_unit_id_foreign` (`unit_id`),
  KEY `local_market_unit_ownership_local_market_order_id_foreign` (`local_market_order_id`),
  CONSTRAINT `local_market_unit_ownership_local_market_order_id_foreign` FOREIGN KEY (`local_market_order_id`) REFERENCES `local_market_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `measurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `measurements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_types_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otpify_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otpify_codes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `initiator_id` bigint unsigned DEFAULT NULL,
  `initiator_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otpifiable_id` bigint unsigned DEFAULT NULL,
  `otpifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiration_date` timestamp NOT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_resets_email_company_id_index` (`email`,`company_id`),
  KEY `password_resets_company_id_foreign` (`company_id`),
  CONSTRAINT `password_resets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expire_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_name_unique` (`group`,`name`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unique_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_locations_company_id_foreign` (`company_id`),
  CONSTRAINT `supplier_locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tiered_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tiered_pricing` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `order_value_start` decimal(64,0) NOT NULL,
  `order_value_end` decimal(64,0) DEFAULT NULL,
  `fee_type` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_cost_without_vat` decimal(64,0) DEFAULT NULL,
  `vat_amount` decimal(64,0) NOT NULL,
  `proration_amount` decimal(64,0) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tiered_pricing_company_id_foreign` (`company_id`),
  CONSTRAINT `tiered_pricing_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trader_order_id` bigint unsigned NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trader_histories_trader_order_id_foreign` (`trader_order_id`),
  KEY `trader_histories_order_id_latest_idx` (`trader_order_id`,`id`),
  CONSTRAINT `trader_histories_trader_order_id_foreign` FOREIGN KEY (`trader_order_id`) REFERENCES `trader_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_order_cancel_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_order_cancel_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` bigint unsigned DEFAULT NULL,
  `trader_order_id` bigint unsigned NOT NULL,
  `cancel_step` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cancel_reason` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cancel_type` tinyint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trader_order_cancel_details_cancelled_by_foreign` (`creator_id`),
  KEY `trader_order_cancel_details_trader_order_id_foreign` (`trader_order_id`),
  CONSTRAINT `trader_order_cancel_details_cancelled_by_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `trader_order_cancel_details_trader_order_id_foreign` FOREIGN KEY (`trader_order_id`) REFERENCES `trader_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_order_proceed_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_order_proceed_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trader_order_id` bigint unsigned NOT NULL,
  `case` tinyint NOT NULL COMMENT 'case of proceed the trader ',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `creator_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trader_order_proceed_cases_trader_order_id_index` (`trader_order_id`),
  CONSTRAINT `trader_order_proceed_cases_trader_order_id_foreign` FOREIGN KEY (`trader_order_id`) REFERENCES `trader_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_order_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_order_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trader_order_id` bigint unsigned NOT NULL,
  `is_commodities_settled` tinyint(1) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0' COMMENT 'Settlement check status: 0=pending, 1=in_progress, 2=completed, 3=failed',
  `creator_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trader_order_settlements_trader_order_id_foreign` (`trader_order_id`),
  KEY `trader_order_settlements_creator_id_foreign` (`creator_id`),
  CONSTRAINT `trader_order_settlements_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `trader_order_settlements_trader_order_id_foreign` FOREIGN KEY (`trader_order_id`) REFERENCES `trader_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_order_time_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_order_time_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trader_order_id` bigint unsigned NOT NULL,
  `type` smallint NOT NULL,
  `default_value` int NOT NULL COMMENT 'Default value in minutes',
  `effective_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` smallint NOT NULL DEFAULT '1',
  `action` tinyint NOT NULL DEFAULT '2' COMMENT 'Defines the action to be taken when the time limit is reached',
  PRIMARY KEY (`id`),
  KEY `trader_order_time_limits_trader_order_id_foreign` (`trader_order_id`),
  CONSTRAINT `trader_order_time_limits_trader_order_id_foreign` FOREIGN KEY (`trader_order_id`) REFERENCES `trader_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `financing_order_id` bigint unsigned NOT NULL,
  `mode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'automatic',
  `reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `commodity_type_id` bigint DEFAULT NULL,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'v1',
  `data` json DEFAULT NULL,
  `status` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contract_signed_type` smallint NOT NULL DEFAULT '1' COMMENT 'Sell=>2|Deliver=>',
  `auto_sell_period_id` bigint unsigned DEFAULT NULL,
  `last_history_action` int unsigned DEFAULT NULL COMMENT 'Cached last history action for performance optimization',
  `last_history_action_updated_at` timestamp NULL DEFAULT NULL COMMENT 'When the cached last history action was updated',
  `creator_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trader_orders_financing_order_id_foreign` (`financing_order_id`),
  KEY `trader_orders_auto_sell_period_id_foreign` (`auto_sell_period_id`),
  KEY `trader_orders_commodity_type_id_foreign` (`commodity_type_id`),
  KEY `trader_orders_reference_index` (`reference`),
  CONSTRAINT `trader_orders_auto_sell_period_id_foreign` FOREIGN KEY (`auto_sell_period_id`) REFERENCES `client_auto_sell_periods` (`id`),
  CONSTRAINT `trader_orders_financing_order_id_foreign` FOREIGN KEY (`financing_order_id`) REFERENCES `financing_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trader_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trader_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` json NOT NULL,
  `order` tinyint unsigned NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_owners` json DEFAULT (json_array()),
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notification_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_type_channel_unique` (`user_id`,`notification_type`,`channel`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_channel` (`channel`),
  CONSTRAINT `user_notification_settings_new_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_auto_verified` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `virtual_company_id_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat_ws(_utf8mb4':',`company_id`,`email`)) VIRTUAL,
  `locale` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `can_manage_orders` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_virtual_company_id_email_unique` (`virtual_company_id_email`),
  KEY `users_company_id_foreign` (`company_id`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_calls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` json DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `exception` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhooks_company_id_foreign` (`company_id`),
  CONSTRAINT `webhooks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zatca_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zatca_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `commodity_type_statistics_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `commodity_type_statistics_view` AS select 1 AS `commodity_type_id`,1 AS `available_value`,1 AS `reserved_value`,1 AS `total_value` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_09_15_000010_create_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_09_15_000020_create_domains_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2022_05_16_140720_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2022_05_17_112516_create_otpify_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2022_06_28_161212_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2022_06_28_163608_create_general_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2022_06_29_103344_create_super_admin_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2022_08_24_144323_create_authorization_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2022_10_09_071020_seed_with_default_roles_and_permissions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2022_10_09_075920_create_super_admin_for_first_time',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2022_10_10_093100_add_company_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2022_10_11_124416_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2022_10_11_125453_create_financing_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2022_10_17_093324_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2022_10_19_103502_add_company_id_column_to_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2022_10_24_114052_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2022_10_24_132649_create_trader_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2022_10_24_133047_create_trader_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2022_10_24_145333_create_lender_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2022_10_24_163608_add_order_cost_to_lender_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2022_10_25_134757_create_edaat_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2022_10_25_150334_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2022_10_31_123834_change_initiator_and_code_otpify_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2022_11_01_103302_create_enquiries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2022_11_01_163608_add_company_creation_status_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2022_11_02_133300_create_enquiry_replies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2022_11_06_094922_add_wakala_template_to_super_admin_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2022_11_08_141924_create_webhooks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2022_11_09_135351_create_project_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2022_11_13_141955_create_wallets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2022_11_13_141956_create_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2022_11_13_141958_create_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2022_11_14_071217_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2022_11_17_160326_rename_columns_lender_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2022_11_28_074401_change_national_id_type_in_financing_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2022_12_05_141956_add_reason_to_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2022_12_13_135931_change_data_column_to_meta_in_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2022_12_18_083633_change_user_and_role_id_in_enquiry_replies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2023_01_09_065503_add_type_column_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2023_01_10_081036_add_driver_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2023_01_10_085847_create_trader_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2023_01_25_081930_chnage_company_cr_column_to_nullable_in_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2023_01_25_100938_add_is_active_column_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2023_02_08_091443_add_email_to_companies_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2023_02_09_103404_create_webhook_calls_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2023_02_28_143637_add_trader_order_time_out_to_general_settings',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2023_03_12_071914_add_customer_name_to_financing_orders_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2023_02_09_042050_add_client_wakala_accepted_at_to_trader_orders_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2023_03_20_093531_update_email_for_all_user_deleted_in_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2023_03_26_105133_move_orders_to_in_progress',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2023_03_27_120222_add_deleted_at_in_personal_access_tokens_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2023_03_27_131814_add_deleted_at_in_media_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2023_03_21_132331_add_order_created_notify_enabled_to_lender_settings',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2023_03_21_132738_add_notify_about_new_orders_to_companies_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2023_05_08_115159_migrate_old_financing_orders_to_in_progress',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2023_04_02_090543_add_version_to_trader_orders_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2023_04_13_103115_create_provider_credential_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2023_05_14_094211_drop_provider_credential_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2023_06_18_071130_add_type_to_trader_orders_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2023_06_18_071130_add_mode_to_trader_orders_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2023_08_16_045408_add_trading_mode_to_companies_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2023_08_21_154153_create_zatca_invoices_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2023_08_20_154757_create_trader_products_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2023_08_23_152743_migrate_old_transactions_of_order_creation_fee_to_include_vat',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2023_08_23_175200_migrate_media_of_trader_orders_zatca_invoices_to_transactions',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2023_08_27_120906_migrate_old_transactions_to_have_is_vat_included_in_meta',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2023_08_27_014327_create_tiered_pricings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2023_08_28_102056_migrate_old_order_cost_in_comapnies_table_to_assocciate_with_tiered_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2023_08_29_061748_drop_order_cost_in_companies_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2023_09_23_011905_create_wallet_notifications_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2023_09_20_124945_add_require_initiate_trade_request_to_lender_settings',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2023_09_20_134626_add_require_initiate_trade_request_to_companies_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2023_09_21_064112_set_inital_value_for_require_initiate_trade_request_to_exist_companies',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2023_09_25_124805_add_notify_borrowers_about_order_updates_to_companies_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2023_09_25_132626_add_notify_borrowers_about_order_updates_to_lender_settings',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2023_10_04_052840_add_notified_at_to_wallet_notifications_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2023_09_30_230033_add_missing_indexes',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2023_10_11_022614_migrate_refunded_at_to_trader_orders_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2023_10_16_125905_add_is_base_column_to_trader_orders_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2023_10_12_031222_migrate_money_to_new_currency',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2023_11_02_053755_add_force_unique_reference_number_to_companies_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2023_11_11_191006_add_can_continue_progress_column_to_trader_orders_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2023_12_04_161858_fix_refunded_orders_transactions',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2024_03_31_113243_add_contract_number_to_companies_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2024_04_01_181713_add_contract_number_to_financing_orders_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2024_04_14_102404_add_trader_order_id_to_transactions_meta',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2024_04_21_131611_create_commodity_suppliers_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2024_04_24_083253_create_commodity_types_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2024_05_12_074509_create_measurements_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2024_05_12_080102_create_currencies_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2024_05_16_085632_fix_unique_name_of_companies_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2024_05_16_101807_create_supplier_locations_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2024_05_19_113257_drop_supplier_users_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2024_05_14_094321_create_commodity_items_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2024_05_19_125107_create_commodity_item_types_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2024_05_20_070107_remove_legal_name_and_unique_name_from_commodity_suppliers_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2024_05_20_080200_add_company_id_to_commodity_suppliers_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2024_05_20_082410_rename_commodity_suppliers_table_to_company_supplier_details',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2024_05_27_084203_add_market_type_to_companies_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2024_05_27_143637_create_local_murabaha_settings',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2024_05_28_103624_drop_commodity_item_types_and_add_commodity_type_id_at_commodity_items_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2024_05_28_113049_company_commodity_types',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2024_05_29_120232_create_inventories_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2024_06_03_074243_change_data_types_to_commodity_items_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2024_06_04_081613_edit_in_preferred_commodity_type_at_compaines_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2024_06_04_113653_create_orders_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2024_06_04_114604_create_order_has_inventories_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2024_06_05_135633_create_inventory_units_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2024_06_10_005625_update_status_column_in_inventories_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2024_06_11_095313_rename_inventories_to_local_market_inventories',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2024_06_25_130706_add_column_default_contract_sign_time_limit_to_local_murabaha_settings',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2024_06_24_120624_create_local_market_order_has_units_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2024_06_24_122115_create_local_market_unit_rotations_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2024_07_01_120700_add_default_contract_sign_time_limit_to_trader_orders_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2024_07_04_095533_create_trader_order_cancel_details_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2024_07_07_123000_add_cancel_type_at_trader_order_cancel_details_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2024_07_08_060605_fix_cancel_detail_data',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2024_07_14_084751_add_deleted_at_to_local_market_inventory_units_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2024_07_24_095421_change_status_column_in_local_market_inventory_units_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2024_07_31_084751_add_deleted_at_to_local_market_inventories_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2024_08_05_072234_add_deleted_at_to_commodity_items_table',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2024_08_06_013151_add_deleted_at_to_supplier_locations_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2024_08_12_110152_remove_unique_name_index_from_supplier_locations',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2024_08_08_151753_remove_unique_name_index_from_commodity_items',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2024_08_26_071804_add_column_charged_trader_order_count_to_financing_orders_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2024_08_26_221436_update_charged_trader_orders_count_in_financing_orders',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2024_09_04_080131_add_old_charged_trader_order_count_column_to_financing_orders_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2024_09_01_105002_fix_stored_procedure_of_inventory_units',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2024_09_02_124009_create_soft_delete_local_market_inventory_units_procedure',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2024_09_08_143442_add_complete_murabaha_step_to_companies_table',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2024_07_02_120339_rename_order_has_inventories_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2024_07_09_070449_create_local_market_unit_ownership_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2024_07_13_012010_add_column_trader_order_id_to_local_market_orders_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2024_07_13_163238_create_local_market_unit_ownership_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2024_08_21_134832_create_local_market_order_histories_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2024_08_21_134851_create_local_market_order_has_cancel_reasons_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2024_08_25_072821_fix_data_of_local_market_order',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2024_08_25_072822_update_data_at_local_market_orders',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2024_08_25_102550_add_data_column_to_local_market_orders_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2024_08_25_160543_remove_relation_company_from_local_market_order_from_database',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2024_08_26_123608_add_uuid_at_local_market_orders_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2024_08_27_123710_drop_unused_columns_from_local_market_order_has_inventories_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2024_08_28_172751_enhance_local_market_order_has_units_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2024_08_29_152034_change_data_type_of_hold_for_at_local_market_inventory_units_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2024_09_03_121556_add_local_market_order_id_to_local_market_order_has_units_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2024_09_03_122903_add_local_market_order_id_to_local_market_unit_ownership_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2024_09_11_093824_make_order_no_is_null_at_local_market_orders_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2024_09_11_194819_add_contract_signed_type_at_trader_orders_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2024_09_11_123028_fix_default_trade_order_roatation_count_settings_table',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2024_09_16_073026_change_data_type_of_data_and_comment_columns_at_local_market_orders_table',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2024_04_29_120232_create_inventories_table',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2024_10_15_130706_add_column_order_responsible_admins_to_general_settings',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2024_10_17_093923_add_column_can_manage_orders_to_users_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2024_10_20_121052_add_column_assignable_id_to_financing_orders_table',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2024_10_10_070457_drop_cancel_step_from_local_market_order_has_cancel_reasons__table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2024_11_03_132423_add_column_expire_at_to_trader_orders_table',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2024_11_03_142239_convert_default_contract_sign_time_limit_to_minutes_trader_orders_table',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2024_11_12_134145_add_previous_owner_and_last_completed_to_local_market_inventory_units',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2024_11_20_174546_add_column_default_customer_delivery_confirmation_time_limit_to_lynk_murabha',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2024_04_29_120232_create_local_market_inventories_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2024_06_04_113653_create_local_market_orders_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2024_06_04_114604_create_local_market_order_has_inventories_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2024_06_05_135633_create_local_market_inventory_units_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2024_11_21_092400_add_action_to_ownership_migration',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2024_11_25_155341_add_previous_owners_column_to_inventory_units_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2024_11_25_172633_add_previous_owners_index_to_units_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2024_11_24_045613_create_trader_order_time_limits_table',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2024_11_27_094129_create_local_market_live_table',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2024_12_08_100329_create_table_local_market_inventories_eligibility',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2024_12_12_074642_fix_data_at_units_table',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2024_12_12_092326_add_indexes_to_inventory_units_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2024_12_05_101223_add_status_to_trader_order_time_limits_table',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2024_11_18_145514_update_status_column_in_local_market_orders',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2024_12_23_145301_add_virtual_columns_to_inventory_units_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2024_12_23_145322_add_indexes_columns_to_inventory_units_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2024_12_24_140100_add_last_action_to_local_market_inventory_units_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2024_12_24_150000_create_after_unit_ownership_change_trigger',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2025_01_02_083722_update_default_value_for_hold_for_column',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2024_12_29_100005_create_company_lender_details_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2025_01_02_142046_add_column_action_to_trader_order_time_limits_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2025_01_02_142047_move_default_contract_sign_time_limit_to_trader_order_time_limits',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2025_01_15_131754_add_force_preferred_commodity_type_to_company_lender_details_table',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2025_01_26_134020_remove_commodity_type_id_from_local_market_inventories_table',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2025_01_28_132801_remove_indexes_from_inventory_units_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2025_01_29_090733_remove_indexes_from_inventory_units_table',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2025_02_04_080441_add_notifications_email_to_company_lender_details_table',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2025_02_04_091400_create_index_inventory_units_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2025_02_04_140850_add_company_cr_to_company_lender_details_table',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2025_02_05_144141_add_contract_number_to_company_lender_details_table',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2025_02_11_151037_add_preferred_market_type_to_company_lender_details_table',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2025_02_15_202126_add_does_order_require_approval_to_company_lender_details_table',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2025_02_06_121733_partation_units_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2025_02_18_080813_add_notify_admins_about_new_orders_to_company_lender_details_table',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2025_02_17_125417_add_last_purchasing_order_id_to_local_market_inventory_units_table',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2025_02_17_142428_add_commodities_settlement_status_to_local_market_orders_table',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2025_02_17_151623_add_settlement_status_to_local_market_order_has_units_table',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2025_03_10_112514_add_indexes_at_local_market_inventory_units_table',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2025_03_13_160001_cleanup_temporary_columns_in_financing_orders_table',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2025_02_18_124312_add_unique_constraint_to_company_id_in_company_lender_details',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2025_02_18_124313_add_notify_borrowers_about_order_updates_to_company_lender_details_table',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2025_03_27_101747_add_trading_mode_to_company_lender_details_table',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2025_03_27_082651_move_require_initiate_trade_request_to_company_lender_details_table',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2025_03_27_122020_move_public_status_comment_to_compant_lender_detail',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2025_03_27_125214_move_auto_complete_murabaha_order_to_compant_lender_detail',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2025_03_26_093459_add_force_unique_reference_number_to_company_lender_details_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2025_03_27_124251_move_internal_status_comment_to_compant_lender_detail',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2025_04_03_100404_add_webhook_secret_key_to_company_lender_details_table',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2025_04_07_082642_create_company_lender_clients_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2025_04_08_091517_change_data_type_of_national_id_at_company_lender_clients_table',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2025_01_28_101812_remove_expire_at_and_defualt_contract_signed_columns_from_trader_orders_table',129);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2025_04_09_083222_add_aauto_complete_sell_at_company_lender_clients_table',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2025_04_09_083436_create_client_auto_sell_periods_table',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2025_04_13_134209_add_auto_sell_period_id_to_trader_orders_table',131);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2025_04_22_153512_edit_constraint_at_company_lender_clients_table',132);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2025_05_12_131025_create_trader_order_proceed_cases_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2025_05_15_132057_fix_corrupted_local_market_orders_inventory_units',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2025_05_25_104842_add_soft_delete_to_trader_products_table',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2025_05_25_105901_seed_testing_product_codes',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2025_05_25_105902_add_column_bursam_default_preferred_commodity_type_to_international_murabaha',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2025_05_26_120100_create_company_preferred_trader_products_table',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2025_06_04_000000_add_deadlock_prevention_indexes',137);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2025_06_02_104604_add_provider_and_soft_deletes_at_commodity_types_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2025_06_11_070826_migrate_date_from_trader_products_to_commodity_types',139);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2025_06_11_084723_migrate_internationl_preffered_product_to_use_new_table',140);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2025_06_11_124404_add_allow_preferred_commodity_in_order_to_company_lender_details_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2025_06_12_070230_add_commodity_type_id_to_financing_orders_table',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2025_06_12_115926_add_commodity_type_id_to_trader_orders_table',143);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2025_06_17_111913_add_force_commodity_type_at_trader_orders_table',144);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2025_06_17_115005_add_force_commodity_type_at_local_market_orders_table',144);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2025_06_17_000001_optimize_wallets_and_transactions_indexes',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2025_06_24_142113_modify_trader_orders_commodity_type_id_constraint_to_allow_negative_one',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2025_06_23_162331_add_token_expire_in_company_lender_details_table',147);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2025_06_24_060050_add_exipre_at_in_personal_access_tokens_table',147);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2025_06_29_112115_remove_notify_borrowers_about_order_updates_in_company_lender_details',148);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2025_06_29_115149_remove_notify_borrowers_about_order_updates',148);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2025_07_02_150112_reset_grantify_roles_and_permissions',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2025_06_25_000001_optimize_deadlock_prevention_indexes',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2025_07_07_150847_add_token_version_to_company_lender_details_table',151);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2025_07_09_000000_drop_force_preferred_commodity_type_from_company_lender_details_table',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2025_07_09_000001_drop_force_commodity_type_columns',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2025_07_09_151139_refresh_grantify_permissions',153);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2025_07_13_150922_remove_lender_columns_from_companies_table',154);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2025_07_15_110349_optimize_trader_histories_performance',155);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2025_07_15_111210_populate_trader_orders_cached_history_actions',156);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2025_07_21_103639_create_company_lender_order_allowed_commodity_types_table',157);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2025_07_22_131921_add_commodity_type_id_to_local_market_inventories_table',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2025_07_23_130801_create_commodity_type_statistics_view',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2025_07_30_100055_add_index_to_local_market_inventory_units_table',159);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2025_08_03_213833_changes_varchar_lenth_at_local_market_inventory_units_table',160);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2025_08_12_000001_drop_fks_from_local_market_order_has_inventories',161);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2025_08_13_000001_drop_hold_for_foreign_key_constraint_from_local_market_inventory_units_table',161);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2025_01_27_000000_update_commodity_type_statistics_view_for_active_items_only',162);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2025_08_06_184916_add_touched_by_to_local_market_eligible_quantities_table',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2025_08_07_120022_create_hold_order_unit_procedure_table',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2025_08_07_163022_add_is_editable_to_local_market_inventories_table',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2025_01_16_000000_drop_company_preferred_trader_products_table',164);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2025_09_15_100330_change_data_column_to_json_in_orders_tables',165);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2025_08_25_120341_add_allowed_financial_order_types_to_company_lender_details_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2025_09_18_100330_drop_data_backup_column_in_orders_tables',167);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2025_08_18_142155_drop_foreign_keys_from_transactions_table',168);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2025_09_18_113746_add_lender_and_lender_identifier_at_financing_orders',168);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2025_09_24_072721_add_lender_name_and_borrower_name_at_local_market_orders_table',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2025_09_29_101430_add_creator_id',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2025_10_02_103533_update_amount_precision_in_local_market_orders',172);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2018_08_08_100000_create_telescope_entries_table',173);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2025_10_09_093737_add_cost_and_cost_without_vat_at_financing_orders_table',174);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2025_10_09_093737_add_cost_and_cost_without_vat_at_financing_orders_table',174);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2025_10_13_171658_add_balance_to_transactions_table',175);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2025_10_21_000001_add_paid_at_to_edaat_invoices_table',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2025_10_22_141218_add_vat_amount_to_tiered_pricing_table',178);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2025_10_28_000000_create_trader_order_settlements_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2025_10_28_000001_add_index_to_last_purchasing_order_id_in_local_market_inventory_units_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2025_10_30_075638_modify_settlement_columns_in_local_market_orders_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2025_10_30_143825_remove_settlement_status_from_local_market_order_has_units_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2025_11_06_141104_remove_cutomer_details_from_financing_orders_table',180);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2025_11_11_000001_create_financing_order_histories_table',181);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2025_11_16_143148_drop_can_continue_progress_at_trader_orders_tables',182);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2025_11_16_141544_drop_local_market_live_tables',183);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2025_11_20_133036_add_auto_approve_at_users_table',184);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2025_11_24_093255_add_index_for_previous_owner_grouping_to_local_market_inventory_units_table',185);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2025_11_26_074033_drop_index_for_previous_owner_grouping_to_local_market_inventory_units_table',186);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2025_12_03_120512_change_lender_identifier_to_unsigned_bigint_in_local_market_orders_table',187);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2025_12_08_000001_create_notification_types_table',188);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2025_12_08_000002_create_user_notification_settings_table',188);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2025_12_10_100000_add_unique_index_to_settings_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2025_12_11_102432_drop_wallet_notifications_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2025_12_11_180000_delete_delivery_confirmation_settings_for_company_users',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2025_12_14_115428_drop_activity_log_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2025_12_15_135040_add_an_index_on_reference_in_trader_orders_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2025_12_24_070216_remove_notify_admins_about_new_orders_from_company_lender_details',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2025_12_26_110000_refactor_user_notification_settings_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2025_12_31_105458_remove_order_created_user_notifications_settings',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2026_01_01_094146_drop_is_base_column_from_trader_orders_table',189);

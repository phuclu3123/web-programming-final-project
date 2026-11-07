-- CouponHub fresh-install baseline (2026-11-07).
-- 21 base tables = 20 consolidated business tables + external_offers.
-- Coupon and article comments share comments (exactly one target per comment).
-- 6 views, 21 stored procedures.  No fixture/user data included.
-- Intended only for a NEW EMPTY database; DO NOT import over an existing database.
-- BACK UP the database before any schema migration. Use database/migrations for upgrades.
-- Compatible target: MariaDB 10.4.32 / utf8mb4_vietnamese_ci; select target DB explicitly.

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Source: portable 20-table MariaDB schema supplied for migration review
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `old_moderation` varchar(20) DEFAULT NULL,
  `new_moderation` varchar(20) DEFAULT NULL,
  `old_publication` varchar(20) DEFAULT NULL,
  `new_publication` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `legacy_history_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_legacy_history` (`legacy_history_id`),
  KEY `fk_activity_coupon` (`coupon_id`),
  KEY `idx_activity_user_date` (`user_id`,`created_at`),
  KEY `idx_activity_review_notification` (`coupon_id`,`action`,`new_moderation`,`created_at`),
  CONSTRAINT `fk_activity_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ck_activity_status_transition` CHECK (`action` = 'COUPON_STATUS_CHANGE' and `coupon_id` is not null and `old_moderation` is not null and `new_moderation` is not null and `old_publication` is not null and `new_publication` is not null or `action` <> 'COUPON_STATUS_CHANGE' and `old_moderation` is null and `new_moderation` is null and `old_publication` is null and `new_publication` is null)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` bigint(20) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `summary` text DEFAULT NULL,
  `body` longtext NOT NULL,
  `status` enum('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_article_author` (`author_id`),
  KEY `idx_article_publication` (`status`,`published_at`),
  CONSTRAINT `fk_article_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaign_coupons`
--

DROP TABLE IF EXISTS `campaign_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_coupons` (
  `campaign_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `added_by` bigint(20) unsigned NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`campaign_id`,`coupon_id`),
  KEY `fk_cc_actor` (`added_by`),
  KEY `idx_cc_coupon` (`coupon_id`),
  CONSTRAINT `fk_cc_actor` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_cc_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`),
  CONSTRAINT `fk_cc_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_merchant_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `banner_path` varchar(500) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` enum('DRAFT','PUBLISHED','DISABLED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_campaign_owner` (`owner_merchant_id`),
  KEY `fk_campaign_creator` (`created_by`),
  KEY `idx_campaign_status_dates` (`status`,`starts_at`,`ends_at`),
  CONSTRAINT `fk_campaign_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_campaign_owner` FOREIGN KEY (`owner_merchant_id`) REFERENCES `merchants` (`id`),
  CONSTRAINT `ck_campaign_dates` CHECK (`ends_at` > `starts_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`),
  CONSTRAINT `ck_category_active` CHECK (`is_active` in (0,1))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) unsigned DEFAULT NULL,
  `article_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `status` enum('VISIBLE','HIDDEN','DELETED') NOT NULL DEFAULT 'VISIBLE',
  `request_id` varbinary(32) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comment_article_request` (`user_id`,`request_id`),
  KEY `fk_comment_user` (`user_id`),
  KEY `idx_comment_coupon_date` (`coupon_id`,`created_at`),
  KEY `idx_comment_article_page` (`article_id`,`status`,`id`),
  KEY `idx_comment_article_moderation` (`status`,`id`),
  CONSTRAINT `fk_comment_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_comment_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ck_comment_target` CHECK ((`coupon_id` IS NOT NULL AND `article_id` IS NULL AND `request_id` IS NULL) OR (`coupon_id` IS NULL AND `article_id` IS NOT NULL AND `request_id` IS NOT NULL))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `coupon_events`
--

DROP TABLE IF EXISTS `coupon_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupon_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` enum('VIEW','COPY','SHARE','FAVORITE_ADD','VOTE') NOT NULL,
  `request_key` varbinary(64) NOT NULL,
  `occurred_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_request` (`request_key`),
  KEY `fk_event_user` (`user_id`),
  KEY `idx_events_coupon_time` (`coupon_id`,`occurred_at`,`event_type`),
  KEY `idx_events_time` (`occurred_at`),
  CONSTRAINT `fk_event_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_event_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned NOT NULL,
  `source` enum('COMMUNITY','OFFICIAL') NOT NULL DEFAULT 'COMMUNITY',
  `title` varchar(200) NOT NULL,
  `code` varchar(100) DEFAULT NULL,
  `code_normalized` varchar(100) GENERATED ALWAYS AS (nullif(ucase(trim(`code`)),'')) STORED,
  `description` text DEFAULT NULL,
  `terms` text NOT NULL,
  `discount_type` enum('PERCENTAGE','FIXED_AMOUNT','FREE_SHIPPING','BUY_X_GET_Y','GIFT','CASHBACK') NOT NULL,
  `discount_value` decimal(15,2) DEFAULT NULL,
  `cashback_unit` enum('PERCENTAGE','FIXED_AMOUNT') DEFAULT NULL,
  `min_order_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(15,2) DEFAULT NULL,
  `usage_channel` enum('ONLINE','OFFLINE','BOTH') NOT NULL,
  `promotion_url` varchar(2048) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `moderation_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `publication_status` enum('ENABLED','DISABLED','EXPIRED','ARCHIVED') NOT NULL DEFAULT 'ENABLED',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_coupon_reviewer` (`reviewed_by`),
  KEY `idx_coupon_duplicate` (`merchant_id`,`code_normalized`,`starts_at`,`ends_at`),
  KEY `idx_coupon_public` (`moderation_status`,`publication_status`,`ends_at`),
  KEY `idx_coupon_category_date` (`category_id`,`ends_at`),
  KEY `idx_coupon_author_date` (`submitted_by`,`created_at`),
  KEY `idx_coupon_created` (`created_at`),
  CONSTRAINT `fk_coupon_author` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_coupon_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_coupon_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  CONSTRAINT `fk_coupon_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ck_coupon_dates` CHECK (`ends_at` > `starts_at`),
  CONSTRAINT `ck_coupon_money` CHECK (`min_order_amount` >= 0 and (`discount_value` is null or `discount_value` >= 0) and (`max_discount_amount` is null or `max_discount_amount` >= 0)),
  CONSTRAINT `ck_coupon_percent` CHECK (`discount_type` <> 'PERCENTAGE' or `discount_value` is not null and `discount_value` > 0 and `discount_value` <= 100)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites` (
  `user_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_eligible_for_rewards` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`,`coupon_id`),
  KEY `idx_favorite_coupon` (`coupon_id`,`created_at`),
  CONSTRAINT `fk_favorite_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_favorite_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ck_favorite_qualified` CHECK (`is_eligible_for_rewards` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `levels`
--

DROP TABLE IF EXISTS `levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `levels` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `min_points` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `min_points` (`min_points`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `merchant_memberships`
--

DROP TABLE IF EXISTS `merchant_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchant_memberships` (
  `user_id` bigint(20) unsigned NOT NULL,
  `merchant_id` bigint(20) unsigned NOT NULL,
  `status` enum('ACTIVE','REVOKED') NOT NULL DEFAULT 'ACTIVE',
  `granted_by` bigint(20) unsigned DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revoked_by` bigint(20) unsigned DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revoke_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`,`merchant_id`),
  KEY `idx_mm_merchant_status` (`merchant_id`,`status`),
  KEY `idx_mm_user_status` (`user_id`,`status`),
  KEY `fk_mm_grant_actor` (`granted_by`),
  KEY `fk_mm_revoke_actor` (`revoked_by`),
  CONSTRAINT `fk_mm_grant_actor` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_mm_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  CONSTRAINT `fk_mm_revoke_actor` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_mm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `merchant_requests`
--

DROP TABLE IF EXISTS `merchant_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchant_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `merchant_id` bigint(20) unsigned NOT NULL,
  `request_type` enum('CREATE','JOIN','CLAIM','LEGACY') NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `message` text DEFAULT NULL,
  `evidence_reference` varchar(1000) DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mr_user_status` (`user_id`,`status`),
  KEY `idx_mr_merchant_status` (`merchant_id`,`status`),
  KEY `idx_mr_queue` (`status`,`created_at`),
  KEY `fk_mr_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_mr_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`),
  CONSTRAINT `fk_mr_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_mr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `merchants`
--

DROP TABLE IF EXISTS `merchants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `website_url` varchar(2048) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `status` enum('UNCLAIMED','PENDING','APPROVED','REJECTED','LOCKED') NOT NULL DEFAULT 'UNCLAIMED',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_merchants_owner` (`owner_user_id`),
  KEY `fk_merchants_reviewer` (`reviewed_by`),
  KEY `idx_merchants_status` (`status`),
  CONSTRAINT `fk_merchants_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_merchants_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oauth_accounts`
--

DROP TABLE IF EXISTS `oauth_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `provider` enum('github','google','facebook') NOT NULL,
  `provider_user_id` varbinary(255) NOT NULL,
  `provider_email` varchar(191) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_oauth_identity` (`provider`,`provider_user_id`),
  UNIQUE KEY `uq_oauth_user_provider` (`user_id`,`provider`),
  CONSTRAINT `fk_oauth_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` varbinary(64) NOT NULL,
  `request_ip_hash` varbinary(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_hash` (`token_hash`),
  KEY `idx_password_reset_user_date` (`user_id`,`created_at`),
  KEY `idx_password_reset_ip_date` (`request_ip_hash`,`created_at`),
  KEY `idx_password_reset_expiry` (`expires_at`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `point_rules`
--

DROP TABLE IF EXISTS `point_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_rules` (
  `code` varchar(40) NOT NULL,
  `description` varchar(200) NOT NULL,
  `points` int(11) NOT NULL,
  `threshold_count` int(10) unsigned NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`code`),
  CONSTRAINT `ck_point_rule_active` CHECK (`is_active` in (0,1)),
  CONSTRAINT `ck_point_rule_threshold` CHECK (`threshold_count` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `point_transactions`
--

DROP TABLE IF EXISTS `point_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `rule_code` varchar(40) NOT NULL,
  `points` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupon_award` (`coupon_id`,`rule_code`),
  KEY `fk_points_rule` (`rule_code`),
  KEY `idx_points_user_date` (`user_id`,`created_at`),
  CONSTRAINT `fk_points_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_points_rule` FOREIGN KEY (`rule_code`) REFERENCES `point_rules` (`code`),
  CONSTRAINT `fk_points_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `reported_by` bigint(20) unsigned NOT NULL,
  `reason` enum('EXPIRED','NOT_WORKING','WRONG_TERMS','DUPLICATE','SPAM','FAKE','INAPPROPRIATE','OTHER') NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('OPEN','RESOLVED','DISMISSED') NOT NULL DEFAULT 'OPEN',
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_report_coupon` (`coupon_id`),
  KEY `fk_report_resolver` (`resolved_by`),
  KEY `idx_report_queue` (`status`,`created_at`),
  KEY `idx_report_author_coupon` (`reported_by`,`coupon_id`),
  CONSTRAINT `fk_report_author` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_report_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_report_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `avatar_path` varchar(500) DEFAULT NULL,
  `role` enum('USER','MERCHANT','ADMIN') NOT NULL DEFAULT 'USER',
  `status` enum('ACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `auth_version` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


-- Provider staging table required by latest main. Article discussions share comments.

-- Source: database/migrations/20261105_external_offers.sql
-- Additive only. Back up an existing database first; never re-import schema/demo.
CREATE TABLE IF NOT EXISTS external_offers (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 provider VARCHAR(32) NOT NULL,
 external_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 provider_id VARCHAR(191) NOT NULL,
 code VARCHAR(100) DEFAULT NULL,
 payload LONGTEXT NOT NULL,
 fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 imported_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 state ENUM('PENDING','IMPORTED','DISMISSED') NOT NULL DEFAULT 'PENDING',
 coupon_id BIGINT UNSIGNED DEFAULT NULL,
 fetched_at DATETIME NOT NULL,
 imported_at DATETIME DEFAULT NULL,
 UNIQUE KEY uq_external_offer(provider,external_key),
 KEY ix_external_state(state,fetched_at),
 CONSTRAINT fk_external_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

--
-- Temporary table structure for view `v_contributor_stats`
--

DROP TABLE IF EXISTS `v_contributor_stats`;
/*!50001 DROP VIEW IF EXISTS `v_contributor_stats`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_contributor_stats` AS SELECT
 1 AS `user_id`,
  1 AS `submitted_count`,
  1 AS `approved_count`,
  1 AS `rejected_count` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_coupon_favorite_stats`
--

DROP TABLE IF EXISTS `v_coupon_favorite_stats`;
/*!50001 DROP VIEW IF EXISTS `v_coupon_favorite_stats`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_coupon_favorite_stats` AS SELECT
 1 AS `coupon_id`,
  1 AS `total_favorites`,
  1 AS `qualified_favorites` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_coupon_status`
--

DROP TABLE IF EXISTS `v_coupon_status`;
/*!50001 DROP VIEW IF EXISTS `v_coupon_status`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_coupon_status` AS SELECT
 1 AS `is_official`,
  1 AS `id`,
  1 AS `merchant_id`,
  1 AS `category_id`,
  1 AS `submitted_by`,
  1 AS `source`,
  1 AS `title`,
  1 AS `code`,
  1 AS `code_normalized`,
  1 AS `description`,
  1 AS `terms`,
  1 AS `discount_type`,
  1 AS `discount_value`,
  1 AS `cashback_unit`,
  1 AS `min_order_amount`,
  1 AS `max_discount_amount`,
  1 AS `usage_channel`,
  1 AS `promotion_url`,
  1 AS `starts_at`,
  1 AS `ends_at`,
  1 AS `moderation_status`,
  1 AS `publication_status`,
  1 AS `reviewed_by`,
  1 AS `reviewed_at`,
  1 AS `review_note`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `deleted_at`,
  1 AS `effective_status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_coupon_vote_stats`
--

DROP TABLE IF EXISTS `v_coupon_vote_stats`;
/*!50001 DROP VIEW IF EXISTS `v_coupon_vote_stats`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_coupon_vote_stats` AS SELECT
 1 AS `coupon_id`,
  1 AS `working_count`,
  1 AS `not_working_count`,
  1 AS `condition_changed_count`,
  1 AS `total_votes`,
  1 AS `trust_score`,
  1 AS `last_verification_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_user_level`
--

DROP TABLE IF EXISTS `v_user_level`;
/*!50001 DROP VIEW IF EXISTS `v_user_level`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_user_level` AS SELECT
 1 AS `user_id`,
  1 AS `earned_points`,
  1 AS `level_id`,
  1 AS `level_name` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_user_points`
--

DROP TABLE IF EXISTS `v_user_points`;
/*!50001 DROP VIEW IF EXISTS `v_user_points`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_user_points` AS SELECT
 1 AS `user_id`,
  1 AS `earned_points` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votes` (
  `user_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `vote_type` enum('WORKING','NOT_WORKING','CONDITION_CHANGED') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_eligible_for_trust` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`,`coupon_id`),
  KEY `idx_vote_coupon_type` (`coupon_id`,`vote_type`),
  CONSTRAINT `fk_vote_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ck_vote_qualified` CHECK (`is_eligible_for_trust` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'couponhub_migration_test'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_admin_dashboard_v2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_admin_dashboard_v2`(IN `p_actor_id` BIGINT UNSIGNED, IN `p_status` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_selected_id` BIGINT UNSIGNED)
    READS SQL DATA
BEGIN

    DECLARE v_id BIGINT UNSIGNED;

    SET time_zone = '+00:00';

    IF NOT EXISTS (SELECT 1 FROM users WHERE id=p_actor_id AND role='ADMIN'

        AND status='ACTIVE' AND deleted_at IS NULL) THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ Admin đang hoạt động được xem hàng chờ.';

    END IF;

    IF p_status IS NULL OR p_status NOT IN ('PENDING','APPROVED','REJECTED') THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Trạng thái hàng chờ không hợp lệ.';

    END IF;

    SELECT (SELECT COUNT(*) FROM merchants WHERE status='APPROVED' AND deleted_at IS NULL) AS merchants,

      COUNT(*) AS total,COALESCE(SUM(moderation_status='PENDING'),0) AS pending,

      COALESCE(SUM(moderation_status='APPROVED'),0) AS approved,

      COALESCE(SUM(moderation_status='REJECTED'),0) AS rejected

    FROM coupons WHERE deleted_at IS NULL;

    SELECT c.*,m.name AS merchant_name,cat.name AS category_name,u.full_name AS author_name

    FROM coupons c JOIN merchants m ON m.id=c.merchant_id

    JOIN categories cat ON cat.id=c.category_id JOIN users u ON u.id=c.submitted_by

    WHERE c.deleted_at IS NULL AND c.moderation_status=p_status

    ORDER BY c.created_at DESC,c.id DESC LIMIT 100;

    IF COALESCE(p_selected_id,0)>0 THEN

        SET v_id=(SELECT id FROM coupons WHERE id=p_selected_id

          AND deleted_at IS NULL AND moderation_status=p_status LIMIT 1);

    ELSE

        SET v_id=(SELECT id FROM coupons WHERE deleted_at IS NULL AND moderation_status=p_status

          ORDER BY created_at DESC,id DESC LIMIT 1);

    END IF;

    SELECT c.*,m.name AS merchant_name,m.status AS merchant_status,cat.name AS category_name,

      u.full_name AS author_name,

      (c.moderation_status='PENDING' AND c.publication_status='ENABLED'

        AND m.status='APPROVED' AND m.deleted_at IS NULL AND cat.is_active=1

        AND c.ends_at>=NOW() AND c.starts_at<c.ends_at

        AND c.discount_type IN ('PERCENTAGE','FIXED_AMOUNT') AND c.discount_value>0

        AND c.discount_value<=1000000000 AND (c.discount_type<>'PERCENTAGE' OR c.discount_value<=100)) AS can_approve

    FROM coupons c JOIN merchants m ON m.id=c.merchant_id

    JOIN categories cat ON cat.id=c.category_id JOIN users u ON u.id=c.submitted_by

    WHERE c.id=v_id AND c.deleted_at IS NULL AND c.moderation_status=p_status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_approve_coupon` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_approve_coupon`(IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED)
    MODIFIES SQL DATA
BEGIN

    SET time_zone = '+00:00';

    CALL sp_moderate_coupon(p_actor,p_coupon,'APPROVED','ENABLED',NULL);

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_auth_user` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_auth_user`(IN `p_user_id` BIGINT UNSIGNED)
    READS SQL DATA
BEGIN

    SELECT u.id, u.email, u.full_name, u.status, u.deleted_at, u.auth_version,

      CASE WHEN u.role='ADMIN' THEN 'ADMIN'

        WHEN EXISTS(SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id

          WHERE mm.user_id=u.id AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL)

        THEN 'MERCHANT' ELSE 'USER' END AS role

    FROM users u WHERE u.id = p_user_id;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_create_coupon` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_create_coupon`(
    IN p_actor BIGINT UNSIGNED, IN p_merchant BIGINT UNSIGNED, IN p_category BIGINT UNSIGNED,
    IN p_title VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN p_code VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN p_description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN p_terms TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci,
    IN p_discount_type VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN p_discount_value DECIMAL(15,2),
    IN p_min_order DECIMAL(15,2), IN p_max_discount DECIMAL(15,2),
    IN p_channel VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN p_start DATETIME, IN p_end DATETIME
)
    MODIFIES SQL DATA
BEGIN
    SET time_zone = '+00:00';
    CALL sp_submit_coupon(p_actor,p_merchant,p_category,1,p_title,p_code,p_description,p_terms,
      p_discount_type,p_discount_value,NULL,p_min_order,p_max_discount,p_channel,NULL,p_start,p_end);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_create_user` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_create_user`(IN `p_full_name` VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_username` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_email` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_phone` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_password_hash` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
BEGIN

    -- Báo lỗi có mã cố định để controller hiện đúng thông báo tiếng Việt.

    -- Không tái sử dụng username/email của tài khoản đã xóa mềm.

    IF EXISTS (SELECT 1 FROM users WHERE username = p_username) THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'USERNAME_EXISTS';

    END IF;

    IF EXISTS (SELECT 1 FROM users WHERE email = p_email) THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'EMAIL_EXISTS';

    END IF;



    -- Quyền mặc định nằm ở server/database, không lấy từ request người dùng.

    INSERT INTO users (full_name, username, email, phone, password_hash, role, status)

    VALUES (p_full_name, p_username, p_email, p_phone, p_password_hash, 'USER', 'ACTIVE');



    -- Model đọc $sets[0][0]['user_id'] để biết tài khoản đã tạo thành công.

    SELECT LAST_INSERT_ID() AS user_id;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_find_user_by_login` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_find_user_by_login`(IN `p_account` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    READS SQL DATA
BEGIN

    SELECT u.id, u.email, u.username, u.phone, u.password_hash, u.full_name,

      u.status, u.deleted_at, u.auth_version,

      CASE WHEN u.role='ADMIN' THEN 'ADMIN'

        WHEN EXISTS(SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id

          WHERE mm.user_id=u.id AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL)

        THEN 'MERCHANT' ELSE 'USER' END AS role

    FROM users u WHERE u.email=TRIM(p_account) OR u.username=TRIM(p_account) LIMIT 1;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_owned_merchant` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_owned_merchant`(IN `p_merchant_id` BIGINT UNSIGNED, IN `p_user_id` BIGINT UNSIGNED)
    READS SQL DATA
BEGIN

    SELECT m.id, m.owner_user_id, mm.user_id AS manager_user_id

    FROM merchants m

    JOIN merchant_memberships mm ON mm.merchant_id=m.id AND mm.user_id=p_user_id AND mm.status='ACTIVE'

    JOIN users u ON u.id = mm.user_id

    WHERE m.id = p_merchant_id

      AND m.status = 'APPROVED' AND m.deleted_at IS NULL

      AND u.role <> 'ADMIN' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_public_catalog_v3` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_public_catalog_v3`(
    IN p_query VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci,
    IN p_category_id BIGINT UNSIGNED,
    IN p_merchant_id BIGINT UNSIGNED,
    IN p_discount_type VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci,
    IN p_usage_channel VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci,
    IN p_ending_soon TINYINT UNSIGNED,
    IN p_sort VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci
)
    READS SQL DATA
BEGIN
    DECLARE v_query VARCHAR(310) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
    SET time_zone = '+00:00';

    IF p_sort IS NULL OR p_sort NOT IN ('newest','expiry') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thứ tự sắp xếp không hợp lệ.';
    END IF;
    IF COALESCE(p_discount_type, '') <> ''
      AND p_discount_type NOT IN ('PERCENTAGE','FIXED_AMOUNT','FREE_SHIPPING','BUY_X_GET_Y','GIFT','CASHBACK') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Loại ưu đãi không hợp lệ.';
    END IF;
    IF COALESCE(p_usage_channel, '') <> ''
      AND p_usage_channel NOT IN ('ONLINE','OFFLINE','BOTH') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kênh sử dụng không hợp lệ.';
    END IF;
    IF COALESCE(p_ending_soon, 0) NOT IN (0, 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bộ lọc sắp hết hạn không hợp lệ.';
    END IF;

    SET v_query = CONCAT('%',
        REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p_query,'')), '!', '!!'), '%', '!%'), '_', '!_'),
        '%');

    SELECT id, name, slug
    FROM categories
    WHERE is_active = 1
    ORDER BY sort_order, id;

    SELECT id, name, slug
    FROM merchants
    WHERE status = 'APPROVED' AND deleted_at IS NULL
    ORDER BY name
    LIMIT 24;

    SELECT c.*, m.name AS merchant_name, cat.name AS category_name
    FROM v_coupon_status c
    JOIN merchants m ON m.id = c.merchant_id
    JOIN categories cat ON cat.id = c.category_id
    WHERE c.deleted_at IS NULL
      AND m.deleted_at IS NULL
      AND cat.is_active = 1
      AND c.moderation_status = 'APPROVED'
      AND c.publication_status = 'ENABLED'
      AND c.effective_status = 'ACTIVE'
      AND c.starts_at <= NOW()
      AND c.ends_at >= NOW()
      AND (COALESCE(p_category_id, 0) = 0 OR c.category_id = p_category_id)
      AND (COALESCE(p_merchant_id, 0) = 0 OR c.merchant_id = p_merchant_id)
      AND (COALESCE(p_discount_type, '') = '' OR c.discount_type = p_discount_type)
      AND (COALESCE(p_usage_channel, '') = ''
        OR c.usage_channel = p_usage_channel
        OR c.usage_channel = 'BOTH')
      AND (COALESCE(p_ending_soon, 0) = 0 OR c.ends_at <= DATE_ADD(NOW(), INTERVAL 7 DAY))
      AND (c.title LIKE v_query ESCAPE '!'
        OR c.code LIKE v_query ESCAPE '!'
        OR m.name LIKE v_query ESCAPE '!'
        OR c.description LIKE v_query ESCAPE '!')
    ORDER BY
        CASE WHEN p_sort = 'expiry' THEN c.ends_at END ASC,
        CASE WHEN p_sort = 'newest' THEN c.created_at END DESC,
        c.id DESC
    LIMIT 60;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_reject_coupon` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_reject_coupon`(IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED, IN `p_reason` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    MODIFIES SQL DATA
BEGIN

    SET time_zone = '+00:00';

    IF p_reason IS NULL OR CHAR_LENGTH(TRIM(p_reason)) NOT BETWEEN 10 AND 1000 THEN

      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Lý do từ chối cần từ 10 đến 1.000 ký tự.';

    END IF;

    CALL sp_moderate_coupon(p_actor,p_coupon,'REJECTED','DISABLED',TRIM(p_reason));

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ch_shop_dashboard_v2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `ch_shop_dashboard_v2`(IN `p_actor_id` BIGINT UNSIGNED)
    READS SQL DATA
BEGIN

    IF NOT EXISTS (SELECT 1 FROM users WHERE id=p_actor_id AND role<>'ADMIN'

        AND status='ACTIVE' AND deleted_at IS NULL) THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ Merchant đang hoạt động được quản lý mã.';

    END IF;

    SELECT id,name,status FROM merchants WHERE EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.merchant_id=merchants.id AND mm.user_id=p_actor_id AND mm.status='ACTIVE')

        AND status='APPROVED' AND deleted_at IS NULL ORDER BY id;

    SELECT id,name FROM categories WHERE is_active=1 ORDER BY sort_order,id;

    SELECT c.*,m.name AS merchant_name,cat.name AS category_name

    FROM coupons c JOIN merchants m ON m.id=c.merchant_id

    JOIN categories cat ON cat.id=c.category_id

    WHERE EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.merchant_id=m.id AND mm.user_id=p_actor_id AND mm.status='ACTIVE') AND m.status='APPROVED' AND m.deleted_at IS NULL AND c.deleted_at IS NULL

    ORDER BY c.created_at DESC,c.id DESC LIMIT 100;

    SELECT COUNT(*) AS total,

        COALESCE(SUM(c.moderation_status='APPROVED'),0) AS approved,

        COALESCE(SUM(c.moderation_status='PENDING'),0) AS pending,

        COALESCE(SUM(c.moderation_status='REJECTED'),0) AS rejected

    FROM coupons c JOIN merchants m ON m.id=c.merchant_id

    WHERE EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.merchant_id=m.id AND mm.user_id=p_actor_id AND mm.status='ACTIVE') AND m.status='APPROVED' AND m.deleted_at IS NULL AND c.deleted_at IS NULL;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_add_category` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_add_category`(IN `p_actor` BIGINT UNSIGNED, IN `p_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_slug` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    SQL SECURITY INVOKER
BEGIN

  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admin required';

  END IF;

  IF p_name IS NULL OR CHAR_LENGTH(TRIM(p_name))=0 OR p_slug IS NULL OR CHAR_LENGTH(TRIM(p_slug))=0 THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Name and slug required';

  END IF;

  INSERT INTO categories(name,slug,description) VALUES(TRIM(p_name),TRIM(p_slug),p_description);

  SELECT LAST_INSERT_ID() AS category_id;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_attach_campaign_coupon` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_attach_campaign_coupon`(IN `p_actor` BIGINT UNSIGNED, IN `p_campaign` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED)
    SQL SECURITY INVOKER
BEGIN

  DECLARE v_campaign BIGINT UNSIGNED DEFAULT NULL;

  DECLARE v_owner_merchant BIGINT UNSIGNED;

  DECLARE v_merchant BIGINT UNSIGNED DEFAULT NULL;

  DECLARE v_role VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci DEFAULT NULL;

  DECLARE v_cs DATETIME; DECLARE v_ce DATETIME; DECLARE v_ps DATETIME; DECLARE v_pe DATETIME;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;

  START TRANSACTION;

  SELECT role INTO v_role FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL;

  IF v_role IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required'; END IF;

  SELECT id,owner_merchant_id,starts_at,ends_at INTO v_campaign,v_owner_merchant,v_cs,v_ce FROM campaigns WHERE id=p_campaign FOR UPDATE;

  SELECT merchant_id,starts_at,ends_at INTO v_merchant,v_ps,v_pe FROM coupons WHERE id=p_coupon AND deleted_at IS NULL FOR UPDATE;

  IF v_campaign IS NULL OR v_merchant IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign or coupon not found'; END IF;

  IF v_owner_merchant IS NOT NULL AND v_owner_merchant<>v_merchant THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon does not belong to campaign merchant';

  END IF;

  IF v_role<>'ADMIN' AND (v_role<>'MERCHANT' OR v_owner_merchant IS NULL OR NOT EXISTS(

    SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE mm.merchant_id=v_owner_merchant AND mm.user_id=p_actor AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL)) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign owner or admin required';

  END IF;

  IF v_cs>=v_pe OR v_ps>=v_ce THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign and coupon periods must overlap'; END IF;

  IF NOT EXISTS(SELECT 1 FROM campaign_coupons WHERE campaign_id=p_campaign AND coupon_id=p_coupon) THEN

    INSERT INTO campaign_coupons(campaign_id,coupon_id,added_by) VALUES(p_campaign,p_coupon,p_actor);

  END IF;

  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_award_coupon_points` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_award_coupon_points`(IN `p_coupon_id` BIGINT UNSIGNED)
    SQL SECURITY INVOKER
BEGIN

  DECLARE v_author BIGINT UNSIGNED;

  DECLARE v_source VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;

  DECLARE v_moderation VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;

  DECLARE v_working INT DEFAULT 0;

  DECLARE v_favorites INT DEFAULT 0;

  SELECT c.submitted_by,c.source,c.moderation_status

    INTO v_author,v_source,v_moderation

    FROM coupons c WHERE c.id=p_coupon_id;

  IF v_source='COMMUNITY' AND v_moderation='APPROVED' THEN

    SELECT working_count INTO v_working FROM v_coupon_vote_stats WHERE coupon_id=p_coupon_id;

    SELECT qualified_favorites INTO v_favorites FROM v_coupon_favorite_stats WHERE coupon_id=p_coupon_id;

    INSERT INTO point_transactions(user_id,coupon_id,rule_code,points,note)

      SELECT v_author,p_coupon_id,r.code,r.points,r.description FROM point_rules r

      WHERE r.is_active=1 AND (

        r.code='COUPON_APPROVED'

        OR (r.code='WORKING_10' AND v_working>=r.threshold_count)

        OR (r.code='FAVORITES_100' AND v_favorites>=r.threshold_count))

      AND NOT EXISTS (SELECT 1 FROM point_transactions p WHERE p.coupon_id=p_coupon_id AND p.rule_code=r.code);

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_merchant_stats` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_merchant_stats`(IN `p_actor` BIGINT UNSIGNED, IN `p_merchant` BIGINT UNSIGNED)
    SQL SECURITY INVOKER
BEGIN

  IF NOT EXISTS(SELECT 1 FROM users u WHERE u.id=p_actor AND u.status='ACTIVE' AND u.deleted_at IS NULL AND

     (u.role='ADMIN' OR (u.role='MERCHANT' AND EXISTS(SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE m.id=p_merchant AND mm.user_id=u.id AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL)))) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Merchant owner or admin required';

  END IF;

  SELECT COUNT(*) AS total_coupons,COALESCE(SUM(effective_status COLLATE utf8mb4_vietnamese_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci),0) AS active_coupons,

    COALESCE(SUM(effective_status COLLATE utf8mb4_vietnamese_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci AND ends_at<=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 3 DAY)),0) AS expiring_soon

    FROM v_coupon_status WHERE merchant_id=p_merchant AND deleted_at IS NULL;

  SELECT COALESCE(SUM(e.event_type='VIEW'),0) AS total_views,COALESCE(SUM(e.event_type='COPY'),0) AS total_copies,

    COALESCE(SUM(e.event_type='SHARE'),0) AS total_shares

    FROM coupon_events e JOIN coupons c ON c.id=e.coupon_id WHERE c.merchant_id=p_merchant;

  SELECT COALESCE(SUM(s.total_favorites),0) AS current_favorites,COALESCE(SUM(s.qualified_favorites),0) AS qualified_favorites FROM v_coupon_favorite_stats s JOIN coupons c ON c.id=s.coupon_id WHERE c.merchant_id=p_merchant;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_moderate_coupon` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_moderate_coupon`(IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED, IN `p_moderation` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_publication` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    SQL SECURITY INVOKER
BEGIN
  DECLARE v_old_m VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci DEFAULT NULL;
  DECLARE v_old_p VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
  DECLARE v_merchant BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_lock BIGINT UNSIGNED;
  DECLARE v_code VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
  DECLARE v_start DATETIME;
  DECLARE v_end DATETIME;
  DECLARE v_deleted DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admin required';
  END IF;
  IF p_moderation IS NULL OR p_moderation NOT IN ('PENDING','APPROVED','REJECTED') OR
     p_publication IS NULL OR p_publication NOT IN ('ENABLED','DISABLED','EXPIRED','ARCHIVED') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid state';
  END IF;
  SELECT merchant_id INTO v_merchant FROM coupons WHERE id=p_coupon;
  IF v_merchant IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;
  -- Same merchant -> coupon lock order as submission/edit to prevent duplicate reactivation.
  SELECT id INTO v_lock FROM merchants WHERE id=v_merchant FOR UPDATE;
  SELECT moderation_status,publication_status,code_normalized,starts_at,ends_at,deleted_at
    INTO v_old_m,v_old_p,v_code,v_start,v_end,v_deleted FROM coupons WHERE id=p_coupon FOR UPDATE;
  IF v_deleted IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Deleted coupon cannot be moderated'; END IF;
  IF v_old_p='ARCHIVED' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Archived or revoked coupon cannot be moderated'; END IF;
  IF p_moderation IN ('PENDING','APPROVED') AND p_publication='ENABLED' AND v_code IS NOT NULL AND EXISTS(
    SELECT 1 FROM coupons WHERE id<>p_coupon AND merchant_id=v_merchant AND code_normalized=v_code
      AND deleted_at IS NULL AND moderation_status IN ('PENDING','APPROVED') AND publication_status='ENABLED'
      AND starts_at<v_end AND ends_at>v_start) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate code with overlapping validity';
  END IF;
  UPDATE coupons SET moderation_status=p_moderation,publication_status=p_publication,
    reviewed_by=p_actor,reviewed_at=UTC_TIMESTAMP(),review_note=p_note WHERE id=p_coupon;
  IF v_old_m<>p_moderation OR v_old_p<>p_publication THEN
    INSERT INTO activity_logs(user_id,coupon_id,action,old_moderation,new_moderation,old_publication,new_publication,note)
    VALUES(p_actor,p_coupon,'COUPON_STATUS_CHANGE',v_old_m,p_moderation,v_old_p,p_publication,p_note);
    INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(p_actor,p_coupon,'COUPON_MODERATE',CONCAT(p_moderation,'/',p_publication));
  END IF;
  IF p_moderation='APPROVED' AND p_publication='ENABLED' THEN CALL sp_award_coupon_points(p_coupon); END IF;
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_record_coupon_event` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_record_coupon_event`(IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED, IN `p_event` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_key` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    SQL SECURITY INVOKER
BEGIN

  IF p_event IS NULL OR p_event NOT IN ('VIEW','COPY','SHARE') OR p_key IS NULL OR CHAR_LENGTH(TRIM(p_key))=0 THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid event or request key';

  END IF;

  IF p_actor IS NOT NULL AND NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid account';

  END IF;

  IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status COLLATE utf8mb4_vietnamese_ci IN (_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci,_utf8mb4'SCHEDULED' COLLATE utf8mb4_vietnamese_ci)) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon unavailable';

  END IF;

  IF p_event='COPY' AND NOT EXISTS(SELECT 1 FROM coupons WHERE id=p_coupon AND NULLIF(TRIM(code),'') IS NOT NULL) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon has no copyable code';

  END IF;

  -- Duplicate key returns an error: service may treat only an exact matching replay as success.

  INSERT INTO coupon_events(coupon_id,user_id,event_type,request_key) VALUES(p_coupon,p_actor,p_event,p_key);

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_resolve_report` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_resolve_report`(IN `p_actor` BIGINT UNSIGNED, IN `p_report` BIGINT UNSIGNED, IN `p_status` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    SQL SECURITY INVOKER
BEGIN

  DECLARE v_coupon BIGINT UNSIGNED DEFAULT NULL;

  DECLARE v_status VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;

  START TRANSACTION;

  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admin required';

  END IF;

  IF p_status IS NULL OR p_status NOT IN ('RESOLVED','DISMISSED') OR p_note IS NULL OR CHAR_LENGTH(TRIM(p_note))=0 THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Resolution status and note required';

  END IF;

  SELECT coupon_id,status INTO v_coupon,v_status FROM reports WHERE id=p_report FOR UPDATE;

  IF v_coupon IS NULL OR v_status<>'OPEN' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Open report required'; END IF;

  UPDATE reports SET status=p_status,resolved_by=p_actor,resolved_at=UTC_TIMESTAMP(),resolution_note=p_note WHERE id=p_report;

  INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(p_actor,v_coupon,'REPORT_RESOLVE',CONCAT('Report #',p_report,': ',p_status));

  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_search_coupons` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_search_coupons`(IN `p_keyword` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_category` BIGINT UNSIGNED, IN `p_merchant` BIGINT UNSIGNED, IN `p_type` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_channel` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_ending_soon` TINYINT, IN `p_sort` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_page` INT, IN `p_page_size` INT)
    SQL SECURITY INVOKER
BEGIN

  DECLARE v_offset INT DEFAULT 0;

  DECLARE v_size INT DEFAULT 20;

  SET v_size=LEAST(100,GREATEST(1,COALESCE(p_page_size,20)));

  SET v_offset=(LEAST(100000,GREATEST(1,COALESCE(p_page,1)))-1)*v_size;

  SELECT c.id,c.title,c.code,c.merchant_id,m.name AS merchant_name,c.category_id,c.discount_type,c.discount_value,

    c.min_order_amount,c.max_discount_amount,c.usage_channel,c.starts_at,c.ends_at,c.is_official,

    s.trust_score,s.total_votes,fs.total_favorites AS favorite_count,fs.qualified_favorites

  FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id JOIN v_coupon_favorite_stats fs ON fs.coupon_id=c.id

  WHERE c.effective_status COLLATE utf8mb4_vietnamese_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci

    AND (p_keyword IS NULL OR p_keyword='' OR c.title LIKE CONCAT('%',p_keyword,'%') OR c.code LIKE CONCAT('%',p_keyword,'%')

      OR c.description LIKE CONCAT('%',p_keyword,'%') OR m.name LIKE CONCAT('%',p_keyword,'%'))

    AND (p_category IS NULL OR c.category_id=p_category) AND (p_merchant IS NULL OR c.merchant_id=p_merchant)

    AND (p_type IS NULL OR c.discount_type=p_type) AND (p_channel IS NULL OR c.usage_channel=p_channel OR c.usage_channel='BOTH')

    AND (COALESCE(p_ending_soon,0)=0 OR c.ends_at<=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 3 DAY))

  ORDER BY CASE WHEN p_sort='POPULAR' THEN fs.qualified_favorites END DESC,

    CASE WHEN p_sort='ENDING_SOON' THEN c.ends_at END ASC,c.created_at DESC,c.id DESC

  LIMIT v_size OFFSET v_offset;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_set_favorite` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_set_favorite`(IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED, IN `p_saved` TINYINT)
    SQL SECURITY INVOKER
BEGIN

  DECLARE v_lock BIGINT UNSIGNED DEFAULT NULL;

  DECLARE v_valid TINYINT DEFAULT 1;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;

  START TRANSACTION;

  SELECT id INTO v_lock FROM coupons WHERE id=p_coupon FOR UPDATE;

  IF v_lock IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;

  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';

  END IF;

  IF p_saved IS NULL OR p_saved NOT IN (0,1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid saved flag'; END IF;

  IF p_saved=1 THEN

    IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status COLLATE utf8mb4_vietnamese_ci IN (_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci,_utf8mb4'SCHEDULED' COLLATE utf8mb4_vietnamese_ci)) THEN

      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon cannot be saved';

    END IF;

    IF NOT EXISTS(SELECT 1 FROM favorites WHERE user_id=p_actor AND coupon_id=p_coupon) THEN

      IF EXISTS(SELECT 1 FROM coupons c WHERE c.id=p_coupon AND (c.submitted_by=p_actor OR EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.user_id=p_actor AND mm.merchant_id=c.merchant_id AND mm.status='ACTIVE'))) THEN

        SET v_valid=0;

      END IF;

      INSERT INTO favorites(user_id,coupon_id,is_eligible_for_rewards) VALUES(p_actor,p_coupon,v_valid);

      INSERT INTO activity_logs(user_id,coupon_id,action) VALUES(p_actor,p_coupon,'FAVORITE_ADD');

      INSERT INTO coupon_events(coupon_id,user_id,event_type,request_key) VALUES(p_coupon,p_actor,'FAVORITE_ADD',UUID());

    END IF;

    CALL sp_award_coupon_points(p_coupon);

  ELSE

    DELETE FROM favorites WHERE user_id=p_actor AND coupon_id=p_coupon;

    IF ROW_COUNT()>0 THEN INSERT INTO activity_logs(user_id,coupon_id,action) VALUES(p_actor,p_coupon,'FAVORITE_REMOVE'); END IF;

  END IF;

  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_set_vote` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_set_vote`(IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED, IN `p_vote` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)
    SQL SECURITY INVOKER
BEGIN

  DECLARE v_author BIGINT UNSIGNED DEFAULT NULL;

  DECLARE v_changed INT DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;

  START TRANSACTION;

  SELECT submitted_by INTO v_author FROM coupons WHERE id=p_coupon FOR UPDATE;

  IF v_author IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;

  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';

  END IF;

  IF p_actor=v_author OR EXISTS(SELECT 1 FROM merchant_memberships mm JOIN coupons c ON c.merchant_id=mm.merchant_id WHERE c.id=p_coupon AND mm.user_id=p_actor AND mm.status='ACTIVE') THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Author and merchant owner cannot vote on own coupon';

  END IF;

  IF p_vote IS NULL OR p_vote NOT IN ('WORKING','NOT_WORKING','CONDITION_CHANGED') THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid vote';

  END IF;

  IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status COLLATE utf8mb4_vietnamese_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon must be active';

  END IF;

  IF NOT EXISTS(SELECT 1 FROM votes WHERE user_id=p_actor AND coupon_id=p_coupon AND vote_type=p_vote AND is_eligible_for_trust=1) THEN

    INSERT INTO votes(user_id,coupon_id,vote_type,is_eligible_for_trust) VALUES(p_actor,p_coupon,p_vote,1)

      ON DUPLICATE KEY UPDATE vote_type=p_vote,is_eligible_for_trust=1,updated_at=UTC_TIMESTAMP();

    SET v_changed=1;

    INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(p_actor,p_coupon,'VOTE_SET',p_vote);

    INSERT INTO coupon_events(coupon_id,user_id,event_type,request_key) VALUES(p_coupon,p_actor,'VOTE',UUID());

  END IF;

  CALL sp_award_coupon_points(p_coupon);

  COMMIT;

  SELECT v_changed AS changed;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_submit_coupon` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_vietnamese_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `sp_submit_coupon`(IN `p_actor` BIGINT UNSIGNED, IN `p_merchant` BIGINT UNSIGNED, IN `p_category` BIGINT UNSIGNED, IN `p_official` TINYINT, IN `p_title` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_code` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_terms` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_type` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_value` DECIMAL(15,2), IN `p_cashback_unit` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_min_order` DECIMAL(15,2), IN `p_max_discount` DECIMAL(15,2), IN `p_channel` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_url` VARCHAR(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_start` DATETIME, IN `p_end` DATETIME)
    SQL SECURITY INVOKER
BEGIN
  DECLARE v_owner BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_merchant_state VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci DEFAULT NULL;
  DECLARE v_deleted DATETIME DEFAULT NULL;
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_duplicate INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';
  END IF;
  SELECT owner_user_id,status,deleted_at INTO v_owner,v_merchant_state,v_deleted FROM merchants WHERE id=p_merchant FOR UPDATE;
  IF v_merchant_state IS NULL OR v_merchant_state IN ('LOCKED','REJECTED') OR v_deleted IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Merchant unavailable';
  END IF;
  IF p_official IS NULL OR p_official NOT IN (0,1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid official flag'; END IF;
  IF p_official=1 AND (v_merchant_state<>'APPROVED' OR
      NOT EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.user_id=p_actor AND mm.merchant_id=p_merchant AND mm.status='ACTIVE')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only approved merchant owner can submit official coupon';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM categories WHERE id=p_category AND is_active=1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active category required';
  END IF;
  IF p_title IS NULL OR CHAR_LENGTH(TRIM(p_title))=0 OR p_terms IS NULL OR CHAR_LENGTH(TRIM(p_terms))=0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Title and terms required';
  END IF;
  IF p_start IS NULL OR p_end IS NULL OR p_end<=p_start OR p_end<=UTC_TIMESTAMP() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid validity period';
  END IF;
  IF NULLIF(TRIM(p_code),'') IS NOT NULL THEN
    SELECT COUNT(*) INTO v_duplicate FROM coupons WHERE merchant_id=p_merchant
      AND code_normalized=UPPER(TRIM(p_code)) AND deleted_at IS NULL
      AND moderation_status IN ('PENDING','APPROVED') AND publication_status='ENABLED'
      AND starts_at<p_end AND ends_at>p_start;
    IF v_duplicate>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate code with overlapping validity'; END IF;
  END IF;
  INSERT INTO coupons(merchant_id,category_id,submitted_by,source,title,code,description,terms,
    discount_type,discount_value,cashback_unit,min_order_amount,max_discount_amount,usage_channel,
    promotion_url,starts_at,ends_at)
  VALUES(p_merchant,p_category,p_actor,IF(p_official=1,'OFFICIAL','COMMUNITY'),TRIM(p_title),NULLIF(TRIM(p_code),''),p_description,p_terms,
    p_type,p_value,p_cashback_unit,p_min_order,p_max_discount,p_channel,NULLIF(TRIM(p_url),''),p_start,p_end);
  SET v_id=LAST_INSERT_ID();
  INSERT INTO activity_logs(user_id,coupon_id,action) VALUES(p_actor,v_id,'COUPON_SUBMIT');
  COMMIT;
  SELECT v_id AS coupon_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `v_contributor_stats`
--

/*!50001 DROP VIEW IF EXISTS `v_contributor_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_vietnamese_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY INVOKER */
/*!50001 VIEW `v_contributor_stats` AS select `u`.`id` AS `user_id`,count(`c`.`id`) AS `submitted_count`,coalesce(sum(`c`.`moderation_status` = 'APPROVED'),0) AS `approved_count`,coalesce(sum(`c`.`moderation_status` = 'REJECTED'),0) AS `rejected_count` from (`users` `u` left join `coupons` `c` on(`c`.`submitted_by` = `u`.`id` and `c`.`source` = 'COMMUNITY')) group by `u`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_coupon_favorite_stats`
--

/*!50001 DROP VIEW IF EXISTS `v_coupon_favorite_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_vietnamese_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY INVOKER */
/*!50001 VIEW `v_coupon_favorite_stats` AS select `c`.`id` AS `coupon_id`,count(`f`.`user_id`) AS `total_favorites`,coalesce(sum(case when `f`.`user_id` is not null and `f`.`user_id` <> `c`.`submitted_by` and `f`.`is_eligible_for_rewards` = 1 and `mm`.`user_id` is null then 1 else 0 end),0) AS `qualified_favorites` from ((`coupons` `c` left join `favorites` `f` on(`f`.`coupon_id` = `c`.`id`)) left join `merchant_memberships` `mm` on(`mm`.`user_id` = `f`.`user_id` and `mm`.`merchant_id` = `c`.`merchant_id` and `mm`.`status` = 'ACTIVE')) group by `c`.`id`,`c`.`submitted_by`,`c`.`merchant_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_coupon_status`
--

/*!50001 DROP VIEW IF EXISTS `v_coupon_status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_vietnamese_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY INVOKER */
/*!50001 VIEW `v_coupon_status` AS select `c`.`source` = 'OFFICIAL' AS `is_official`,`c`.`id` AS `id`,`c`.`merchant_id` AS `merchant_id`,`c`.`category_id` AS `category_id`,`c`.`submitted_by` AS `submitted_by`,`c`.`source` AS `source`,`c`.`title` AS `title`,`c`.`code` AS `code`,`c`.`code_normalized` AS `code_normalized`,`c`.`description` AS `description`,`c`.`terms` AS `terms`,`c`.`discount_type` AS `discount_type`,`c`.`discount_value` AS `discount_value`,`c`.`cashback_unit` AS `cashback_unit`,`c`.`min_order_amount` AS `min_order_amount`,`c`.`max_discount_amount` AS `max_discount_amount`,`c`.`usage_channel` AS `usage_channel`,`c`.`promotion_url` AS `promotion_url`,`c`.`starts_at` AS `starts_at`,`c`.`ends_at` AS `ends_at`,`c`.`moderation_status` AS `moderation_status`,`c`.`publication_status` AS `publication_status`,`c`.`reviewed_by` AS `reviewed_by`,`c`.`reviewed_at` AS `reviewed_at`,`c`.`review_note` AS `review_note`,`c`.`created_at` AS `created_at`,`c`.`updated_at` AS `updated_at`,`c`.`deleted_at` AS `deleted_at`,case when `c`.`deleted_at` is not null or `c`.`publication_status` = 'ARCHIVED' then 'ARCHIVED' when `c`.`publication_status` = 'DISABLED' or `c`.`source` = 'OFFICIAL' and `m`.`status` <> 'APPROVED' or `m`.`status` = 'LOCKED' or `m`.`deleted_at` is not null or `cat`.`is_active` = 0 then 'DISABLED' when `c`.`moderation_status` = 'REJECTED' then 'REJECTED' when `c`.`moderation_status` = 'PENDING' then 'PENDING' when `c`.`publication_status` = 'EXPIRED' or utc_timestamp() >= `c`.`ends_at` then 'EXPIRED' when utc_timestamp() < `c`.`starts_at` then 'SCHEDULED' else 'ACTIVE' end AS `effective_status` from ((`coupons` `c` join `merchants` `m` on(`m`.`id` = `c`.`merchant_id`)) join `categories` `cat` on(`cat`.`id` = `c`.`category_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_coupon_vote_stats`
--

/*!50001 DROP VIEW IF EXISTS `v_coupon_vote_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_vietnamese_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY INVOKER */
/*!50001 VIEW `v_coupon_vote_stats` AS select `c`.`id` AS `coupon_id`,coalesce(sum(`v`.`vote_type` = 'WORKING'),0) AS `working_count`,coalesce(sum(`v`.`vote_type` = 'NOT_WORKING'),0) AS `not_working_count`,coalesce(sum(`v`.`vote_type` = 'CONDITION_CHANGED'),0) AS `condition_changed_count`,count(`v`.`user_id`) AS `total_votes`,round(100.0 * sum(`v`.`vote_type` = 'WORKING') / nullif(sum(`v`.`vote_type` in ('WORKING','NOT_WORKING')),0),2) AS `trust_score`,max(`v`.`updated_at`) AS `last_verification_at` from (`coupons` `c` left join `votes` `v` on(`v`.`coupon_id` = `c`.`id` and `v`.`is_eligible_for_trust` = 1 and `v`.`user_id` <> `c`.`submitted_by` and !exists(select 1 from `merchant_memberships` `mm` where `mm`.`user_id` = `v`.`user_id` and `mm`.`merchant_id` = `c`.`merchant_id` and `mm`.`status` = 'ACTIVE' limit 1))) group by `c`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_user_level`
--

/*!50001 DROP VIEW IF EXISTS `v_user_level`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_vietnamese_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY INVOKER */
/*!50001 VIEW `v_user_level` AS select `p`.`user_id` AS `user_id`,`p`.`earned_points` AS `earned_points`,`l`.`id` AS `level_id`,`l`.`name` AS `level_name` from (`v_user_points` `p` join `levels` `l` on(`l`.`min_points` = (select max(`l2`.`min_points`) from `levels` `l2` where `l2`.`min_points` <= `p`.`earned_points`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_user_points`
--

/*!50001 DROP VIEW IF EXISTS `v_user_points`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_vietnamese_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY INVOKER */
/*!50001 VIEW `v_user_points` AS select `u`.`id` AS `user_id`,coalesce(sum(`p`.`points`),0) AS `earned_points` from (`users` `u` left join `point_transactions` `p` on(`p`.`user_id` = `u`.`id`)) group by `u`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-07  0:47:16

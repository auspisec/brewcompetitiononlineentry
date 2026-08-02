-- Privacy consent text versions (one row per language per version per consent_type)
-- consent_type: 'privacy' (collection/use) or 'publication' (award publication)
CREATE TABLE IF NOT EXISTS `{PREFIX}consent_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(10) NOT NULL DEFAULT 'en-US',
  `version` int(11) NOT NULL DEFAULT 1,
  `consent_text` text NOT NULL,
  `consent_type` varchar(20) NOT NULL DEFAULT 'privacy',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_version_type` (`language`, `version`, `consent_type`),
  KEY `active` (`language`, `is_active`, `consent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent acceptance log (audit trail)
-- consent_type: 'privacy' or 'publication'
CREATE TABLE IF NOT EXISTS `{PREFIX}consent_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `consent_text_id` int(11) NOT NULL,
  `consent_given` tinyint(1) NOT NULL DEFAULT 1,
  `consent_type` varchar(20) NOT NULL DEFAULT 'privacy',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `consent_text_id` (`consent_text_id`),
  KEY `consent_type` (`consent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

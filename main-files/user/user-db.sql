-- --------------------------------------------------------
-- Additional Tables for User Authentication System
-- --------------------------------------------------------

-- Table structure for table `registered_users` (Frontend Users)
DROP TABLE IF EXISTS `registered_users`;
CREATE TABLE `registered_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `login_method` enum('email','google','both') NOT NULL DEFAULT 'email',
  `status` enum('active','suspended','blocked') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `user_sessions`
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_token` (`session_token`),
  FOREIGN KEY (`user_id`) REFERENCES `registered_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `password_resets`
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  FOREIGN KEY (`user_id`) REFERENCES `registered_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update exchanges table to link with registered users
ALTER TABLE `exchanges` 
ADD COLUMN `registered_user_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `registered_user_id` (`registered_user_id`),
ADD CONSTRAINT `fk_exchange_user` FOREIGN KEY (`registered_user_id`) REFERENCES `registered_users` (`id`) ON DELETE SET NULL;

-- Insert PHPMailer settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('smtp_enabled', 'yes'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls'),
('smtp_from_email', ''),
('smtp_from_name', 'Exchange Bridge'),
('google_oauth_enabled', 'no'),
('google_oauth_client_id', ''),
('google_oauth_client_secret', ''),
('google_oauth_redirect_uri', ''),
('email_verification_required', 'yes'),
('user_registration_enabled', 'yes');

-- Create indexes for better performance
CREATE INDEX `idx_user_email_verified` ON `registered_users` (`email_verified`);
CREATE INDEX `idx_user_status` ON `registered_users` (`status`);
CREATE INDEX `idx_session_expires` ON `user_sessions` (`expires_at`);
CREATE INDEX `idx_password_reset_expires` ON `password_resets` (`expires_at`);
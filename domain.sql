-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 27 May 2025, 21:08:39
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `domain_takip`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$jiRJTP5ebZlrd8SW4owCX.UDx/CvL.PwAY7LNic3Z9nUCYYSMn.eu', 'admin@example.com', '2025-05-26 21:58:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `domains`
--

CREATE TABLE `domains` (
  `id` int(11) NOT NULL,
  `domain_name` varchar(255) NOT NULL,
  `registrar` varchar(255) DEFAULT NULL,
  `creation_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `updated_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `name_servers` text DEFAULT NULL,
  `last_checked` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `domains`
--

INSERT INTO `domains` (`id`, `domain_name`, `registrar`, `creation_date`, `expiry_date`, `updated_date`, `status`, `name_servers`, `last_checked`, `notes`, `created_at`, `updated_at`) VALUES
(20, 'r10.net', 'GoDaddy.com, LLC', '2005-02-26', '2035-02-26', '2025-03-26', 'clientDeleteProhibited https://icann.org/epp#clien', 'carl.ns.cloudflare.com, lucy.ns.cloudflare.com', '2025-05-27 18:38:03', 'Batuhan', '2025-05-27 18:38:02', '2025-05-27 18:38:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mail_logs`
--

CREATE TABLE `mail_logs` (
  `id` int(11) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `days_remaining` int(11) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'sent',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mail_settings`
--

CREATE TABLE `mail_settings` (
  `id` int(11) NOT NULL,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `smtp_secure` varchar(10) DEFAULT 'tls',
  `smtp_username` varchar(255) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT 'Domain Takip Sistemi',
  `to_email` varchar(255) NOT NULL,
  `notification_days` varchar(255) DEFAULT '30,15,7,5,4,3,2,1',
  `enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `mail_settings`
--

INSERT INTO `mail_settings` (`id`, `smtp_host`, `smtp_port`, `smtp_secure`, `smtp_username`, `smtp_password`, `from_email`, `from_name`, `to_email`, `notification_days`, `enabled`, `updated_at`) VALUES
(1, 'smtp.gmail.com', 587, 'tls', '', '', '', '', '', '30,15,7,5,4,3,2,1', 1, '2025-05-27 19:08:20');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `simple_debts`
--

CREATE TABLE `simple_debts` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `whois_logs`
--

CREATE TABLE `whois_logs` (
  `id` int(11) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `whois_data` text DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `whois_logs`
--

INSERT INTO `whois_logs` (`id`, `domain_id`, `whois_data`, `checked_at`) VALUES
(40, 20, '{\"registrar\":\"GoDaddy.com, LLC\",\"creation_date\":\"2005-02-26\",\"expiry_date\":\"2035-02-26\",\"updated_date\":\"2025-03-26\",\"status\":\"clientDeleteProhibited https:\\/\\/icann.org\\/epp#clientDeleteProhibited\",\"name_servers\":[\"carl.ns.cloudflare.com\",\"lucy.ns.cloudflare.com\"]}', '2025-05-27 18:38:03');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `domains`
--
ALTER TABLE `domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain_name` (`domain_name`),
  ADD KEY `expiry_date` (`expiry_date`);

--
-- Tablo için indeksler `mail_logs`
--
ALTER TABLE `mail_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain_id` (`domain_id`),
  ADD KEY `sent_at` (`sent_at`);

--
-- Tablo için indeksler `mail_settings`
--
ALTER TABLE `mail_settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `simple_debts`
--
ALTER TABLE `simple_debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_name` (`customer_name`);

--
-- Tablo için indeksler `whois_logs`
--
ALTER TABLE `whois_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain_id` (`domain_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `domains`
--
ALTER TABLE `domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `mail_logs`
--
ALTER TABLE `mail_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `mail_settings`
--
ALTER TABLE `mail_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `simple_debts`
--
ALTER TABLE `simple_debts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `whois_logs`
--
ALTER TABLE `whois_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `mail_logs`
--
ALTER TABLE `mail_logs`
  ADD CONSTRAINT `mail_logs_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `whois_logs`
--
ALTER TABLE `whois_logs`
  ADD CONSTRAINT `whois_logs_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

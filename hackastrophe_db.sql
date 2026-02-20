-- Base de données pour Hackastrophe - Plateforme Bug Bounty / CTF
-- Version: 1.0
-- Date: 2026-02-04

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `hackastrophe_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hackastrophe_db`;

-- --------------------------------------------------------
-- Structure de la table `User`
-- --------------------------------------------------------

CREATE TABLE `User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `role` enum('user','creator','admin') NOT NULL DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `skill_level` enum('junior','intermediate','senior','expert') DEFAULT 'junior',
  `score` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `Challenge`
-- --------------------------------------------------------

CREATE TABLE `Challenge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `category` enum('web','pwn','crypto','forensic','reverse','steganography','osint','misc') NOT NULL,
  `difficulty` enum('noob','mid','ardu','fou','cybersec') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `author_id` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `access_url` varchar(255) DEFAULT NULL,
  `flag_hash` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `solved_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `idx_category` (`category`),
  KEY `idx_difficulty` (`difficulty`),
  KEY `idx_active` (`is_active`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `challenge_author_fk` FOREIGN KEY (`author_id`) REFERENCES `User` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `Cart`
-- --------------------------------------------------------

CREATE TABLE `Cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`challenge_id`),
  KEY `challenge_id` (`challenge_id`),
  CONSTRAINT `cart_user_fk` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_challenge_fk` FOREIGN KEY (`challenge_id`) REFERENCES `Challenge` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `ChallengeInstance`
-- --------------------------------------------------------

CREATE TABLE `ChallengeInstance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_id` int(11) NOT NULL,
  `available_instances` int(11) NOT NULL DEFAULT -1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `challenge_id` (`challenge_id`),
  CONSTRAINT `instance_challenge_fk` FOREIGN KEY (`challenge_id`) REFERENCES `Challenge` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `Invoice`
-- --------------------------------------------------------

CREATE TABLE `Invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `billing_address` varchar(255) NOT NULL,
  `billing_city` varchar(100) NOT NULL,
  `billing_zip` varchar(10) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_date` (`date`),
  CONSTRAINT `invoice_user_fk` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `InvoiceItem`
-- --------------------------------------------------------

CREATE TABLE `InvoiceItem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `challenge_id` int(11) NULL,
  `challenge_title` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `challenge_id` (`challenge_id`),
  CONSTRAINT `invoiceitem_invoice_fk` FOREIGN KEY (`invoice_id`) REFERENCES `Invoice` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoiceitem_challenge_fk` FOREIGN KEY (`challenge_id`) REFERENCES `Challenge` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `Submission`
-- --------------------------------------------------------

CREATE TABLE `Submission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `flag_submitted` varchar(255) NOT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `challenge_id` (`challenge_id`),
  KEY `idx_valid` (`is_valid`),
  KEY `idx_submitted` (`submitted_at`),
  CONSTRAINT `submission_user_fk` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submission_challenge_fk` FOREIGN KEY (`challenge_id`) REFERENCES `Challenge` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `PurchasedChallenge`
-- --------------------------------------------------------

CREATE TABLE `PurchasedChallenge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_solved` tinyint(1) NOT NULL DEFAULT 0,
  `solved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_purchase` (`user_id`,`challenge_id`),
  KEY `challenge_id` (`challenge_id`),
  KEY `idx_solved` (`is_solved`),
  CONSTRAINT `purchased_user_fk` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchased_challenge_fk` FOREIGN KEY (`challenge_id`) REFERENCES `Challenge` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insertion de données de test
-- --------------------------------------------------------

-- Utilisateurs de test
-- Mot de passe pour tous: password123
INSERT INTO `User` (`username`, `email`, `password`, `balance`, `role`, `bio`, `skill_level`, `score`) VALUES
('admin', 'admin@hackastrophe.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5000.00, 'admin', 'Administrateur de la plateforme Hackastrophe', 'expert', 500),
('pentester_pro', 'pro@hackastrophe.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2500.00, 'creator', 'Pentester professionnel spécialisé en web hacking', 'senior', 350),
('john_doe', 'john@hackastrophe.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1000.00, 'user', 'Débutant passionné de cybersécurité', 'junior', 50);

-- Challenges de test
INSERT INTO `Challenge` (`title`, `description`, `category`, `difficulty`, `price`, `author_id`, `flag_hash`, `points`, `access_url`) VALUES
('SQL Injection Basics', 'Apprenez les bases de l''injection SQL en exploitant une simple page de login. Le flag se trouve dans la base de données.', 'web', 'noob', 50.00, 2, '$2y$10$abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRS', 10, 'http://challenge.hackastrophe.fr/sqli-basic'),
('Buffer Overflow Classic', 'Exploitez un buffer overflow classique dans un binaire 32-bit. ASLR désactivé pour simplifier.', 'pwn', 'mid', 150.00, 2, '$2y$10$zyxwvutsrqponmlkjihgfedcba987654321ZYXWVUTSRQPONMLKJIH', 25, 'nc challenge.hackastrophe.fr 4444'),
('RSA Weak Keys', 'Cassez ce chiffrement RSA utilisant des clés faibles. Les mathématiques sont vos amies.', 'crypto', 'ardu', 300.00, 2, '$2y$10$MNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyzABC', 50, 'http://challenge.hackastrophe.fr/rsa-weak'),
('Memory Forensics', 'Analysez un dump mémoire pour retrouver des informations cachées. Volatility sera votre meilleur ami.', 'forensic', 'fou', 500.00, 1, '$2y$10$DEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz12345', 100, 'http://files.hackastrophe.fr/memdump.raw'),
('Reverse Engineer Me', 'Inversez ce binaire obfusqué pour comprendre son fonctionnement et extraire le flag.', 'reverse', 'cybersec', 1000.00, 1, '$2y$10$6789012345ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopq', 200, 'http://files.hackastrophe.fr/crackme'),
('XSS Hunter', 'Trouvez et exploitez une faille XSS dans cette application web moderne. Le flag est dans les cookies de l''admin.', 'web', 'mid', 120.00, 2, '$2y$10$rstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefgh', 25, 'http://challenge.hackastrophe.fr/xss'),
('Steganography 101', 'Une image vaut mille mots, mais combien de bits cachés?', 'steganography', 'noob', 75.00, 2, '$2y$10$ijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567', 10, 'http://files.hackastrophe.fr/hidden.png'),
('OSINT Challenge', 'Utilisez vos compétences en OSINT pour retrouver des informations sur cette mystérieuse personne.', 'osint', 'ardu', 250.00, 1, '$2y$10$89abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXY', 50, 'http://challenge.hackastrophe.fr/osint');

-- Instances de challenges (stock illimité par défaut)
INSERT INTO `ChallengeInstance` (`challenge_id`, `available_instances`) VALUES
(1, -1),
(2, -1),
(3, -1),
(4, -1),
(5, -1),
(6, -1),
(7, -1),
(8, -1);

-- Achats de test pour john_doe
INSERT INTO `PurchasedChallenge` (`user_id`, `challenge_id`, `is_solved`, `solved_at`) VALUES
(3, 1, 1, '2026-02-01 14:30:00'),
(3, 7, 0, NULL);

-- Soumissions de test
INSERT INTO `Submission` (`user_id`, `challenge_id`, `flag_submitted`, `is_valid`, `submitted_at`) VALUES
(3, 1, 'FLAG{sql_injection_master}', 1, '2026-02-01 14:30:00'),
(3, 7, 'FLAG{wrong_flag}', 0, '2026-02-03 10:15:00');

-- Facture de test
INSERT INTO `Invoice` (`user_id`, `amount`, `billing_address`, `billing_city`, `billing_zip`) VALUES
(3, 125.00, '123 Rue de la Cybersécurité', 'Bordeaux', '33000');

INSERT INTO `InvoiceItem` (`invoice_id`, `challenge_id`, `challenge_title`, `price`, `quantity`) VALUES
(1, 1, 'SQL Injection Basics', 50.00, 1),
(1, 7, 'Steganography 101', 75.00, 1);

COMMIT;
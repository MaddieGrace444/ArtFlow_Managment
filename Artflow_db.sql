-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Dec 22, 2025 at 03:57 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Artflow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `artists`
--

CREATE TABLE `artists` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `display_name` varchar(200) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `bio` text,
  `specialization` varchar(255) DEFAULT NULL,
  `years_experience` int DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `social_media_links` json DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `artists`
--

INSERT INTO `artists` (`id`, `user_id`, `first_name`, `last_name`, `display_name`, `email`, `bio`, `specialization`, `years_experience`, `website_url`, `social_media_links`, `is_verified`, `is_featured`, `status`, `hourly_rate`, `created_at`, `updated_at`) VALUES
(8, NULL, 'Elena', 'Rodriguez', 'Elena Rodriguez', 'elena@artstudio.com', 'Contemporary oil painter from Santa Fe, New Mexico.', 'Oil Painting', 12, 'https://elenarodriguezart.com', '{\"instagram\": \"@elenarodriguez.art\"}', 1, 1, 'active', 85.00, '2025-12-07 00:28:51', '2025-12-07 00:28:51'),
(9, NULL, 'Marcus', 'Chen', 'Marcus brown', 'marcus@abstractvisions.com', 'Abstract expressionist painter from Brooklyn, New York.', 'Abstract Painting', 10, 'https://marcusbrownabstract.com', '{\"instagram\": \"@marcusbrown_abstract\"}', 1, 1, 'active', 95.00, '2025-12-07 00:29:36', '2025-12-07 00:29:36'),
(10, NULL, 'Sophia', 'Williams', 'Sophia Williams Photography', 'sophia@fineartphoto.com', 'Fine art photographer from Portland, Oregon.', 'Photography', 8, 'https://sophiawilliamsphoto.com', '{\"instagram\": \"@sophiawilliams.photo\"}', 1, 1, 'active', 75.00, '2025-12-07 00:33:24', '2025-12-07 00:33:24');

-- --------------------------------------------------------

--
-- Table structure for table `artworks`
--

CREATE TABLE `artworks` (
  `id` int NOT NULL,
  `artist_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `category` enum('painting','digital_art','sculpture','photography','mixed_media','print') NOT NULL,
  `medium` varchar(100) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `stock_quantity` int DEFAULT '1',
  `is_available` tinyint(1) DEFAULT '1',
  `tags` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `artworks`
--

INSERT INTO `artworks` (`id`, `artist_id`, `title`, `description`, `category`, `medium`, `dimensions`, `price`, `image_url`, `stock_quantity`, `is_available`, `tags`, `created_at`, `updated_at`) VALUES
(16, 10, 'SolarPunk', 'Industrial landscape with eco friendly elments . The perfect SolorPunk world', 'photography', 'Digital', '', 400.00, 'images/artworks/1765124866_SolarPunk.jpg', 1, 1, NULL, '2025-12-07 16:27:46', '2025-12-07 16:27:46'),
(17, 10, 'SolarPunk', 'Industrial ecofriendly building ', 'photography', 'Digital Art', '', 400.00, 'images/artworks/1765140529_SolarPunk.jpg', 7, 1, NULL, '2025-12-07 20:48:49', '2025-12-07 20:48:49'),
(18, 10, 'SolarPunk', 'Digtial art work', 'photography', 'Digital Art', '', 600.00, 'images/artworks/1765378784_SolarPunk.jpg', 3, 1, NULL, '2025-12-10 14:59:44', '2025-12-10 14:59:44'),
(19, 8, 'Bird Oil Painting', 'oil painting of bird', 'painting', 'oil on canvas', '', 5000.00, 'images/artworks/1765389149_bird_oil_painting.jpeg', 1, 1, NULL, '2025-12-10 17:52:29', '2025-12-10 17:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `commissions`
--

CREATE TABLE `commissions` (
  `id` int NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `artist_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('requested','approved','in_progress','completed','cancelled') DEFAULT 'requested',
  `reference_images` json DEFAULT NULL,
  `special_requirements` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `image_path` varchar(255) NOT NULL,
  `artist_id` int DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('available','sold','reserved','archived') DEFAULT 'available',
  `featured` tinyint(1) DEFAULT '0',
  `dimensions` varchar(100) DEFAULT NULL,
  `medium` varchar(100) DEFAULT NULL,
  `year_created` year DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `views` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `image_path`, `artist_id`, `category`, `price`, `status`, `featured`, `dimensions`, `medium`, `year_created`, `location`, `notes`, `upload_date`, `last_updated`, `views`) VALUES
(1, 'Sunset Over Mountains', 'Beautiful oil painting of mountains at sunset.', 'images/sunset.jpg', NULL, 'Landscape', 4500.00, 'reserved', 1, NULL, 'Oil on Canvas', '2023', NULL, NULL, '2025-12-03 21:29:40', '2025-12-10 14:57:14', 0),
(2, 'Abstract Thoughts', 'Modern abstract piece using mixed media.', 'images/abstract.jpg', NULL, 'Abstract', 3200.00, 'sold', 1, NULL, 'Mixed Media', '2024', NULL, NULL, '2025-12-03 21:29:40', '2025-12-10 17:50:13', 0),
(3, 'Urban Photography', 'Street photography from downtown.', 'images/urban.jpg', NULL, 'Photography', 800.00, 'available', 0, NULL, 'Digital Photography', '2023', NULL, NULL, '2025-12-03 21:29:40', '2025-12-03 21:29:40', 0),
(7, 'Bird Oil Painting', 'oil painting of bird', 'images/artworks/1765389149_bird_oil_painting.jpeg', NULL, 'Painting', 5000.00, 'available', 0, NULL, NULL, NULL, NULL, NULL, '2025-12-10 17:52:29', '2025-12-10 17:52:29', 0);

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `inquiry_type` enum('general','commission','purchase','support') DEFAULT 'general',
  `status` enum('new','in_progress','resolved','closed') DEFAULT 'new',
  `assigned_to` int DEFAULT NULL,
  `response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `customer_name`, `customer_email`, `subject`, `message`, `inquiry_type`, `status`, `assigned_to`, `response`, `created_at`, `updated_at`) VALUES
(1, 'Alice Johnson', 'alice@email.com', 'Commission Request', 'I would like to commission a portrait painting similar to your Sunset Dreams piece. Can you provide more information about your process and pricing?', 'commission', 'new', NULL, NULL, '2025-11-16 23:06:52', '2025-11-16 23:06:52'),
(2, 'Bob Smith', 'bob.smith@company.com', 'Bulk Order for Office', 'We are interested in purchasing several digital prints for our new office space. Do you offer bulk discounts?', 'purchase', 'in_progress', NULL, NULL, '2025-11-16 23:06:52', '2025-11-16 23:06:52'),
(3, 'Carol Davis', 'carol.davis@email.com', 'Shipping Question', 'How long does shipping usually take for sculptures? Do you ship internationally?', 'general', 'resolved', NULL, NULL, '2025-11-16 23:06:52', '2025-11-16 23:06:52'),
(4, 'Alice Johnson', 'alice@email.com', 'Commission Request', 'I would like to commission a portrait painting similar to your Sunset Dreams piece. Can you provide more information about your process and pricing?', 'commission', 'new', NULL, NULL, '2025-11-16 23:07:22', '2025-11-16 23:07:22'),
(5, 'Bob Smith', 'bob.smith@company.com', 'Bulk Order for Office', 'We are interested in purchasing several digital prints for our new office space. Do you offer bulk discounts?', 'purchase', 'in_progress', NULL, NULL, '2025-11-16 23:07:22', '2025-11-16 23:07:22'),
(6, 'Carol Davis', 'carol.davis@email.com', 'Shipping Question', 'How long does shipping usually take for sculptures? Do you ship internationally?', 'general', 'resolved', NULL, NULL, '2025-11-16 23:07:22', '2025-11-16 23:07:22');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `artwork_id` int NOT NULL,
  `sku` varchar(100) NOT NULL,
  `condition` enum('mint','excellent','good','fair','poor') DEFAULT 'excellent',
  `location` varchar(255) DEFAULT NULL,
  `status` enum('in_stock','sold','reserved','shipped') DEFAULT 'in_stock',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int NOT NULL,
  `artwork_id` int NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_status` enum('pending','completed','refunded') DEFAULT 'pending',
  `shipping_address` text,
  `tracking_number` varchar(255) DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_type` enum('admin','user') DEFAULT 'user',
  `bio` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `created_at`, `user_type`, `bio`) VALUES
(10, 'Kim', 'Daly', 'KimDaly66', 'Kim.Daly@example.com', '$2y$10$/Iz2YlajQdK02LHYyh6KSezi.VogOXAyF5VQqxaZ8B3jiy.eO8Xsi', '2025-11-14 23:23:39', 'user', NULL),
(11, 'fran', 'kelly', 'fran.kelly', 'fran.kelly@example.com', '$2y$10$cC0CZoNwpt9fBi7igbQ2h..RZnDM6L2umAI3ncj3YfwkxDIHy/neC', '2025-12-03 20:37:18', 'user', NULL),
(12, 'Eli', 'Manning', 'eli12', 'eli12@example.com', '$2y$10$L8fpwgJH3jzBYEJjF3t9IOY4t.BOZjA4ZBjiO9PnSEBPWpVFt4UKm', '2025-12-06 18:47:08', 'user', NULL),
(13, 'dan', 'billy', 'dan.billy', 'danbilly@example.com', '$2y$10$ARzhVe0eFw0/H7r4OPPM.usPfxIKKQFVxHPKWhKMG2klW4BGRrYXG', '2025-12-06 19:05:52', 'user', NULL),
(14, 'tim', 'good', 'Tim.good', 'tim.good@example.com', '$2y$10$M7Yhe3q87ihoMLzqmqvvDeZNbWMGQvyeMHWqubcdw2V2MNlu4TqrK', '2025-12-06 19:20:23', 'user', NULL),
(15, 'ken', 'omg', 'ken.omg', 'ken.omg@example.com', '$2y$10$fV/ya/u7gYXWEM71izMHQOxUCir7BF0vV4eN4H21Tt2PSuXr.aTde', '2025-12-06 22:13:50', 'user', NULL),
(16, 'ali', 'kid', 'Ali.kid', 'ali.kid@example.com', '$2y$10$byz8bVYRm/YbK5aM792Be.UmSFTkDuN5qBRZjmLnqr8thED0Fu5Bm', '2025-12-07 20:43:26', 'user', NULL),
(17, 'rick', 'flair', 'rick.flair', 'rick.flair@example.com', '$2y$10$gGr86cYtGSrfHtkkw2yPGuiwxOCmGPD7/MFXz8VCtr/Udn01MNiAC', '2025-12-10 14:55:35', 'user', NULL),
(18, 'rick', 'blue', 'rick.blue', 'rick.blue@example.com', '$2y$10$GiesWRLyKcdsYo33XjtpcuiZEH.IRalKCZB8DTR90KEAb1d5dL7wq', '2025-12-10 17:48:30', 'user', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `artworks`
--
ALTER TABLE `artworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist_id` (`artist_id`);

--
-- Indexes for table `commissions`
--
ALTER TABLE `commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist_id` (`artist_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist_id` (`artist_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `artwork_id` (`artwork_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artwork_id` (`artwork_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artists`
--
ALTER TABLE `artists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `artworks`
--
ALTER TABLE `artworks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `commissions`
--
ALTER TABLE `commissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `artworks`
--
ALTER TABLE `artworks`
  ADD CONSTRAINT `artworks_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `commissions`
--
ALTER TABLE `commissions`
  ADD CONSTRAINT `commissions_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

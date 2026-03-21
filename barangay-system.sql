-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 01:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barangay-system`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `birthdate` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `civil_status` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `isf` varchar(10) NOT NULL,
  `household_head` varchar(150) NOT NULL,
  `pwd` varchar(10) NOT NULL,
  `solo_parent` varchar(10) NOT NULL,
  `pwd_proof` varchar(255) DEFAULT NULL,
  `solo_parent_proof` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'resident',
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `status_message` varchar(255) DEFAULT NULL,
  `status_message_seen` tinyint(1) NOT NULL DEFAULT 0,
  `proof_of_residency` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `fullname`, `birthdate`, `gender`, `civil_status`, `address`, `phone`, `email`, `username`, `password`, `isf`, `household_head`, `pwd`, `solo_parent`, `pwd_proof`, `solo_parent_proof`, `role`, `status`, `created_at`, `profile_image`, `status_message`, `status_message_seen`, `proof_of_residency`) VALUES
(1, 'Arron Rodriguez Perlas', '2026-01-20', 'male', 'single', 'kahit saan', '09999999999', 'arronperlas2017@gmail.com', 'Arron', '$2y$10$aVx9XTppmVw3RZ2tFJeZR.9L3oe/rTdiXV8.xipXAj/yq.uWKf6lK', 'no', 'Arron', 'no', 'no', NULL, NULL, 'Admin', 'active', '2026-01-19 16:33:09', NULL, NULL, 0, NULL),
(2, 'Katsu Rodriguez Perlas', '2026-01-20', 'male', 'single', 'sadad', '09991234567', 'katsu@gmail.com', 'Katsu', '$2y$10$60QEmS54HBsysZWmQJP0keOvrBtn3Qzqbo2QRtVQsgxKAHOlS.Eyq', 'no', 'Arron', 'no', 'no', NULL, NULL, 'resident', 'active', '2026-01-19 16:34:27', 'uploads/profiles/profile_2_1771397420.png', NULL, 0, NULL),
(3, 'Marcus Dominique  Muico', '2004-12-26', 'male', 'single', 'Blk.15 Lot 1 D Matuklasan, Kung Saan-Saan na Napadpad St. Naligaw City', '09121231234', 'marcusmuico70@gmail.com', 'makimaki', '$2y$10$muAO1bzwEc62O7mLLZ4AIOkKhaR.r06qhLbE5alf5k5MhzgESfeVG', 'no', 'n/a', 'no', 'no', NULL, NULL, 'resident', 'active', '2026-01-22 10:15:44', 'uploads/profiles/profile_3_1771243356.png', NULL, 0, NULL),
(7, 'Marcus admin', '2004-12-26', 'male', 'single', 'Blk.15 Lot 1 D Matuklasan, Kung Saan-Saan na Napadpad St. Naligaw City', '09121231234', 'makimuico@gmail.com', 'Maki admin', '$2y$10$3T5fy4VOfPHS5X/6wC/TyO5gd6gO6oRyHX144jqo3/QEOZytKsuEO', 'no', 'n/a', 'no', 'no', NULL, NULL, 'Admin', 'active', '2026-01-22 10:15:44', NULL, NULL, 0, NULL),
(8, 'maki maki muico', '2020-02-12', 'male', 'single', 'asdddddd', '09121231235', 'maki@gmail.com', 'makimakimaki', '$2y$10$1X2ogfG/ekRxtVpIyG72kOeca9vBmy5LWHocmOtlox12UnA8YWhve', 'yes', 'mama ko', 'yes', 'yes', 'uploads/ids/pwd_1771243652.pdf', 'uploads/ids/solo_1771243652.png', 'SuperAdmin', 'active', '2026-02-16 12:07:32', 'uploads/profiles/profile_8_1771244355.png', NULL, 0, NULL),
(9, 'Arron R. Perlas', '2026-02-19', 'male', 'single', 'secret', '09995668071', 'pikachu2477@gmail.com', 'Pika', '$2y$10$DIhFb.Df./6dt6PKmaBvmOk9w3Uu7nlVvvymgKpHEPf11wJHcXnsO', 'no', 'pikachu', 'yes', 'yes', 'uploads/ids/pwd_1772243174.jpg', 'uploads/ids/solo_1772243174.jpg', 'resident', 'active', '2026-02-28 01:46:14', NULL, NULL, 0, 'uploads/ids/residency_1772243174.png'),
(10, 'wefASDFASD FASDFASDF ASDFASDF', '2026-03-04', 'female', 'single', 'ASDASDASDA', '09996785642', 'ASDD@GMAUL.COM', 'ASDASDASD', '$2y$10$oFaMKaHS77xBwf7ZsXcRsOdaEJzN1JmkJf33.fFAwHLWkLe2sW3f.', 'no', 'DASDAS', 'no', 'no', NULL, NULL, 'resident', 'declined', '2026-03-02 13:03:06', NULL, NULL, 0, 'uploads/ids/residency_1772456586.jpg'),
(11, 'asdasdasda dasdasdas dasdasd', '2026-04-01', 'male', 'married', 'adsasdasdsada', '09998765467', 'asdasd@gmail.com', 'asda', '$2y$10$yCiwg0fx2LqATxRdulnXGOLn59fO4XrGp7HIMBdZ42ud.Xrb/LQkW', 'no', 'asdasd', 'no', 'no', NULL, NULL, 'resident', 'pending', '2026-03-02 13:20:10', NULL, NULL, 0, 'uploads/ids/residency_1772457610.jpg'),
(12, 'jgbcdjhdh mnbcjhg jhgfjtjh', '2026-03-13', 'male', 'single', 'lljkhljk', '09987656765', 'ron@gmail.com', 'opuihpou9', '$2y$10$OTwufNjX0LSqM3AVnsAj/ODc4KSdHJelFle.lsIOBmpJfXxx0fPPi', 'no', 'lkjgikhj', 'no', 'no', NULL, NULL, 'resident', 'pending', '2026-03-03 08:20:00', NULL, NULL, 0, 'uploads/ids/residency_1772526000.png'),
(13, 'JKAGSDKJ asdasdas asdasdasda', '2026-03-03', 'female', 'married', 'asdasdasdsadas', '09999878786', 'asdadasdasd@gmail.com', 'asd', '$2y$10$iqrvnw.knDXKUGmOT8g4Jek0KCpCKCy6ruxnMXtDb4wnowxGVHSE.', 'no', 'asdasd', 'no', 'no', NULL, NULL, 'resident', 'pending', '2026-03-03 12:57:52', NULL, NULL, 0, 'uploads/ids/residency_1772542672.png'),
(16, 'pat pat pat', '2026-03-06', 'male', 'separated', 'kahitsaan', '09991239999', 'patrick@gmail.com', 'pat', '$2y$10$UCFA.6hPslKP6oG4tGrWDeVSbjswU.UKAWh6LKlZKZSCPPkkJVz1S', 'yes', 'ron', 'no', 'yes', NULL, 'uploads/ids/solo_1772545962.jpg', 'resident', 'active', '2026-03-03 13:52:42', NULL, NULL, 0, 'uploads/ids/residency_1772545962.jpg'),
(17, 'Arron Rodriguez Perlas', '2026-03-18', 'male', 'single', 'Kahit saan basta jan sa tabi', '09989786564', 'pikachu242577@gmail.com', 'RONNNN', '$2y$10$1YRansx/5rY1n0OJ2lXWW.KA.fehQB4Rj4vQprHo0ezn0S2eLtsHe', 'no', 'Perlas senior', 'no', 'no', NULL, NULL, 'resident', 'active', '2026-03-18 12:47:10', NULL, NULL, 0, 'uploads/ids/residency_1773838030_69ba9ecec6cdf.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `admin_followups`
--

CREATE TABLE `admin_followups` (
  `id` int(11) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_followups`
--

INSERT INTO `admin_followups` (`id`, `sender_email`, `message`, `status`, `created_at`) VALUES
(2, 'maki@gmail.com', 'pafollow up po', 'Read', '2026-03-05 17:15:01'),
(3, 'ron@gmail.com', 'sddasad', 'New', '2026-03-05 17:51:08'),
(4, 'rron@gmail.com', 'paaccept pls', 'New', '2026-03-05 17:55:46'),
(5, 'pikachu2477@gmail.com', 'plss acpt my account', 'Read', '2026-03-18 10:49:19'),
(6, 'marcusmuico70@gmail.com', 'daadas', 'New', '2026-03-21 05:50:43');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `event_date`, `event_time`, `event_type`, `created_at`) VALUES
(1, '2026-02-18', '17:30:00', 'System Checking', '2026-02-15 18:47:32'),
(2, '2026-02-20', '18:30:00', 'Feeding Program', '2026-02-15 19:21:58');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `proof_of_donation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('New','Viewed','Approved','Declined') DEFAULT 'New'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `user_id`, `message`, `proof_of_donation`, `created_at`, `status`) VALUES
(1, 3, 'dsadsa', 'uploads/donations/barangay-system.sql', '2026-01-22 14:54:54', 'Approved'),
(2, 3, '1m', 'uploads/donations/updated-1-20-2026_BarangaySystem.zip', '2026-01-22 14:55:56', 'Declined'),
(3, 3, 'yes', 'uploads/donations/BARANGAY-292-OFFICIAL-DIRECTORY-2025.xlsx', '2026-01-22 14:56:03', 'New'),
(6, 2, 'adasdasdsad', 'uploads/donations/Screenshot 2025-08-31 185330.png', '2026-03-02 13:07:44', 'New'),
(7, 9, 'asdasdas', 'uploads/donations/Demonyo.jpg', '2026-03-18 11:03:10', 'New');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `feedback_text` text NOT NULL,
  `status` enum('New','Reviewed','Declined') DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `user_id`, `feedback_text`, `status`, `created_at`) VALUES
(3, 3, 'sdadasddas', 'Reviewed', '2026-01-22 15:04:45'),
(4, 3, 'sds', 'New', '2026-02-12 18:10:44'),
(5, 3, 'aaaaassaaaaaaa', 'New', '2026-02-12 18:25:05');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `email`, `ip_address`, `login_time`) VALUES
(1, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-19 16:35:04'),
(2, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-19 16:35:25'),
(3, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-19 16:45:10'),
(4, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-19 17:00:37'),
(5, 2, 'katsu@gmail.com', '::1', '2026-01-19 17:10:36'),
(6, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-19 17:44:52'),
(7, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 02:46:00'),
(8, 2, 'katsu@gmail.com', '::1', '2026-01-20 02:46:30'),
(9, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 03:42:54'),
(10, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 04:23:41'),
(11, 2, 'katsu@gmail.com', '::1', '2026-01-20 04:23:50'),
(12, 2, 'katsu@gmail.com', '::1', '2026-01-20 05:01:46'),
(13, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 05:02:03'),
(14, 2, 'katsu@gmail.com', '::1', '2026-01-20 05:06:15'),
(15, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 05:07:22'),
(16, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 05:11:04'),
(17, 1, 'arronperlas2017@gmail.com', '::1', '2026-01-20 05:11:45'),
(18, 2, 'katsu@gmail.com', '::1', '2026-01-20 05:11:54'),
(19, 2, 'katsu@gmail.com', '::1', '2026-01-22 10:17:27'),
(20, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 10:18:30'),
(21, 7, 'makimuico@gmail.com', '::1', '2026-01-22 10:20:36'),
(22, 7, 'makimuico@gmail.com', '::1', '2026-01-22 10:21:18'),
(23, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 10:22:39'),
(24, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 10:32:52'),
(25, 7, 'makimuico@gmail.com', '::1', '2026-01-22 10:33:14'),
(26, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 10:40:14'),
(27, 7, 'makimuico@gmail.com', '::1', '2026-01-22 11:11:56'),
(28, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 11:12:14'),
(29, 7, 'makimuico@gmail.com', '::1', '2026-01-22 13:46:50'),
(30, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 13:51:46'),
(31, 7, 'makimuico@gmail.com', '::1', '2026-01-22 13:52:31'),
(32, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:04:16'),
(33, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:04:38'),
(34, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:08:46'),
(35, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:10:01'),
(36, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:15:33'),
(37, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:16:04'),
(38, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:19:33'),
(39, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:19:58'),
(40, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:22:40'),
(41, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:22:47'),
(42, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:23:12'),
(43, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:25:21'),
(44, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:28:51'),
(45, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:29:23'),
(46, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:29:47'),
(47, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:34:44'),
(48, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:34:56'),
(49, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:39:12'),
(50, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:55:08'),
(51, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:55:49'),
(52, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:56:13'),
(53, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 14:58:04'),
(54, 7, 'makimuico@gmail.com', '::1', '2026-01-22 14:58:23'),
(55, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 15:04:40'),
(56, 7, 'makimuico@gmail.com', '::1', '2026-01-22 15:04:51'),
(57, 7, 'makimuico@gmail.com', '::1', '2026-01-22 15:19:23'),
(58, 3, 'marcusmuico70@gmail.com', '::1', '2026-01-22 15:19:29'),
(59, 7, 'makimuico@gmail.com', '::1', '2026-01-22 15:20:20'),
(60, 7, 'makimuico@gmail.com', '::1', '2026-02-12 15:26:06'),
(61, 7, 'makimuico@gmail.com', '::1', '2026-02-12 16:45:51'),
(62, 7, 'makimuico@gmail.com', '::1', '2026-02-12 17:07:58'),
(63, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 17:57:29'),
(64, 7, 'makimuico@gmail.com', '::1', '2026-02-12 17:58:35'),
(65, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:09:43'),
(66, 7, 'makimuico@gmail.com', '::1', '2026-02-12 18:10:58'),
(67, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:11:32'),
(68, 7, 'makimuico@gmail.com', '::1', '2026-02-12 18:23:57'),
(69, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:24:04'),
(70, 7, 'makimuico@gmail.com', '::1', '2026-02-12 18:25:25'),
(71, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:25:58'),
(72, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:31:11'),
(73, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:32:30'),
(74, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:33:51'),
(75, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:34:41'),
(76, 7, 'makimuico@gmail.com', '::1', '2026-02-12 18:34:49'),
(77, 7, 'makimuico@gmail.com', '::1', '2026-02-12 18:35:23'),
(78, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:35:32'),
(79, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:36:06'),
(80, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-12 18:38:40'),
(81, 7, 'makimuico@gmail.com', '::1', '2026-02-14 03:41:39'),
(82, 7, 'makimuico@gmail.com', '::1', '2026-02-15 07:35:53'),
(83, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 07:38:45'),
(84, 7, 'makimuico@gmail.com', '::1', '2026-02-15 07:39:26'),
(85, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 07:40:56'),
(86, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 14:37:12'),
(87, 7, 'makimuico@gmail.com', '::1', '2026-02-15 14:47:44'),
(88, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 14:48:00'),
(89, 7, 'makimuico@gmail.com', '::1', '2026-02-15 14:48:10'),
(90, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 14:48:22'),
(91, 7, 'makimuico@gmail.com', '::1', '2026-02-15 14:48:35'),
(92, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 14:48:47'),
(93, 7, 'makimuico@gmail.com', '::1', '2026-02-15 14:50:08'),
(94, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 15:03:06'),
(95, 7, 'makimuico@gmail.com', '::1', '2026-02-15 15:03:21'),
(96, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 15:03:39'),
(97, 7, 'makimuico@gmail.com', '::1', '2026-02-15 15:04:42'),
(98, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 15:04:58'),
(99, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 15:35:50'),
(100, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 16:25:45'),
(101, 7, 'makimuico@gmail.com', '::1', '2026-02-15 16:48:51'),
(102, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 16:57:00'),
(103, 7, 'makimuico@gmail.com', '::1', '2026-02-15 17:04:45'),
(104, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 17:04:54'),
(105, 7, 'makimuico@gmail.com', '::1', '2026-02-15 17:05:09'),
(106, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 18:30:44'),
(107, 7, 'makimuico@gmail.com', '::1', '2026-02-15 18:34:21'),
(108, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 18:34:56'),
(109, 7, 'makimuico@gmail.com', '::1', '2026-02-15 18:44:47'),
(110, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 18:47:42'),
(111, 7, 'makimuico@gmail.com', '::1', '2026-02-15 19:07:42'),
(112, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 19:11:34'),
(113, 7, 'makimuico@gmail.com', '::1', '2026-02-15 19:19:38'),
(114, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 19:22:09'),
(115, 7, 'makimuico@gmail.com', '::1', '2026-02-15 19:41:47'),
(116, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 19:50:06'),
(117, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-15 19:52:19'),
(118, 7, 'makimuico@gmail.com', '::1', '2026-02-16 11:58:43'),
(119, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 12:01:32'),
(120, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:03:11'),
(121, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:08:09'),
(122, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 12:10:40'),
(123, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:10:50'),
(124, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 12:11:07'),
(125, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:11:44'),
(126, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 12:12:06'),
(127, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:12:22'),
(128, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 12:13:25'),
(129, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:14:14'),
(130, 8, 'maki@gmail.com', '::1', '2026-02-16 12:16:52'),
(131, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:17:51'),
(132, 8, 'maki@gmail.com', '::1', '2026-02-16 12:18:51'),
(133, 7, 'makimuico@gmail.com', '::1', '2026-02-16 12:19:26'),
(134, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 12:21:36'),
(135, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:06:52'),
(136, 3, 'marcusmuico70@gmail.com', '::1', '2026-02-16 14:07:10'),
(137, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:08:20'),
(138, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:41:49'),
(139, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:42:24'),
(140, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:42:32'),
(141, 8, 'maki@gmail.com', '::1', '2026-02-16 14:50:16'),
(142, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:50:31'),
(143, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:53:45'),
(144, 7, 'makimuico@gmail.com', '::1', '2026-02-16 14:59:10'),
(145, 8, 'maki@gmail.com', '::1', '2026-02-16 14:59:54'),
(146, 7, 'makimuico@gmail.com', '::1', '2026-02-16 15:00:45'),
(147, 1, 'arronperlas2017@gmail.com', '::1', '2026-02-17 12:33:21'),
(148, 2, 'katsu@gmail.com', '::1', '2026-02-17 12:33:31'),
(149, 2, 'katsu@gmail.com', '::1', '2026-02-18 06:46:06'),
(150, 1, 'arronperlas2017@gmail.com', '::1', '2026-02-18 06:50:46'),
(151, 1, 'arronperlas2017@gmail.com', '::1', '2026-02-28 01:33:03'),
(152, 1, 'arronperlas2017@gmail.com', '::1', '2026-02-28 01:46:24'),
(153, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-02 12:52:18'),
(154, 2, 'katsu@gmail.com', '::1', '2026-03-02 12:57:23'),
(155, 2, 'katsu@gmail.com', '::1', '2026-03-02 13:04:58'),
(156, 2, 'katsu@gmail.com', '::1', '2026-03-02 13:05:13'),
(157, 2, 'katsu@gmail.com', '::1', '2026-03-02 13:06:45'),
(158, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-02 13:08:39'),
(159, 2, 'katsu@gmail.com', '::1', '2026-03-02 13:10:35'),
(160, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 08:16:09'),
(161, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 08:20:34'),
(162, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 12:46:51'),
(163, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 12:49:00'),
(164, 2, 'katsu@gmail.com', '::1', '2026-03-03 12:55:30'),
(165, 2, 'katsu@gmail.com', '::1', '2026-03-03 12:58:09'),
(166, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 12:58:44'),
(167, 2, 'katsu@gmail.com', '::1', '2026-03-03 13:00:32'),
(168, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:04:19'),
(169, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:06:35'),
(170, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:18:27'),
(171, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:28:17'),
(172, 2, 'katsu@gmail.com', '::1', '2026-03-03 13:28:25'),
(173, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:32:22'),
(174, 2, 'katsu@gmail.com', '::1', '2026-03-03 13:37:16'),
(175, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:39:57'),
(176, 2, 'katsu@gmail.com', '::1', '2026-03-03 13:40:15'),
(177, 2, 'katsu@gmail.com', '::1', '2026-03-03 13:43:03'),
(178, 2, 'katsu@gmail.com', '::1', '2026-03-03 13:44:37'),
(179, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:45:19'),
(180, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:50:14'),
(181, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-03 13:52:52'),
(182, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-05 03:53:58'),
(183, 2, 'katsu@gmail.com', '::1', '2026-03-05 03:57:15'),
(184, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-05 04:00:50'),
(185, 7, 'makimuico@gmail.com', '::1', '2026-03-05 15:39:49'),
(186, 7, 'makimuico@gmail.com', '::1', '2026-03-05 16:42:33'),
(187, 7, 'makimuico@gmail.com', '::1', '2026-03-05 16:50:10'),
(188, 7, 'makimuico@gmail.com', '::1', '2026-03-05 16:55:34'),
(189, 7, 'makimuico@gmail.com', '::1', '2026-03-05 17:10:13'),
(190, 7, 'makimuico@gmail.com', '::1', '2026-03-05 17:10:56'),
(191, 7, 'makimuico@gmail.com', '::1', '2026-03-05 17:15:17'),
(192, 7, 'makimuico@gmail.com', '::1', '2026-03-05 17:40:22'),
(193, 7, 'makimuico@gmail.com', '::1', '2026-03-05 17:51:18'),
(194, 7, 'makimuico@gmail.com', '::1', '2026-03-05 17:55:57'),
(195, 7, 'makimuico@gmail.com', '::1', '2026-03-05 18:01:51'),
(196, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-05 18:32:43'),
(197, 7, 'makimuico@gmail.com', '::1', '2026-03-05 18:33:22'),
(198, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-13 07:07:59'),
(199, 2, 'katsu@gmail.com', '::1', '2026-03-13 07:09:05'),
(200, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-13 07:11:06'),
(201, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:18:17'),
(202, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:22:51'),
(203, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:26:18'),
(204, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 02:27:25'),
(205, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 02:29:51'),
(206, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:30:02'),
(207, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 02:32:03'),
(208, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:35:45'),
(209, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 02:36:18'),
(210, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:38:18'),
(211, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 02:40:55'),
(212, 2, 'katsu@gmail.com', '::1', '2026-03-18 02:41:10'),
(213, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 02:48:11'),
(214, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 03:01:42'),
(215, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 03:36:08'),
(216, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 03:39:52'),
(217, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 03:40:06'),
(218, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 03:40:21'),
(219, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 03:49:56'),
(220, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 04:23:46'),
(221, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 04:23:53'),
(222, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 04:24:19'),
(223, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 04:29:30'),
(224, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 06:41:48'),
(225, 2, 'katsu@gmail.com', '::1', '2026-03-18 06:42:01'),
(226, 2, 'katsu@gmail.com', '::1', '2026-03-18 06:43:02'),
(227, 2, 'katsu@gmail.com', '::1', '2026-03-18 06:46:26'),
(228, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 06:47:41'),
(229, 2, 'katsu@gmail.com', '::1', '2026-03-18 06:49:19'),
(230, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 06:49:29'),
(231, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 09:49:46'),
(232, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 10:43:52'),
(233, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 10:45:21'),
(234, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 10:47:28'),
(235, 2, 'katsu@gmail.com', '::1', '2026-03-18 10:47:59'),
(236, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 10:49:24'),
(237, 9, 'pikachu2477@gmail.com', '::1', '2026-03-18 10:52:26'),
(238, 9, 'pikachu2477@gmail.com', '::1', '2026-03-18 10:53:01'),
(239, 9, 'pikachu2477@gmail.com', '::1', '2026-03-18 11:02:35'),
(240, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 12:47:26'),
(241, 17, 'pikachu242577@gmail.com', '::1', '2026-03-18 12:48:09'),
(242, 17, 'pikachu242577@gmail.com', '::1', '2026-03-18 12:51:34'),
(243, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 14:39:30'),
(244, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 17:55:37'),
(245, 17, 'pikachu242577@gmail.com', '::1', '2026-03-18 18:04:45'),
(246, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 18:07:35'),
(247, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 18:11:15'),
(250, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 18:15:54'),
(253, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 18:19:05'),
(255, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 18:29:26'),
(257, 1, 'arronperlas2017@gmail.com', '::1', '2026-03-18 18:30:46'),
(259, 7, 'makimuico@gmail.com', '::1', '2026-03-21 03:21:16'),
(260, 8, 'maki@gmail.com', '::1', '2026-03-21 03:44:16'),
(261, 7, 'makimuico@gmail.com', '::1', '2026-03-21 03:44:59'),
(262, 8, 'maki@gmail.com', '::1', '2026-03-21 03:45:14'),
(263, 8, 'maki@gmail.com', '::1', '2026-03-21 03:45:22'),
(264, 8, 'maki@gmail.com', '::1', '2026-03-21 03:46:40'),
(265, 7, 'makimuico@gmail.com', '::1', '2026-03-21 03:47:16'),
(266, 7, 'makimuico@gmail.com', '::1', '2026-03-21 03:48:32'),
(267, 7, 'makimuico@gmail.com', '::1', '2026-03-21 03:49:28'),
(268, 8, 'maki@gmail.com', '::1', '2026-03-21 03:49:42'),
(269, 7, 'makimuico@gmail.com', '::1', '2026-03-21 03:55:20'),
(270, 8, 'maki@gmail.com', '::1', '2026-03-21 03:55:42'),
(271, 7, 'makimuico@gmail.com', '::1', '2026-03-21 04:09:02'),
(272, 8, 'maki@gmail.com', '::1', '2026-03-21 04:09:37'),
(273, 8, 'maki@gmail.com', '::1', '2026-03-21 04:12:56'),
(274, 8, 'maki@gmail.com', '::1', '2026-03-21 04:13:35'),
(275, 8, 'maki@gmail.com', '::1', '2026-03-21 04:17:21'),
(276, 7, 'makimuico@gmail.com', '::1', '2026-03-21 04:26:13'),
(277, 8, 'maki@gmail.com', '::1', '2026-03-21 04:26:22'),
(278, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-21 04:30:40'),
(279, 7, 'makimuico@gmail.com', '::1', '2026-03-21 04:31:00'),
(280, 8, 'maki@gmail.com', '::1', '2026-03-21 04:42:26'),
(281, 7, 'makimuico@gmail.com', '::1', '2026-03-21 04:52:13'),
(282, 8, 'maki@gmail.com', '::1', '2026-03-21 04:52:26'),
(283, 7, 'makimuico@gmail.com', '::1', '2026-03-21 04:54:20'),
(284, 17, 'pikachu242577@gmail.com', '::1', '2026-03-21 04:56:13'),
(285, 8, 'maki@gmail.com', '::1', '2026-03-21 04:56:50'),
(286, 7, 'makimuico@gmail.com', '::1', '2026-03-21 04:59:07'),
(287, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-21 04:59:20'),
(288, 8, 'maki@gmail.com', '::1', '2026-03-21 05:00:15'),
(289, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-21 05:15:27'),
(290, 8, 'maki@gmail.com', '::1', '2026-03-21 05:16:28'),
(291, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-21 05:17:12'),
(292, 8, 'maki@gmail.com', '::1', '2026-03-21 05:17:19'),
(293, 8, 'maki@gmail.com', '::1', '2026-03-21 05:50:11'),
(294, 8, 'maki@gmail.com', '::1', '2026-03-21 05:50:49'),
(295, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-21 05:54:45'),
(296, 8, 'maki@gmail.com', '::1', '2026-03-21 05:54:56'),
(297, 8, 'maki@gmail.com', '::1', '2026-03-21 06:31:33'),
(298, 8, 'maki@gmail.com', '::1', '2026-03-21 06:46:24'),
(299, 3, 'marcusmuico70@gmail.com', '::1', '2026-03-21 06:46:57'),
(300, 8, 'maki@gmail.com', '::1', '2026-03-21 06:47:05');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `email`, `code`, `expires_at`, `used`, `created_at`) VALUES
(3, 'arronperlas2017@gmail.com', '$2y$10$fs5otJAxIz3Xt5OmwKoiJu4WfKpIUSQ02rpjLHwDKJiZVkUngO.xW', '2026-03-18 11:52:41', 1, '2026-03-18 10:42:41'),
(5, 'arronperlas2017@gmail.com', '$2y$10$h2I5DwYIX738YGshdDBT5eKdEtXFnTt4Jr6ws66dnfGcVyg3hTRZu', '2026-03-18 11:54:54', 1, '2026-03-18 10:44:54'),
(6, 'arronperlas2017@gmail.com', '$2y$10$tsJcWRqDpP0AK0V1n3tPtuGDQxKiFXR.0HRMJ0UHVNi.xDyu.cd6S', '2026-03-18 11:56:58', 1, '2026-03-18 10:46:58'),
(9, 'katsu@gmail.com', '$2y$10$DILW6C0HwBLTy1rK1Sk5P.hqT0hdWrRoGLBFQa1LzPLBgHqVsUxKG', '2026-03-18 12:08:07', 0, '2026-03-18 10:58:07'),
(10, 'marcusmuico70@gmail.com', '$2y$10$Js.MHfuzS81jF5TQwqQ6CuKJzU6vENQ7oy5bruQZ5Jq0WZeP6VktK', '2026-03-18 12:08:11', 0, '2026-03-18 10:58:11'),
(11, 'makimuico@gmail.com', '$2y$10$2.gGpQFZxPG9n1cOO4Bl5u7F35yAuSx5LsEOqheQVtWnGVclmmNrK', '2026-03-18 12:08:16', 0, '2026-03-18 10:58:16'),
(12, 'maki@gmail.com', '$2y$10$L1NHhiLiqb244DCzT6LzluG.JEJWOIbvFb.GQxNeuj0TW5I.aLSO.', '2026-03-18 12:08:20', 0, '2026-03-18 10:58:20'),
(35, 'arronperlas2017@gmail.com', '$2y$10$widT2.B.cS2PSnPGcpYGEO0RkUNEFh4mxG0OKIEFvm9vz.Wz3qWRi', '2026-03-18 13:34:12', 0, '2026-03-18 12:24:12'),
(39, 'pikachu2477@gmail.com', '$2y$10$Eh0IeJ6f5KScFHS9lOT2k.Nfc4Nd10ssVe9K1k.erITh9w2BCHFzq', '2026-03-18 13:54:51', 0, '2026-03-18 12:44:51'),
(41, 'pikachu242577@gmail.com', '$2y$10$jKp073Yc3VH1P8WigsvcyuAP7b5aya3jCII/yhCWQ2q3x9Sz3Yggm', '2026-03-18 13:58:47', 1, '2026-03-18 12:48:47'),
(42, 'pikachu242577@gmail.com', '$2y$10$/kPaWsX9ZRfY3/raHy4CLOIHaAovSTd4puTmSXPEfIRHoY18lv/Gu', '2026-03-18 14:00:55', 1, '2026-03-18 12:50:55');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `person_reported` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `proof` varchar(255) DEFAULT NULL,
  `specify` text DEFAULT NULL,
  `status` enum('Pending','Resolved','Ongoing','Declined') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decline_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `reason`, `person_reported`, `address`, `proof`, `specify`, `status`, `created_at`, `decline_reason`) VALUES
(1, 7, 'Vandalism', 'dsad', 'asdasda', 'uploads/reports/BARANGAY-292-OFFICIAL-DIRECTORY-2025.xlsx', 'sasddasd', 'Resolved', '2026-01-22 14:03:16', NULL),
(2, 3, 'Vandalism', 'Tracy Alamo', 'Hulo', 'uploads/reports/barangay-system.sql', 'Drinowingan bakod ko', 'Declined', '2026-01-22 14:09:48', NULL),
(3, 3, 'Noise Disturbance', 'Arron', 'Don sa kanto', 'uploads/reports/barangay-system.sql', 'Ingay ng speaker', 'Resolved', '2026-01-22 14:25:54', NULL),
(16, 3, 'Suspicious Activity', 'ddasddddasa', 'ddasddadasdas', 'uploads/reports/1771243896_627686341_1249429603793758_3506346902432469639_n.png', 'adsdddddasdd', 'Declined', '2026-02-16 12:11:36', ''),
(17, 2, 'Noise Disturbance', 'my neighbor', 'kahit saan', 'uploads/reports/1772456795_Screenshot 2026-02-27 153347.png', 'lkj HAVDFGYHJKASDfv hlkjasfdlhkas', 'Pending', '2026-03-02 13:06:35', NULL),
(18, 9, 'Noise Disturbance', 'asd', 'asd', 'uploads/reports/1773831204_Demonyo.jpg', 'asd', 'Pending', '2026-03-18 10:53:24', NULL),
(19, 9, 'Noise Disturbance', 'asd', 'asd', 'uploads/reports/1773831778_Demonyo.jpg', 'asd', 'Resolved', '2026-03-18 11:02:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_address` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `date_requested` datetime DEFAULT current_timestamp(),
  `decline_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `user_id`, `fullname`, `address`, `purpose`, `document_type`, `guardian_name`, `guardian_address`, `guardian_contact`, `status`, `created_at`, `date_requested`, `decline_reason`) VALUES
(1, 2, 'asd', 'asda', 'adsad', 'Barangay Indigency', '', '', '', 'Declined', '2026-01-20 11:16:19', '2026-01-20 11:57:18', NULL),
(3, 1, 'Katsu Rodriguez Perlas', 'asdasda', 'asdasd', 'Barangay Clearance', '', '', '', 'Approved', '2026-01-20 12:16:49', '2026-01-20 12:16:49', NULL),
(4, 1, 'Patrick garcia', 'asdasd', 'asdasd', 'Barangay ID', '', '', '', 'Approved', '2026-01-20 13:05:38', '2026-01-20 13:05:38', NULL),
(5, 2, 'Patrick garcia', 'asdasd', 'asdasd', 'First Time Job Seeker', '', '', '', 'Declined', '2026-01-20 13:06:41', '2026-01-20 13:06:41', ''),
(21, 8, 'Marcus Dominique  Muico', 'Blk.15 Lot 1 D Matuklasan, Kung Saan-Saan na Napadpad St. Naligaw City', 'Basta kelangan ko ', 'Barangay Clearance', '', '', '', 'Declined', '2026-02-16 23:00:16', '2026-02-16 23:00:16', ''),
(22, 2, 'Katsu Rodriguez Perlas', 'Hulaan mo', 'For job ', 'Barangay ID', 'ewan ko', 'kahit saan', '19209348-120-370-12', 'Declined', '2026-03-02 21:07:32', '2026-03-02 21:07:32', 'Test'),
(23, 2, 'Katsu Rodriguez Perlas', 'Hulaan mo', 'For job ', 'Barangay Clearance', '', '', '', 'Declined', '2026-03-03 21:39:52', '2026-03-03 21:39:52', 'test'),
(24, 3, 'Marcus Dominique  Muico', 'Blk.15 Lot 1 D Matuklasan, Kung Saan-Saan na Napadpad St. Naligaw City', 'sdasdsad', 'Barangay Clearance', '', '', '', 'Approved', '2026-03-21 12:30:53', '2026-03-21 12:30:53', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_followups`
--
ALTER TABLE `admin_followups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reports_user` (`user_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `admin_followups`
--
ALTER TABLE `admin_followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

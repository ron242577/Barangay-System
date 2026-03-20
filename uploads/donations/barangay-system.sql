-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2026 at 02:59 PM
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
-- Database: `barangay_system`
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `fullname`, `birthdate`, `gender`, `civil_status`, `address`, `phone`, `email`, `username`, `password`, `isf`, `household_head`, `pwd`, `solo_parent`, `pwd_proof`, `solo_parent_proof`, `role`, `status`, `created_at`) VALUES
(1, 'Arron Rodriguez Perlas', '2026-01-20', 'male', 'single', 'kahit saan', '09999999999', 'arronperlas2017@gmail.com', 'Arron', '12345678', 'no', 'Arron', 'no', 'no', NULL, NULL, 'Admin', 'active', '2026-01-19 16:33:09'),
(2, 'Katsu Rodriguez Perlas', '2026-01-20', 'male', 'single', 'sadad', '09991234567', 'katsu@gmail.com', 'Katsu', '12345678', 'no', 'Arron', 'no', 'no', NULL, NULL, 'resident', 'active', '2026-01-19 16:34:27'),
(3, 'Marcus Dominique  Muico', '2004-12-26', 'male', 'single', 'Blk.15 Lot 1 D Matuklasan, Kung Saan-Saan na Napadpad St. Naligaw City', '09121231234', 'marcusmuico70@gmail.com', 'Maki', 'maki1234', 'no', 'n/a', 'no', 'no', NULL, NULL, 'resident', 'active', '2026-01-22 10:15:44'),
(7, 'Marcus admin', '2004-12-26', 'male', 'single', 'Blk.15 Lot 1 D Matuklasan, Kung Saan-Saan na Napadpad St. Naligaw City', '09121231234', 'makimuico@gmail.com', 'Maki admin', 'maki1234', 'no', 'n/a', 'no', 'no', NULL, NULL, 'Admin', 'active', '2026-01-22 10:15:44');

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
(31, 7, 'makimuico@gmail.com', '::1', '2026-01-22 13:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `date_requested` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `user_id`, `fullname`, `address`, `purpose`, `document_type`, `guardian_name`, `guardian_address`, `guardian_contact`, `status`, `created_at`, `date_requested`) VALUES
(1, 2, 'asd', 'asda', 'adsad', 'Barangay Indigency', '', '', '', 'Pending', '2026-01-20 11:16:19', '2026-01-20 11:57:18'),
(3, 1, 'Katsu Rodriguez Perlas', 'asdasda', 'asdasd', 'Barangay Clearance', '', '', '', 'Approved', '2026-01-20 12:16:49', '2026-01-20 12:16:49'),
(4, 1, 'Patrick garcia', 'asdasd', 'asdasd', 'Barangay ID', '', '', '', 'Pending', '2026-01-20 13:05:38', '2026-01-20 13:05:38'),
(5, 2, 'Patrick garcia', 'asdasd', 'asdasd', 'First Time Job Seeker', '', '', '', 'Declined', '2026-01-20 13:06:41', '2026-01-20 13:06:41'),
(6, 3, 'Maki Muico', 'afsfdsasdg', 'Basta kelangan ko ', 'Barangay ID', '', '', '', 'Approved', '2026-01-22 18:29:55', '2026-01-22 18:29:55');

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
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

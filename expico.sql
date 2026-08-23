-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2026 at 07:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `expico`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expense_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `expense_id`) VALUES
(1, 'Food', 'Updated from expense form', 1),
(2, 'Design', 'Added from subscription form', NULL),
(3, 'Relax', 'Added from expense form', 2),
(4, 'FOOD', 'Added from expense form', 3),
(5, 'Transport', 'Added from expense form', 4),
(6, 'Expense', 'Updated from expense form', 5),
(7, 'FOOD', 'Added from expense form', 6);

-- --------------------------------------------------------

--
-- Table structure for table `deadlines`
--

CREATE TABLE `deadlines` (
  `deadline_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(50) DEFAULT 'upcoming',
  `subscription_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deadlines`
--

INSERT INTO `deadlines` (`deadline_id`, `due_date`, `status`, `subscription_id`) VALUES
(1, '2026-09-15', 'upcoming', 1),
(2, '2026-09-01', 'upcoming', 2);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `amount` double NOT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `title`, `amount`, `expense_date`, `created_at`, `group_id`, `user_id`) VALUES
(1, 'Dinner', 1200, '2026-08-15', '2026-08-15 06:43:24', 1, 1),
(2, 'Sauna', 5000, '2026-08-15', '2026-08-15 17:54:18', 1, 6),
(3, 'Dinner', 1200, '2026-08-15', '2026-08-15 18:24:02', 1, 4),
(4, 'Potenga Auto', 150, '2026-08-15', '2026-08-15 19:06:31', 1, 2),
(5, 'Electricity Bill', 512, '2026-08-16', '2026-08-16 09:00:33', 5, 1),
(6, 'Dinner', 1200, '2026-08-16', '2026-08-16 09:02:34', 5, 4);

-- --------------------------------------------------------

--
-- Table structure for table `expenses_participants`
--

CREATE TABLE `expenses_participants` (
  `user_id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `share_amount` double NOT NULL,
  `is_settled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses_participants`
--

INSERT INTO `expenses_participants` (`user_id`, `expense_id`, `share_amount`, `is_settled`) VALUES
(1, 1, 300, 0),
(1, 2, 1250, 1),
(1, 3, 300, 0),
(1, 4, 37.5, 0),
(1, 5, 170.66666666666666, 0),
(1, 6, 600, 0),
(2, 1, 300, 1),
(2, 2, 1250, 1),
(2, 3, 300, 0),
(2, 4, 37.5, 0),
(2, 5, 170.66666666666666, 1),
(4, 1, 300, 1),
(4, 2, 1250, 0),
(4, 3, 300, 0),
(4, 4, 37.5, 0),
(4, 5, 170.66666666666666, 0),
(4, 6, 600, 0),
(6, 1, 300, 1),
(6, 2, 1250, 0),
(6, 3, 300, 0),
(6, 4, 37.5, 0);

-- --------------------------------------------------------

--
-- Table structure for table `get`
--

CREATE TABLE `get` (
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `get`
--

INSERT INTO `get` (`user_id`, `subscription_id`) VALUES
(1, 1),
(1, 2),
(2, 2),
(4, 2),
(6, 2);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `settlement_deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `description`, `created_at`, `settlement_deadline`) VALUES
(1, 'Cox’s Bazar Trip', 'Cox\'s Bazas Trip with dosto', '2026-08-15 06:41:04', '2026-08-16'),
(2, 'Date', '', '2026-08-15 16:18:07', NULL),
(4, 'Hostel group', 'This group is basically for the roommates who live together in a house, and they share the expenses of daily necessities and houserents etc', '2026-08-15 17:37:24', NULL),
(5, 'Room 311', 'Roommates for NSU', '2026-08-16 08:59:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`user_id`, `group_id`, `joined_at`, `role`) VALUES
(1, 1, '2026-08-15 06:41:04', 'Admin'),
(1, 2, '2026-08-15 18:00:36', 'Member'),
(1, 5, '2026-08-16 08:59:45', 'Admin'),
(2, 1, '2026-08-15 16:16:59', 'Member'),
(2, 4, '2026-08-15 19:05:20', 'Member'),
(2, 5, '2026-08-16 09:07:29', 'Member'),
(4, 1, '2026-08-15 16:18:04', 'Member'),
(4, 4, '2026-08-15 17:37:24', 'Admin'),
(4, 5, '2026-08-16 09:01:32', 'Member'),
(5, 2, '2026-08-15 16:18:07', 'Admin'),
(6, 1, '2026-08-15 17:52:29', 'Member'),
(6, 4, '2026-08-15 18:04:38', 'Member');

-- --------------------------------------------------------

--
-- Table structure for table `is_settled`
--

CREATE TABLE `is_settled` (
  `user_id` int(11) NOT NULL,
  `settlement_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deadline_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `message`, `is_read`, `sent_at`, `deadline_id`, `user_id`) VALUES
(1, 'You owe Sam ৳50.00 for Spotify.', 1, '2026-08-15 18:07:35', NULL, 1),
(2, 'You owe Sam ৳50.00 for Spotify.', 0, '2026-08-15 18:08:22', NULL, 1),
(3, 'You owe Muhammad Mohsin ৳300.00 for Dinner.', 1, '2026-08-15 18:13:04', NULL, 6),
(4, 'Sam paid you ৳300.00 for Dinner.', 0, '2026-08-15 18:13:49', NULL, 1),
(5, 'You owe Muhammad Mohsin ৳300.00 for Dinner.', 0, '2026-08-15 18:14:45', NULL, 4),
(6, 'You owe Sam ৳1,250.00 for Sauna.', 0, '2026-08-15 18:14:45', NULL, 4),
(7, 'You owe Sam ৳50.00 for Spotify.', 0, '2026-08-15 18:14:45', NULL, 4),
(8, 'Yeakub Ali Dhony paid you ৳1,250.00 for Sauna.', 1, '2026-08-15 18:15:53', NULL, 6),
(9, 'Yeakub Ali Dhony paid you ৳50.00 for Spotify.', 1, '2026-08-15 18:16:09', NULL, 6),
(10, 'Yeakub Ali Dhony paid you ৳300.00 for Dinner.', 0, '2026-08-15 18:16:24', NULL, 1),
(11, 'Nanto paid you ৳300.00 for Dinner.', 0, '2026-08-15 18:18:17', NULL, 1),
(12, 'You owe Nanto ৳300.00 for Dinner.', 0, '2026-08-15 18:25:08', NULL, 6),
(13, 'You owe Nanto ৳300.00 for Dinner.', 0, '2026-08-15 19:31:47', NULL, 2),
(14, 'You owe Nanto ৳300.00 for Dinner.', 0, '2026-08-16 08:48:02', NULL, 1),
(15, 'You owe Yeakub Ali Dhony ৳37.50 for Potenga Auto.', 0, '2026-08-16 08:48:02', NULL, 1),
(16, 'You owe Muhammad Mohsin ৳170.67 for Electricity Bill.', 1, '2026-08-16 09:07:56', NULL, 2),
(17, 'You owe Nanto ৳600.00 for Dinner.', 0, '2026-08-16 09:08:29', NULL, 1),
(18, 'Yeakub Ali Dhony paid you ৳170.67 for Room 311.', 0, '2026-08-16 09:09:29', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `payment_method_id` int(11) NOT NULL,
  `method_type` varchar(30) NOT NULL,
  `account_details` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `expense_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`payment_method_id`, `method_type`, `account_details`, `is_default`, `expense_id`, `user_id`) VALUES
(1, 'Bkash', '01575314702', 1, 5, 1),
(2, 'bkash', '1969', 1, 6, 4),
(3, 'Bkash', '0123456789', 1, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `settlements`
--

CREATE TABLE `settlements` (
  `settlement_id` int(11) NOT NULL,
  `amount` double NOT NULL,
  `settlement_date` date NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `group_id` int(11) NOT NULL,
  `paid_by` int(11) NOT NULL,
  `paid_to` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `amount` double NOT NULL,
  `billing_cycle` varchar(20) DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`subscription_id`, `name`, `amount`, `billing_cycle`, `next_due_date`, `payment_method_id`, `category_id`, `group_id`, `user_id`) VALUES
(1, 'Canva', 250, 'monthly', '2026-09-15', 1, 2, NULL, NULL),
(2, 'Spotify', 200, 'monthly', '2026-09-01', NULL, NULL, 1, 6);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_participants`
--

CREATE TABLE `subscription_participants` (
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `share_amount` double NOT NULL,
  `is_settled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_participants`
--

INSERT INTO `subscription_participants` (`user_id`, `subscription_id`, `share_amount`, `is_settled`) VALUES
(1, 2, 50, 1),
(2, 2, 50, 1),
(4, 2, 50, 0),
(6, 2, 50, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `phone_no`, `created_at`) VALUES
(1, 'Muhammad Mohsin', 'mmohsin0804@gmail.com', '$2y$12$3a.fBRFWr34wZw8cGevDs.NwBcWG.WmqWeRjk6iSCW0NyUMRASe76', '01575314702', '2026-08-15 06:08:45'),
(2, 'Yeakub Ali Dhony', 'yeakub.ali.rony@gmail.com', '$2y$12$BrNmFe8H02z21618rprhhu5oPIKNGtpN8vN7forKsZw74Z6WXVhDq', '+8801575314702', '2026-08-15 06:30:20'),
(3, 'Tasnuva Islam Ayona', 'tasnuvaislamayona@gmail.com', '$2y$12$Uz5Yksba13mF9L2aRd3HreAueKZoQyh2DsYZmcDRzBCnLmlX2w2L6', '01319403087', '2026-08-15 06:38:26'),
(4, 'Nanto', 'ananto6892@gmail.com', '$2y$12$fznsvQA4uqs55etxnoCZdeyfXJ6g8IGSL1d3iFV7V5UfMzEa2V4cS', '01729962917', '2026-08-15 16:16:30'),
(5, 'PORSHIA KABIR', 'porshiakabir13@gmail.com', '$2y$12$dXNgZRfSd2o8lYrE1bzJ7OcJiJ1iRu9rsZMnWllbt1n7uMwWxegIK', '01603792373', '2026-08-15 16:16:47'),
(6, 'Sam', 'sam123@gmail.com', '$2y$12$f7OzPI8RdVBBKpxgeyNjV.0PqY/8JWu2iuyssVv4eiD7h2eSUAePK', '0123456789', '2026-08-15 17:30:07'),
(7, 'Ahidul Islam Dipu', 'amarbohuttel2@gmail.com', '$2y$12$mHohWwrVj9X1VL8pOfU2oeRmA1EY8QzT3P8EYauEJITIqnSQJpQde', '0123456789', '2026-08-16 09:16:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `expense_id` (`expense_id`);

--
-- Indexes for table `deadlines`
--
ALTER TABLE `deadlines`
  ADD PRIMARY KEY (`deadline_id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses_participants`
--
ALTER TABLE `expenses_participants`
  ADD PRIMARY KEY (`user_id`,`expense_id`),
  ADD KEY `expense_id` (`expense_id`);

--
-- Indexes for table `get`
--
ALTER TABLE `get`
  ADD PRIMARY KEY (`user_id`,`subscription_id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`user_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `is_settled`
--
ALTER TABLE `is_settled`
  ADD PRIMARY KEY (`user_id`,`settlement_id`),
  ADD KEY `settlement_id` (`settlement_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `deadline_id` (`deadline_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`payment_method_id`),
  ADD KEY `expense_id` (`expense_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settlements`
--
ALTER TABLE `settlements`
  ADD PRIMARY KEY (`settlement_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `paid_by` (`paid_by`),
  ADD KEY `paid_to` (`paid_to`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subscription_participants`
--
ALTER TABLE `subscription_participants`
  ADD PRIMARY KEY (`user_id`,`subscription_id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`);

--
-- Constraints for table `deadlines`
--
ALTER TABLE `deadlines`
  ADD CONSTRAINT `deadlines_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`subscription_id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `expenses_participants`
--
ALTER TABLE `expenses_participants`
  ADD CONSTRAINT `expenses_participants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `expenses_participants_ibfk_2` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`);

--
-- Constraints for table `get`
--
ALTER TABLE `get`
  ADD CONSTRAINT `get_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `get_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`subscription_id`);

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`);

--
-- Constraints for table `is_settled`
--
ALTER TABLE `is_settled`
  ADD CONSTRAINT `is_settled_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `is_settled_ibfk_2` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`settlement_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`deadline_id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD CONSTRAINT `payment_method_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`),
  ADD CONSTRAINT `payment_method_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `settlements`
--
ALTER TABLE `settlements`
  ADD CONSTRAINT `settlements_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`),
  ADD CONSTRAINT `settlements_ibfk_2` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `settlements_ibfk_3` FOREIGN KEY (`paid_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`payment_method_id`),
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `subscriptions_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`),
  ADD CONSTRAINT `subscriptions_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `subscription_participants`
--
ALTER TABLE `subscription_participants`
  ADD CONSTRAINT `subscription_participants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `subscription_participants_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`subscription_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

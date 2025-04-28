-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2025 at 12:23 PM
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
-- Database: `bookstore_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `user_id`, `street`, `city`, `state`, `country`, `postal_code`, `is_default`) VALUES
(1, 1, 'Dhaura Tanda', 'Bareilly', 'Uttar Pradesh', 'India', '243202', 0);

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `author_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`author_id`, `name`, `bio`, `photo`) VALUES
(1, 'Musheer', 'Graduate', 'assets/images/author.png');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `isbn` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `publisher_id` int(11) NOT NULL,
  `publication_date` date DEFAULT NULL,
  `pages` int(11) DEFAULT NULL,
  `binding_type` enum('Paperback','Hardcover','E-book') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `isbn`, `title`, `author_id`, `publisher_id`, `publication_date`, `pages`, `binding_type`, `description`, `price`, `discount_price`, `stock_quantity`, `created_at`, `updated_at`) VALUES
(3, 'HSBN00987', 'UPSC History', 1, 1, '2025-04-26', 205, 'Paperback', 'YCT Book for UPSC which is very useful for youth who are preparing for ssc and other competetive exams.', 90.00, 85.00, 7, '2025-04-26 12:36:43', '2025-04-27 17:21:06'),
(4, '9788197170362', 'SSC Samanya Adhayayan', 1, 1, '2024-05-25', 470, 'Paperback', 'Pinnacle 60 days self prepration SSC Samanya Adhyayan 2nd Editon Thoery Book Based on Latest TCS Pattern Chapterwise Hindi Medium Useful for SSC CGL Tier 1, Tier 2, MTS, CHSL, SSC Selection Post, SSC JE and other Government Exams.', 620.00, 350.00, 500, '2025-04-28 07:34:15', '2025-04-28 07:34:31');

-- --------------------------------------------------------

--
-- Table structure for table `book_categories`
--

CREATE TABLE `book_categories` (
  `book_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_categories`
--

INSERT INTO `book_categories` (`book_id`, `category_id`) VALUES
(3, 1),
(4, 1),
(4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `session_id`, `created_at`) VALUES
(1, NULL, 'kptv3cuth1hap7agkpugle4c5v', '2025-04-26 14:45:10'),
(2, 1, NULL, '2025-04-26 15:08:32'),
(3, NULL, 'hpfvl703lc6k7sac355c5aln3n', '2025-04-27 07:06:36');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`item_id`, `cart_id`, `book_id`, `quantity`) VALUES
(2, 1, 3, 6),
(5, 3, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `parent_id`, `description`) VALUES
(1, 'History', NULL, 'History Books'),
(2, 'SSC Books', NULL, 'Books for SSC CGL, CHSL, MTS, Selection Post');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `refund_id` varchar(255) DEFAULT NULL,
  `refund_status` enum('Pending','Processed','Failed') DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_processed_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
  `payment_error_code` varchar(50) DEFAULT NULL,
  `payment_error_description` text DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `shipping_address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `status`, `cancellation_reason`, `cancelled_at`, `refund_id`, `refund_status`, `refund_amount`, `refund_processed_at`, `payment_method`, `razorpay_order_id`, `razorpay_payment_id`, `payment_status`, `payment_error_code`, `payment_error_description`, `payment_date`, `shipping_address`) VALUES
(1, 1, '2025-04-26 15:25:23', 250.60, 'Processing', NULL, NULL, NULL, NULL, NULL, NULL, 'cod', 'order_QO9hA2aIfjuKtO', 'pay_QO9nKEJAIpnVUW', 'Completed', NULL, NULL, '2025-04-27 22:32:11', 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(2, 1, '2025-04-26 15:31:41', 150.30, 'Processing', NULL, NULL, NULL, NULL, NULL, NULL, 'cod', NULL, NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(3, 1, '2025-04-27 07:08:31', 150.30, 'Cancelled', 'Ordered by mistake', '2025-04-27 14:25:43', NULL, NULL, NULL, NULL, 'razorpay', NULL, NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(15, 1, '2025-04-27 17:10:06', 150.30, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'cod', NULL, NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(17, 1, '2025-04-27 17:21:06', 150.30, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', NULL, NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(53, 1, '2025-04-27 18:19:55', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOB7qlXikXgBik', NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(54, 1, '2025-04-27 18:20:22', 150.00, 'Processing', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOB8JFeMbwptXC', 'pay_QOB91HWmvqpwrF', 'Completed', NULL, NULL, '2025-04-27 23:51:24', 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(55, 1, '2025-04-27 18:31:42', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBKIJVsgHnwkB', NULL, 'Pending', NULL, NULL, NULL, ', , , , '),
(56, 1, '2025-04-27 18:32:08', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBKkViXijRSiD', NULL, 'Pending', NULL, NULL, NULL, ', , , , '),
(57, 1, '2025-04-27 18:33:44', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBMS0EH9q6zWF', NULL, 'Pending', NULL, NULL, NULL, ', , , , '),
(58, 1, '2025-04-27 18:34:22', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBN6MrFkw0Yrf', NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(59, 1, '2025-04-27 18:35:01', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBNnSiK1nlzS8', NULL, 'Pending', NULL, NULL, NULL, ', , , , '),
(60, 1, '2025-04-27 18:37:41', 150.00, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBQdEL9jR6Tb0', NULL, 'Pending', NULL, NULL, NULL, 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(61, 1, '2025-04-27 18:47:08', 150.00, 'Processing', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBaadI75yl1Lq', 'pay_QOBhjdCTe03Qxm', 'Completed', NULL, NULL, '2025-04-28 00:24:15', 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202'),
(62, 1, '2025-04-27 18:48:18', 150.00, 'Processing', NULL, NULL, NULL, NULL, NULL, NULL, 'razorpay', 'order_QOBbou3Y4FmrMd', 'pay_QOBcUJH69ltUve', 'Completed', NULL, NULL, '2025-04-28 00:19:17', 'Dhaura Tanda, Bareilly, Uttar Pradesh, India, 243202');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `book_id`, `quantity`, `price`) VALUES
(1, 1, 3, 2, 85.00),
(2, 2, 3, 1, 85.00),
(3, 3, 3, 1, 85.00),
(4, 15, 3, 1, 85.00),
(5, 17, 3, 1, 85.00),
(9, 53, 3, 1, 85.00),
(10, 54, 3, 1, 85.00),
(11, 55, 3, 1, 85.00),
(12, 56, 3, 1, 85.00),
(13, 57, 3, 1, 85.00),
(14, 58, 3, 1, 85.00),
(15, 59, 3, 1, 85.00),
(16, 60, 3, 1, 85.00),
(17, 61, 3, 1, 85.00),
(18, 62, 3, 1, 85.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publishers`
--

CREATE TABLE `publishers` (
  `publisher_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publishers`
--

INSERT INTO `publishers` (`publisher_id`, `name`, `description`, `logo`) VALUES
(1, 'Edutola Publisher', 'Dhaura Tanda Bareilly', 'assets/images/pub.png');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_admin` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `is_admin`, `created_at`, `updated_at`) VALUES
(1, 'Mohd Moin', 'musheer.fready@gmail.com', '$2y$10$ZkXkMvHLuMVaa4jfvwoowO3CpGXpIi6xLcg4TFeVWHet4LtEnd5OW', '9759773601', 0, '2025-04-26 15:07:42', '2025-04-26 15:07:42');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`author_id`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `publisher_id` (`publisher_id`);

--
-- Indexes for table `book_categories`
--
ALTER TABLE `book_categories`
  ADD PRIMARY KEY (`book_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`publisher_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `publisher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`),
  ADD CONSTRAINT `books_ibfk_2` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`publisher_id`);

--
-- Constraints for table `book_categories`
--
ALTER TABLE `book_categories`
  ADD CONSTRAINT `book_categories_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `book_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

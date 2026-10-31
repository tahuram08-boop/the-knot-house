-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2026 at 04:02 PM
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
-- Database: `knot_house`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'The Knot House Admin', 'admin@knothouse.com', 'admin123', '2026-10-04 10:00:59');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `replied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `admin_reply`, `status`, `replied_at`, `created_at`) VALUES
(1, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'Collaboration', 'i want to customize my phone case', NULL, 'unread', NULL, '2026-10-06 09:07:23'),
(2, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'Product Inquiry', 'ftghbyujkm', NULL, 'read', NULL, '2026-10-06 13:51:46'),
(4, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'Product Inquiry', 'SQEFWRGT4WERT5', NULL, 'read', NULL, '2026-10-20 14:30:11');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `password`, `created_at`) VALUES
(7, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', '$2y$10$4PxlFOKIY8PDNK3n.DnyauloMZnXvXKiquGF3COne.p/Sain1tLey', '2026-10-25 15:42:30');

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address_label` varchar(50) NOT NULL DEFAULT 'Home',
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `landmark` varchar(150) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_addresses`
--

INSERT INTO `customer_addresses` (`id`, `customer_id`, `address_label`, `full_name`, `phone`, `address_line`, `landmark`, `city`, `state`, `pincode`, `is_default`, `created_at`) VALUES
(4, 1, 'Work', 'Tahura Mansuri', '1245364152', 'sdjhbcxi9woklsmz,x', '', 'Ahmedabad', 'Gujarat', '345413', 1, '2026-10-13 17:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `customer_favourites`
--

CREATE TABLE `customer_favourites` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favourites`
--

CREATE TABLE `favourites` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash on Delivery',
  `payment_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `order_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `address`, `city`, `state`, `pincode`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', '98,abc flat , dhalgarwad', 'Ahmedabad', 'Gujarat', '380001', 210.00, 'Cash on Delivery', 'Pending', 'Pending', '2026-10-05 15:45:24'),
(2, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', '1388 , near abd', 'Ahmedabad', 'Gujarat', '380001', 310.00, 'Cash on Delivery', 'Pending', 'Pending', '2026-10-05 15:49:05'),
(3, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'ghdzxjjnm', 'Ahmedabad', 'Gujarat', '380001', 560.00, 'Cash on Delivery', 'Pending', 'Pending', '2026-10-16 10:58:58'),
(4, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'erdfgcvrdfgcbvyjuhbgn7uyhjbgm', 'Ahmedabad', 'Gujarat', '380001', 1060.00, 'COD', 'Pending', 'Pending', '2026-10-18 12:35:24'),
(5, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'tgijkcvb lkm,n gvb ikjnm', 'ahmedabad', 'Gujarat', '380001', 560.00, 'COD', 'Pending', 'Pending', '2026-10-18 16:23:56'),
(6, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'MUBARAK SOCIETY', 'ahmedabad', 'Gujarat', '380001', 3206.00, 'COD', 'Pending', 'Pending', '2026-10-20 14:28:29'),
(7, NULL, 'Tahura Mansuri', 'tahuram08@gmail.com', '8849354960', 'hjcxnm wwksxmz,dyuhcxbn maskLm.,', 'ahmedabad', 'Gujarat', '380001', 182.00, 'COD', 'Pending', 'Pending', '2026-10-31 12:31:54');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 19, 'Crocher Rose', 150.00, 1, 150.00),
(2, 2, 18, 'Crochet Keychain', 250.00, 1, 250.00),
(3, 3, 16, 'Crochet Sunflower', 500.00, 1, 500.00),
(4, 4, 17, 'Crochet Gajra', 1000.00, 1, 1000.00),
(5, 5, 16, 'Crochet Sunflower', 500.00, 1, 500.00),
(6, 6, 16, 'Crochet Sunflower', 500.00, 1, 500.00),
(7, 6, 11, 'Crochet Cushion Cover', 599.00, 1, 599.00),
(8, 6, 9, 'Crochet Hair Scrunchie', 149.00, 1, 149.00),
(9, 6, 1, 'Everyday Crochet Tote', 899.00, 1, 899.00),
(10, 6, 6, 'Crochet Summer Top', 999.00, 1, 999.00),
(11, 7, 20, 'Crochet Keychain', 122.00, 1, 122.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category`, `price`, `description`, `image`, `stock`, `status`, `created_at`) VALUES
(1, 'Everyday Crochet Tote', 'Bags', 899.00, 'A beautiful handmade crochet tote bag perfect for everyday use.', '../images/crochet-tote.jpg', 11, 'Active', '2026-10-04 10:07:53'),
(2, 'Forever Flower Bouquet', 'Flowers', 749.00, 'A beautiful handmade crochet flower bouquet that lasts forever.', '../images/crochet-flower-bouquet.jpg', 8, 'Active', '2026-10-04 10:07:53'),
(3, 'Little Crochet Teddy', 'Toys', 499.00, 'A cute and soft handmade crochet teddy bear.', '../images/crochet-teddy.jpg', 15, 'Active', '2026-10-04 10:07:53'),
(5, 'Crochet Mini Shoulder Bag', 'Bags', 699.00, 'A stylish handmade crochet mini shoulder bag.', '../images/crochet-mini-bag.jpg', 10, 'Active', '2026-10-04 10:07:53'),
(6, 'Crochet Summer Top', 'Clothes', 999.00, 'A beautiful handmade crochet summer top.', '../images/crochet-summer-top.jpg', 6, 'Active', '2026-10-04 10:07:53'),
(8, 'Crochet Tulip Stem', 'Flowers', 249.00, 'A handmade crochet tulip stem for beautiful home decoration.', '../images/crochet-tulip.jpg', 18, 'Active', '2026-10-04 10:07:53'),
(9, 'Crochet Hair Scrunchie', 'Accessories', 149.00, 'A cute handmade crochet hair scrunchie.', '../images/crochet-scrunchie.jpg', 24, 'Active', '2026-10-04 10:07:53'),
(10, 'Crochet Flower Pot Cover', 'Decor', 399.00, 'A beautiful handmade crochet cover for your flower pots.', '../images/crochet-pot-cover.jpg', 6, 'Active', '2026-10-04 10:07:53'),
(11, 'Crochet Cushion Cover', 'Decor', 599.00, 'A beautiful handmade crochet cushion cover.', '../images/crochet-cushion.jpg', 10, 'Active', '2026-10-04 10:07:53'),
(12, 'Crochet Bucket Bag', 'Bags', 849.00, 'A stylish handmade crochet bucket bag.', '../images/crochet-bucket-bag.jpg', 8, 'Active', '2026-10-04 10:07:53'),
(16, 'Crochet Sunflower', 'Flowers', 500.00, '', '../images/crochet-sunflower-1791136704-6ac293c01b9cb.jpg', 7, 'Active', '2026-10-04 17:58:24'),
(17, 'Crochet Gajra', 'Accessories', 1000.00, '', '../images/crochet-gajra-1791136742-6ac293e6036f0.jpg', 9, 'Active', '2026-10-04 17:59:02'),
(19, 'Crocher Rose', 'Flowers', 150.00, '', '../images/crocher-rose-1791137232-6ac295d0d924e.jpg', 9, 'Active', '2026-10-04 18:07:12'),
(20, 'Crochet Keychain', 'Accessories', 122.00, '', '../images/crochet-keychain-1791275913-6ac4b389c7395.jpg', 9, 'Active', '2026-10-06 08:38:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`);

--
-- Indexes for table `customer_favourites`
--
ALTER TABLE `customer_favourites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favourite` (`customer_id`,`product_id`);

--
-- Indexes for table `favourites`
--
ALTER TABLE `favourites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favourite` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_favourites`
--
ALTER TABLE `customer_favourites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `favourites`
--
ALTER TABLE `favourites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favourites`
--
ALTER TABLE `favourites`
  ADD CONSTRAINT `favourites_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

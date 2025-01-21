-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2024 at 03:25 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crypto_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_info` varchar(255) NOT NULL,
  `role` enum('admin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `contact_info`, `role`, `created_at`) VALUES
(1, 'Rick Sanchez', 'rick_sanchez@gmail.com', '$2y$10$Gk4l4yOcy2XXWPayOOKhG.zMXcPy51j0.HCD8i1Uday68q1EyiDQK', '1234', 'admin', '2024-11-30 11:47:05');

-- --------------------------------------------------------

--
-- Table structure for table `cryptos`
--

CREATE TABLE `cryptos` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `available_supply` decimal(20,8) DEFAULT NULL,
  `current_marketprice` decimal(18,8) NOT NULL,
  `highest_marketprice` decimal(20,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cryptos`
--

INSERT INTO `cryptos` (`id`, `symbol`, `name`, `available_supply`, `current_marketprice`, `highest_marketprice`, `created_at`, `updated_at`) VALUES
(1, 'BTC', 'Bitcoin', '110.00000000', '35000.00000000', '69000.00000000', '2024-11-29 16:42:14', '2024-12-06 14:50:32'),
(2, 'ETH', 'Ethereum', '499992.00000000', '1800.00000000', '4800.00000000', '2024-11-29 16:43:12', '2024-12-05 15:49:42'),
(3, 'XRP', 'Ripple', '24999945.00000000', '0.50000000', '3.84000000', '2024-11-29 16:43:49', '2024-12-06 15:25:39'),
(4, 'ADA', 'Cardano', '9999999.00000000', '0.30000000', '3.00000000', '2024-11-29 16:45:33', '2024-12-08 14:06:01'),
(5, 'SOL', 'Solana', '499990.00000000', '20.00000000', '260.00000000', '2024-11-29 16:46:29', '2024-12-06 15:25:54'),
(6, 'DOGE', 'Dogecoin', '9994000.00000000', '0.07500000', '0.75000000', '2024-11-29 16:47:09', '2024-12-06 15:27:04'),
(7, 'MATIC', 'Polygon', '500000000.00000000', '0.80000000', '2.90000000', '2024-11-29 16:49:10', '2024-11-30 07:05:17'),
(8, 'BNB', 'Binance Coin', '999770.99999965', '220.00000000', '690.00000000', '2024-11-29 16:49:37', '2024-12-08 13:46:22'),
(9, 'LTC', 'Litecoin', '999994.00000000', '95.00000000', '375.00000000', '2024-11-29 16:50:04', '2024-12-06 15:42:13'),
(10, 'DOT', 'Polkadot', '49999989.99999999', '6.50000000', '55.00000000', '2024-11-29 16:50:33', '2024-12-08 13:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `crypto_wallets`
--

CREATE TABLE `crypto_wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_type` enum('MetaMask','Ledger Nano X','Trezor Model T','Exodus','Trust Wallet','Coinbase Wallet','Electrum','Mycelium','Samourai Wallet','BitPay Wallet') NOT NULL,
  `wallet_address` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `crypto_wallets`
--

INSERT INTO `crypto_wallets` (`id`, `user_id`, `wallet_type`, `wallet_address`, `is_verified`, `verification_status`, `verified_at`, `verified_by_admin_id`, `created_at`) VALUES
(9, 4, 'MetaMask', '0xD0aF4732B9C6236b6889F1Ff5b0F2730e3F3F4A7', 1, 'verified', '2024-12-06 14:31:31', 1, '2024-12-06 14:29:31'),
(10, 5, 'Samourai Wallet', '0xD0aF4732B9C6236b6889F1Ff5b0F2730e3F3F4A1', 1, 'verified', '2024-12-06 15:24:43', 1, '2024-12-06 15:24:14');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method_type` enum('gcash','paymaya','credit_card') NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `balance` decimal(20,8) DEFAULT 0.00000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by_admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `user_id`, `method_type`, `account_name`, `account_number`, `is_verified`, `balance`, `created_at`, `updated_at`, `verification_status`, `verified_at`, `verified_by_admin_id`) VALUES
(13, 4, 'gcash', 'Morty Sanchez', '11111111111', 1, '33612.00000000', '2024-12-06 14:29:42', '2024-12-08 14:06:01', 'verified', '2024-12-06 14:34:19', 1),
(14, 4, 'paymaya', 'Morty Sanchez', '03154125341', 1, '37568.00000000', '2024-12-06 14:34:16', '2024-12-06 14:59:40', 'verified', '2024-12-06 14:34:21', 1),
(17, 5, 'gcash', 'John Doe', '13154125341', 1, '17960.00000000', '2024-12-06 15:23:47', '2024-12-06 15:58:32', 'verified', '2024-12-06 15:24:41', 1),
(18, 5, 'paymaya', 'John Doe', '11111111113', 1, '20000.00000000', '2024-12-06 15:27:53', '2024-12-06 15:28:23', 'verified', '2024-12-06 15:28:07', 1);

-- --------------------------------------------------------

--
-- Table structure for table `trade_transactions`
--

CREATE TABLE `trade_transactions` (
  `id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `crypto_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `trade_type` enum('buy','sell') NOT NULL,
  `amount` decimal(20,8) NOT NULL,
  `crypto_price` decimal(18,8) NOT NULL,
  `total_cost_usd` decimal(18,8) NOT NULL,
  `total_cost_php` decimal(18,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `trade_transactions`
--

INSERT INTO `trade_transactions` (`id`, `user_id`, `crypto_id`, `wallet_id`, `payment_method_id`, `trade_type`, `amount`, `crypto_price`, `total_cost_usd`, `total_cost_php`, `created_at`) VALUES
('035c3b46afc407e241113e6cc86148b1', 5, 6, 10, 17, 'buy', '6000.00000000', '0.07500000', '450.00000000', '25200.00000000', '2024-12-06 15:27:04'),
('249ec18aa1cd4d55c665432c966b29ee', 4, 8, 9, 13, 'buy', '1.00000000', '220.00000000', '220.00000000', '12320.00000000', '2024-12-08 12:57:19'),
('2f07257d5ba1493573251ef4affd865b', 4, 4, 9, 13, 'buy', '10.00000000', '0.30000000', '3.00000000', '168.00000000', '2024-12-08 13:08:42'),
('34fa8afb87d825398a9cce6bb67f14ba', 5, 5, 10, 17, 'buy', '10.00000000', '20.00000000', '200.00000000', '11200.00000000', '2024-12-06 15:25:54'),
('57be4d36cd325746a22b9ea607ee2d68', 4, 10, 9, 13, 'buy', '10.00000000', '6.50000000', '65.00000000', '3640.00000000', '2024-12-08 13:45:33'),
('757a858f99575842ac147eefdf6ad754', 4, 4, 9, 13, 'sell', '11.00000000', '0.30000000', '3.30000000', '184.80000000', '2024-12-08 14:06:01'),
('8d4e7418916518f869a23f2d730b229d', 5, 9, 10, 17, 'buy', '1.00000000', '95.00000000', '95.00000000', '5320.00000000', '2024-12-06 15:42:13'),
('8e5a2d5a815eaf197d04928bc76435a5', 5, 9, 10, 17, 'buy', '5.00000000', '95.00000000', '475.00000000', '26600.00000000', '2024-12-06 15:26:09'),
('a6502ba826a99decc3084b58809ee534', 4, 4, 9, 13, 'buy', '1.00000000', '0.30000000', '0.30000000', '16.80000000', '2024-12-08 13:08:25'),
('acfd80d7edf1954945b3e5d75b5618cc', 5, 3, 10, 17, 'buy', '50.00000000', '0.50000000', '25.00000000', '1400.00000000', '2024-12-06 15:25:39'),
('adee0ba8b5750464c730f5a1ba98338f', 4, 10, 9, 13, 'sell', '200.00000000', '6.50000000', '1300.00000000', '72800.00000000', '2024-12-06 15:13:25'),
('b86ebb1e5dd1e14c9eb7ff566514f557', 5, 8, 10, 17, 'buy', '1.00000000', '220.00000000', '220.00000000', '12320.00000000', '2024-12-06 15:58:32'),
('bac27716f10c30ab832d097dddd97891', 4, 8, 9, 13, 'buy', '1.00000000', '220.00000000', '220.00000000', '12320.00000000', '2024-12-08 12:57:30'),
('fc195b575618401ff495b34b75d2c636', 4, 8, 9, 13, 'buy', '1.00000000', '220.00000000', '220.00000000', '12320.00000000', '2024-12-08 13:46:22');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_feedbacks`
--

CREATE TABLE `transaction_feedbacks` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `transaction_feedbacks`
--

INSERT INTO `transaction_feedbacks` (`id`, `transaction_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, '757a858f99575842ac147eefdf6ad754', 4, 3, 'asdasdasdasd', '2024-12-11 11:51:54'),
(2, 'b86ebb1e5dd1e14c9eb7ff566514f557', 5, 4, 'aaaa', '2024-12-11 13:49:55'),
(3, 'b86ebb1e5dd1e14c9eb7ff566514f557', 5, 5, 'asdasd', '2024-12-12 15:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `role` enum('user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `contact_info`, `role`, `created_at`) VALUES
(4, 'Morty Sanchez', 'Sanchez_Morty@gmail.com', '$2y$10$gBnKuZreTnMvyFi0hdkZ2e4J6n4Wv6pRfIs0q6/DFeE5pXHQ4Ey1G', '09448451356', 'user', '2024-12-06 14:27:18'),
(5, 'John Doe', 'John_doe@gmail.com', '$2y$10$Eox3Oncdz9MY3xkpM9Mg/OMsZ9QG/fEs8uEZyKfqbOGtq9QSVknte', '1234', 'user', '2024-12-06 15:23:16'),
(6, 'Jane Doe', 'Doe_jane@gmail.com', '$2y$10$A1TGVM1ga4L.wsANO91NkuqmLYpZvJX7apgkTmRSiOqeJdOzgpoE.', '12345', 'user', '2024-12-12 15:22:00'),
(7, 'Christian Ramos', 'tambopogiawtsu@gmail.com', '$2y$10$u1bgw0kaoGF4IPVh0eLHrOhrP3Hii0xtGIm8YS4z9PAl5oGZM1/Sm', '1234', 'user', '2024-12-12 15:50:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_crypto_holdings`
--

CREATE TABLE `user_crypto_holdings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `crypto_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `quantity` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_crypto_holdings`
--

INSERT INTO `user_crypto_holdings` (`id`, `user_id`, `crypto_id`, `wallet_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 4, 10, 9, '10.00000000', '2024-12-06 14:37:13', '2024-12-08 13:45:33'),
(3, 4, 3, 9, '5.00000000', '2024-12-06 14:39:02', '2024-12-06 14:58:35'),
(7, 4, 8, 9, '6.00000000', '2024-12-06 14:47:57', '2024-12-08 13:46:22'),
(9, 4, 5, 9, '1.00000000', '2024-12-06 14:51:30', '2024-12-06 14:51:30'),
(12, 5, 3, 10, '50.00000000', '2024-12-06 15:25:39', '2024-12-06 15:25:39'),
(13, 5, 5, 10, '10.00000000', '2024-12-06 15:25:54', '2024-12-06 15:25:54'),
(14, 5, 9, 10, '6.00000000', '2024-12-06 15:26:09', '2024-12-06 15:42:13'),
(15, 5, 6, 10, '6000.00000000', '2024-12-06 15:27:04', '2024-12-06 15:27:04'),
(17, 5, 8, 10, '1.00000000', '2024-12-06 15:58:32', '2024-12-06 15:58:32'),
(20, 4, 4, 9, '0.00000000', '2024-12-08 13:08:25', '2024-12-08 14:06:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cryptos`
--
ALTER TABLE `cryptos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wallet_address` (`wallet_address`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verified_by_admin_id` (`verified_by_admin_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_method` (`user_id`,`method_type`,`account_number`),
  ADD KEY `verified_by_admin_id` (`verified_by_admin_id`);

--
-- Indexes for table `trade_transactions`
--
ALTER TABLE `trade_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `crypto_id` (`crypto_id`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `payment_method_id` (`payment_method_id`);

--
-- Indexes for table `transaction_feedbacks`
--
ALTER TABLE `transaction_feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_crypto_holdings`
--
ALTER TABLE `user_crypto_holdings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_crypto` (`user_id`,`crypto_id`,`wallet_id`),
  ADD KEY `crypto_id` (`crypto_id`),
  ADD KEY `wallet_id` (`wallet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cryptos`
--
ALTER TABLE `cryptos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transaction_feedbacks`
--
ALTER TABLE `transaction_feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_crypto_holdings`
--
ALTER TABLE `user_crypto_holdings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  ADD CONSTRAINT `crypto_wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `crypto_wallets_ibfk_2` FOREIGN KEY (`verified_by_admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payment_methods_ibfk_2` FOREIGN KEY (`verified_by_admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `trade_transactions`
--
ALTER TABLE `trade_transactions`
  ADD CONSTRAINT `trade_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `trade_transactions_ibfk_2` FOREIGN KEY (`crypto_id`) REFERENCES `cryptos` (`id`),
  ADD CONSTRAINT `trade_transactions_ibfk_3` FOREIGN KEY (`wallet_id`) REFERENCES `crypto_wallets` (`id`),
  ADD CONSTRAINT `trade_transactions_ibfk_4` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);

--
-- Constraints for table `transaction_feedbacks`
--
ALTER TABLE `transaction_feedbacks`
  ADD CONSTRAINT `transaction_feedbacks_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `trade_transactions` (`id`),
  ADD CONSTRAINT `transaction_feedbacks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_crypto_holdings`
--
ALTER TABLE `user_crypto_holdings`
  ADD CONSTRAINT `user_crypto_holdings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_crypto_holdings_ibfk_2` FOREIGN KEY (`crypto_id`) REFERENCES `cryptos` (`id`),
  ADD CONSTRAINT `user_crypto_holdings_ibfk_3` FOREIGN KEY (`wallet_id`) REFERENCES `crypto_wallets` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

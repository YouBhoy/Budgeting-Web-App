-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2025 at 02:36 PM
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
-- Database: `budgeting_app`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddFamilyMember` (IN `p_user_id` INT, IN `p_name` VARCHAR(100))   BEGIN
  INSERT INTO family_members (user_id, name) VALUES (p_user_id, p_name);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddRecurringTransaction` (IN `p_user_id` INT, IN `p_type` ENUM('income','expense'), IN `p_amount` DECIMAL(10,2), IN `p_description` VARCHAR(255), IN `p_category` VARCHAR(100), IN `p_recurrence` ENUM('daily','weekly','monthly','yearly'), IN `p_next_date` DATE, IN `p_end_date` DATE)   BEGIN
  INSERT INTO recurring_transactions (user_id, type, amount, description, category, recurrence, next_date, end_date)
  VALUES (p_user_id, p_type, p_amount, p_description, p_category, p_recurrence, p_next_date, p_end_date);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddSavingsGoal` (IN `p_user_id` INT, IN `p_name` VARCHAR(100), IN `p_target_amount` DECIMAL(10,2))   BEGIN
  INSERT INTO savings_goals (user_id, name, target_amount) VALUES (p_user_id, p_name, p_target_amount);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddTransaction` (IN `p_user_id` INT, IN `p_type` ENUM('income','expense'), IN `p_amount` DECIMAL(10,2), IN `p_description` VARCHAR(255), IN `p_category` VARCHAR(100))   BEGIN
  INSERT INTO transactions (user_id, type, amount, description, category)
  VALUES (p_user_id, p_type, p_amount, p_description, p_category);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteFamilyMember` (IN `p_member_id` INT, IN `p_user_id` INT)   BEGIN
  DELETE FROM family_members WHERE id = p_member_id AND user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteRecurringTransaction` (IN `p_id` INT, IN `p_user_id` INT)   BEGIN
  DELETE FROM recurring_transactions WHERE id = p_id AND user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteSavingsGoal` (IN `p_id` INT, IN `p_user_id` INT)   BEGIN
  DELETE FROM savings_goals WHERE id = p_id AND user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetMonthlySummary` (IN `p_user_id` INT, IN `p_month` INT, IN `p_year` INT)   BEGIN
  SELECT type, category, SUM(amount) AS total
  FROM transactions
  WHERE user_id = p_user_id AND MONTH(created_at) = p_month AND YEAR(created_at) = p_year
  GROUP BY type, category;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserTotals` (IN `p_user_id` INT)   BEGIN
  SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'income') AS total_income,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'expense') AS total_expense;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ListFamilyMembers` (IN `p_user_id` INT)   BEGIN
  SELECT id, name, created_at FROM family_members WHERE user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ListRecurringTransactions` (IN `p_user_id` INT)   BEGIN
  SELECT id, type, amount, description, category, recurrence, next_date, end_date, created_at
  FROM recurring_transactions
  WHERE user_id = p_user_id
  ORDER BY next_date ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ListSavingsGoals` (IN `p_user_id` INT)   BEGIN
  SELECT id, name, target_amount, saved_amount, created_at FROM savings_goals WHERE user_id = p_user_id ORDER BY created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateSavingsGoal` (IN `p_id` INT, IN `p_user_id` INT, IN `p_saved_amount` DECIMAL(10,2))   BEGIN
  UPDATE savings_goals SET saved_amount = p_saved_amount WHERE id = p_id AND user_id = p_user_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recurring_transactions`
--

CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `recurrence` enum('daily','weekly','monthly','yearly') NOT NULL,
  `next_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_goals`
--

CREATE TABLE `savings_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `saved_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `member_id`, `type`, `amount`, `description`, `category`, `created_at`) VALUES
(2, 1, NULL, 'income', 100.00, '', 'JIB', '2025-07-24 10:05:03'),
(4, 1, NULL, 'expense', 123.00, '', 'POOP', '2025-07-25 12:22:51'),
(5, 1, NULL, 'expense', 32.00, '', 'POOP', '2025-07-25 12:23:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` enum('individual','family') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `account_type`, `created_at`) VALUES
(1, 'Test Account', 'test@gmail.com', '$2y$10$p6AjsgzvSlTQygZmWg6zzOGdN/SM0MWgE2t8Pxr4CygzSh4fQCQOq', 'family', '2025-07-23 12:53:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `family_members`
--
ALTER TABLE `family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `member_id` (`member_id`);

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
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_goals`
--
ALTER TABLE `savings_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `family_members`
--
ALTER TABLE `family_members`
  ADD CONSTRAINT `family_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  ADD CONSTRAINT `recurring_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings_goals`
--
ALTER TABLE `savings_goals`
  ADD CONSTRAINT `savings_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `family_members` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

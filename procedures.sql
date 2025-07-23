-- Budgeting Web App: Database Schema & Stored Procedures
-- Run this file in phpMyAdmin or MySQL CLI to set up the database

-- Use your database name here
-- CREATE DATABASE IF NOT EXISTS budgeting_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE budgeting_app;

-- -----------------------------
-- Table: users
-- -----------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  account_type ENUM('individual', 'family') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------
-- Table: family_members
-- -----------------------------
CREATE TABLE IF NOT EXISTS family_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL, -- The account owner
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------
-- Updated Table: transactions (add member_id)
-- -----------------------------
DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  member_id INT DEFAULT NULL, -- Nullable, for family mode
  type ENUM('income', 'expense') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  category VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------
-- Stored Procedure: AddTransaction
-- -----------------------------
DROP PROCEDURE IF EXISTS AddTransaction;
DELIMITER //
CREATE PROCEDURE AddTransaction(
  IN p_user_id INT,
  IN p_type ENUM('income','expense'),
  IN p_amount DECIMAL(10,2),
  IN p_description VARCHAR(255),
  IN p_category VARCHAR(100)
)
BEGIN
  INSERT INTO transactions (user_id, type, amount, description, category)
  VALUES (p_user_id, p_type, p_amount, p_description, p_category);
END //
DELIMITER ;

-- -----------------------------
-- Stored Procedure: GetUserTotals
-- -----------------------------
DROP PROCEDURE IF EXISTS GetUserTotals;
DELIMITER //
CREATE PROCEDURE GetUserTotals(
  IN p_user_id INT
)
BEGIN
  SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'income') AS total_income,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND type = 'expense') AS total_expense;
END //
DELIMITER ;

-- -----------------------------
-- Stored Procedure: GetMonthlySummary
-- -----------------------------
DROP PROCEDURE IF EXISTS GetMonthlySummary;
DELIMITER //
CREATE PROCEDURE GetMonthlySummary(
  IN p_user_id INT,
  IN p_month INT,
  IN p_year INT
)
BEGIN
  SELECT type, category, SUM(amount) AS total
  FROM transactions
  WHERE user_id = p_user_id AND MONTH(created_at) = p_month AND YEAR(created_at) = p_year
  GROUP BY type, category;
END //
DELIMITER ; 

-- -----------------------------
-- Stored Procedure: AddFamilyMember
-- -----------------------------
DROP PROCEDURE IF EXISTS AddFamilyMember;
DELIMITER //
CREATE PROCEDURE AddFamilyMember(
  IN p_user_id INT,
  IN p_name VARCHAR(100)
)
BEGIN
  INSERT INTO family_members (user_id, name) VALUES (p_user_id, p_name);
END //
DELIMITER ;

-- -----------------------------
-- Stored Procedure: ListFamilyMembers
-- -----------------------------
DROP PROCEDURE IF EXISTS ListFamilyMembers;
DELIMITER //
CREATE PROCEDURE ListFamilyMembers(
  IN p_user_id INT
)
BEGIN
  SELECT id, name, created_at FROM family_members WHERE user_id = p_user_id;
END //
DELIMITER ;

-- -----------------------------
-- Stored Procedure: DeleteFamilyMember
-- -----------------------------
DROP PROCEDURE IF EXISTS DeleteFamilyMember;
DELIMITER //
CREATE PROCEDURE DeleteFamilyMember(
  IN p_member_id INT,
  IN p_user_id INT
)
BEGIN
  DELETE FROM family_members WHERE id = p_member_id AND user_id = p_user_id;
END //
DELIMITER ; 

-- -----------------------------
-- Table: recurring_transactions
-- -----------------------------
CREATE TABLE IF NOT EXISTS recurring_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('income', 'expense') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  category VARCHAR(100),
  recurrence ENUM('daily','weekly','monthly','yearly') NOT NULL,
  next_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------
-- Table: savings_goals
-- -----------------------------
CREATE TABLE IF NOT EXISTS savings_goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  target_amount DECIMAL(10,2) NOT NULL,
  saved_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

-- -----------------------------
-- Stored Procedures: Recurring Transactions
-- -----------------------------
DROP PROCEDURE IF EXISTS AddRecurringTransaction;
DELIMITER //
CREATE PROCEDURE AddRecurringTransaction(
  IN p_user_id INT,
  IN p_type ENUM('income','expense'),
  IN p_amount DECIMAL(10,2),
  IN p_description VARCHAR(255),
  IN p_category VARCHAR(100),
  IN p_recurrence ENUM('daily','weekly','monthly','yearly'),
  IN p_next_date DATE,
  IN p_end_date DATE
)
BEGIN
  INSERT INTO recurring_transactions (user_id, type, amount, description, category, recurrence, next_date, end_date)
  VALUES (p_user_id, p_type, p_amount, p_description, p_category, p_recurrence, p_next_date, p_end_date);
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS ListRecurringTransactions;
DELIMITER //
CREATE PROCEDURE ListRecurringTransactions(
  IN p_user_id INT
)
BEGIN
  SELECT id, type, amount, description, category, recurrence, next_date, end_date, created_at
  FROM recurring_transactions
  WHERE user_id = p_user_id
  ORDER BY next_date ASC;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS DeleteRecurringTransaction;
DELIMITER //
CREATE PROCEDURE DeleteRecurringTransaction(
  IN p_id INT,
  IN p_user_id INT
)
BEGIN
  DELETE FROM recurring_transactions WHERE id = p_id AND user_id = p_user_id;
END //
DELIMITER ;

-- -----------------------------
-- Stored Procedures: Savings Goals
-- -----------------------------
DROP PROCEDURE IF EXISTS AddSavingsGoal;
DELIMITER //
CREATE PROCEDURE AddSavingsGoal(
  IN p_user_id INT,
  IN p_name VARCHAR(100),
  IN p_target_amount DECIMAL(10,2)
)
BEGIN
  INSERT INTO savings_goals (user_id, name, target_amount) VALUES (p_user_id, p_name, p_target_amount);
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS ListSavingsGoals;
DELIMITER //
CREATE PROCEDURE ListSavingsGoals(
  IN p_user_id INT
)
BEGIN
  SELECT id, name, target_amount, saved_amount, created_at FROM savings_goals WHERE user_id = p_user_id ORDER BY created_at DESC;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS UpdateSavingsGoal;
DELIMITER //
CREATE PROCEDURE UpdateSavingsGoal(
  IN p_id INT,
  IN p_user_id INT,
  IN p_saved_amount DECIMAL(10,2)
)
BEGIN
  UPDATE savings_goals SET saved_amount = p_saved_amount WHERE id = p_id AND user_id = p_user_id;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS DeleteSavingsGoal;
DELIMITER //
CREATE PROCEDURE DeleteSavingsGoal(
  IN p_id INT,
  IN p_user_id INT
)
BEGIN
  DELETE FROM savings_goals WHERE id = p_id AND user_id = p_user_id;
END //
DELIMITER ; 
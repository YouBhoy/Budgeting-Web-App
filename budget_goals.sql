-- Budget Goals Table
CREATE TABLE IF NOT EXISTS `budget_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `deadline` date NULL,
  `category` varchar(100) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `deadline` (`deadline`),
  KEY `category` (`category`),
  CONSTRAINT `fk_budget_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recurring Transactions Table
CREATE TABLE IF NOT EXISTS `recurring_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
  `next_due` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `next_due` (`next_due`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stored Procedure for Budget Goals Summary
DELIMITER //
CREATE PROCEDURE GetBudgetGoalsSummary(IN user_id_param INT)
BEGIN
    SELECT 
        COUNT(*) as total_goals,
        COUNT(CASE WHEN current_amount >= target_amount THEN 1 END) as completed_goals,
        SUM(target_amount) as total_target,
        SUM(current_amount) as total_current,
        AVG(CASE WHEN current_amount < target_amount THEN (current_amount / target_amount) * 100 END) as avg_progress
    FROM budget_goals 
    WHERE user_id = user_id_param;
END //
DELIMITER ;

-- Stored Procedure for Recurring Transactions Due Soon
DELIMITER //
CREATE PROCEDURE GetRecurringDueSoon(IN user_id_param INT, IN days_ahead INT)
BEGIN
    SELECT 
        id,
        type,
        amount,
        description,
        category,
        frequency,
        next_due,
        DATEDIFF(next_due, CURDATE()) as days_until_due
    FROM recurring_transactions 
    WHERE user_id = user_id_param 
    AND is_active = 1 
    AND next_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL days_ahead DAY)
    ORDER BY next_due ASC;
END //
DELIMITER ;

-- Indexes for better performance
CREATE INDEX idx_budget_goals_user_deadline ON budget_goals(user_id, deadline);
CREATE INDEX idx_recurring_user_next_due ON recurring_transactions(user_id, next_due);
CREATE INDEX idx_recurring_active ON recurring_transactions(is_active, next_due);

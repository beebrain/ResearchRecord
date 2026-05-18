-- Fix missing timestamp columns in faculty table
-- Run this SQL in phpMyAdmin or MySQL client

-- Check if columns exist first, then add if missing
ALTER TABLE `faculty`
ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Optional: Check the structure after update
-- DESCRIBE `faculty`;

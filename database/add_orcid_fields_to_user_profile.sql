-- Add ORCID fields to user_profile table
-- Run this migration to add ORCID sync functionality

-- Add orcid_id field (just the ID without URL)
ALTER TABLE `user_profile` 
ADD COLUMN `orcid_id` VARCHAR(19) NULL DEFAULT NULL COMMENT 'ORCID iD in format 0000-0001-2345-6789' AFTER `orcid`;

-- Add orcid_data field (stores raw JSON response from ORCID API)
ALTER TABLE `user_profile` 
ADD COLUMN `orcid_data` TEXT NULL DEFAULT NULL COMMENT 'JSON data from ORCID API sync' AFTER `orcid_id`;

-- Add orcid_synced_at field (timestamp of last sync)
ALTER TABLE `user_profile` 
ADD COLUMN `orcid_synced_at` DATETIME NULL DEFAULT NULL COMMENT 'Timestamp of last ORCID sync' AFTER `orcid_data`;

-- Add index for faster lookups
ALTER TABLE `user_profile` 
ADD INDEX `idx_orcid_id` (`orcid_id`);

-- Check columns
SHOW COLUMNS FROM `user_profile` LIKE 'orcid%';

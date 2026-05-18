-- Add missing ORCID columns to user_profile table
-- Run this in phpMyAdmin or MySQL CLI

ALTER TABLE user_profile 
ADD COLUMN IF NOT EXISTS orcid_id VARCHAR(20) NULL COMMENT 'ORCID iD (e.g., 0000-0001-2345-6789)' AFTER orcid,
ADD COLUMN IF NOT EXISTS orcid_data LONGTEXT NULL COMMENT 'Cached ORCID API response data' AFTER orcid_id,
ADD COLUMN IF NOT EXISTS orcid_synced_at DATETIME NULL COMMENT 'Last ORCID sync timestamp' AFTER orcid_data;

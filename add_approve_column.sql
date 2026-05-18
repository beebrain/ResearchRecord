-- Migration: Add approve column to publications table
-- Date: 2025-11-20
-- Purpose: Track approval status of publications

USE researchrecord;

-- Add approve column to publications table
ALTER TABLE publications
  ADD COLUMN `approve` TINYINT(1) DEFAULT 0 COMMENT '0 = Not approved, 1 = Approved' AFTER `notes`;

-- Show updated table structure
DESCRIBE publications;


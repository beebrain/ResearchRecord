-- Migration: Add missing fields to publications table for complete publication type support
-- Date: 2025-11-08
-- Purpose: Support journal, book, conference, thesis, report, and other publication types

USE researchrecord;

-- Add fields to publications table
ALTER TABLE publications
  -- Add issue for journals
  ADD COLUMN `issue` VARCHAR(50) NULL AFTER `volume`,

  -- Add publisher
  ADD COLUMN `publisher` VARCHAR(500) NULL AFTER `source`,

  -- Add conference fields
  ADD COLUMN `conference_name` VARCHAR(500) NULL AFTER `publisher`,
  ADD COLUMN `conference_location` VARCHAR(500) NULL AFTER `conference_name`,
  ADD COLUMN `conference_date` DATE NULL AFTER `conference_location`,

  -- Add book fields
  ADD COLUMN `book_title` VARCHAR(500) NULL AFTER `conference_date`,
  ADD COLUMN `chapter` VARCHAR(100) NULL AFTER `book_title`,
  ADD COLUMN `editor` TEXT NULL AFTER `chapter`,

  -- Add URL field (separate from referencelink)
  ADD COLUMN `url` TEXT NULL AFTER `referencelink`;

-- Show updated table structure
DESCRIBE publications;

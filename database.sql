-- database.sql
-- Kenil Tech Business - Freight & Logistics Management System
-- Production-ready MySQL Database Schema with Relationships, Cascade Deletes, and Indexing

-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS kenil_tech_logistics;
USE kenil_tech_logistics;

-- 1. Users Table (Super Admin & Staff)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `address` TEXT NULL,
  `gst_no` VARCHAR(15) NULL,
  `iec_no` VARCHAR(10) NULL,
  `pan_no` VARCHAR(10) NULL,
  `ad_code` VARCHAR(20) NULL,
  `ifsc_code` VARCHAR(11) NULL,
  `factory_addresses` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexing for Customer Searching
CREATE INDEX idx_customer_name ON `customers` (`name`);

-- 3. Jobs Table
-- Auto-generated Job ID format: KT/IMP/25-26/000001
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_num` VARCHAR(30) NOT NULL UNIQUE,
  `transport_mode` ENUM('Sea', 'Air') NOT NULL,
  `customer_id` INT NOT NULL,
  `port_loading` VARCHAR(100) NOT NULL,
  `port_dest` VARCHAR(100) NOT NULL,
  `port_discharge` VARCHAR(100) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Under Assessment', -- Customs status: Under Assessment, Cleared, etc.
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0, -- Active jobs remain in dashboard until is_completed = 1
  `approval_status` ENUM('live', 'pending_approval') NOT NULL DEFAULT 'pending_approval',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexing for Dashboard Speed & Filters
CREATE INDEX idx_job_status ON `jobs` (`status`, `is_completed`);
CREATE INDEX idx_job_approval ON `jobs` (`approval_status`);

-- 4. Job Containers Table (Dynamic Containers list for Sea shipment or Air AWB packets)
CREATE TABLE IF NOT EXISTS `job_containers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `booking_no` VARCHAR(50) NULL,
  `bl_no` VARCHAR(50) NULL,
  `container_no` VARCHAR(20) NULL,
  `seal_no` VARCHAR(30) NULL,
  `package` VARCHAR(100) NULL,
  `gross_wt` DECIMAL(12, 3) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Audit Logs Table (Activity Tracking)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action_description` TEXT NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PRE-LOAD INITIAL DEFAULT DATA --
-- Insert Default Master Users using PHP-compatible password hashes (password_verify with bcrypt matches this)
-- Default admin password: 'admin123' (hash: $2y$10$WWhp7O9vHq0699s59K2pWuxshGNoYI.qT.fTymv88jK0e/hU2t9Xq)
-- Default staff password: 'staff123' (hash: $2y$10$tZptE/WfI3R6S2L7t4N8EOn2XW2E48/qJEqiFbyVym1QREbLbe4U6)
INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES
('super_admin', '$2y$10$WWhp7O9vHq0699s59K2pWuxshGNoYI.qT.fTymv88jK0e/hU2t9Xq', 'super_admin'),
('staff_user', '$2y$10$tZptE/WfI3R6S2L7t4N8EOn2XW2E48/qJEqiFbyVym1QREbLbe4U6', 'staff');

-- Insert Some Initial Customers for testing
INSERT INTO `customers` (`name`, `address`, `gst_no`, `iec_no`, `pan_no`, `ad_code`, `ifsc_code`, `factory_addresses`) VALUES
('Global Trade Corp', '102 Skyline Business Park, Mumbai, MH', '27AAACG1234A1Z1', '0102030405', 'AAACG1234A', 'AD65784321', 'KKBK0000123', 'Plot 45, MIDC Industrial Area, Pune'),
('Aero Cargo Logistics', 'Unit B, cargo Terminal-2, Delhi Airport', '07BBBCG5678B1Z2', '0504030201', 'BBBCG5678B', 'AD71243125', 'HDFC0000543', 'Warehouse 12, Palam Extension, New Delhi');

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 05:38 PM
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
-- Database: `lean_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `business_categories`
--

CREATE TABLE `business_categories` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_categories`
--

INSERT INTO `business_categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
('b1', 'Wholesale', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('b2', 'Retail', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('b3', 'Corporate', '2026-03-05 06:16:13', '2026-03-05 06:16:13');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `company_id`, `name`, `created_at`, `updated_at`) VALUES
('cat-1', 'tenant-1', 'Electronics', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('cat-2', 'tenant-1', 'Furniture', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('cat-3', 'tenant-1', 'Stationery', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('CAT-6XuqENomz', 'tenant-LG2rhNmVn', 'Grocery', '2026-03-11 04:08:14', '2026-03-11 04:08:14'),
('CAT-bvfSCJJzc', 'tenant-LG2rhNmVn', 'Home Appliances', '2026-03-11 04:08:36', '2026-03-11 04:08:36'),
('CAT-nZL6Rqe0Z', 'tenant-2PofxxGvE', 'Grocery', '2026-03-11 15:39:37', '2026-03-11 15:39:37'),
('CAT-tAqxUvaKV', 'tenant-LG2rhNmVn', 'Electronics', '2026-03-11 04:07:56', '2026-03-11 04:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `max_user_limit` int(11) NOT NULL,
  `registration_payment` decimal(15,2) NOT NULL,
  `saas_plan` varchar(255) NOT NULL,
  `admin_password_plain` varchar(255) DEFAULT NULL,
  `info_name` varchar(255) DEFAULT NULL,
  `info_tagline` varchar(255) DEFAULT NULL,
  `info_address` text DEFAULT NULL,
  `info_phone` varchar(255) DEFAULT NULL,
  `info_email` varchar(255) DEFAULT NULL,
  `info_website` varchar(255) DEFAULT NULL,
  `info_tax_id` varchar(255) DEFAULT NULL,
  `info_logo_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `costing_method` varchar(255) NOT NULL DEFAULT 'moving_average'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `status`, `max_user_limit`, `registration_payment`, `saas_plan`, `admin_password_plain`, `info_name`, `info_tagline`, `info_address`, `info_phone`, `info_email`, `info_website`, `info_tax_id`, `info_logo_url`, `created_at`, `updated_at`, `costing_method`) VALUES
('tenant-1', 'Demo Business Corp', 'Active', 10, 5000.00, 'Annually', NULL, 'Demo Business Corp', 'Streamlining Growth with LeanERP', 'Suite 204, Tech Plaza, Shara-e-Faisal, Karachi', '021-3445566', 'hello@democorp.io', 'www.democorp.io', 'STR-229988-1', NULL, '2026-03-05 06:16:13', '2026-03-05 06:16:13', 'moving_average'),
('tenant-2PofxxGvE', 'SA Limited', 'Active', 5, 0.00, 'Monthly', 'sa123', 'SA Limited', '', '', '', '', '', '', '', '2026-03-11 15:34:01', '2026-03-11 15:34:01', 'moving_average'),
('tenant-LG2rhNmVn', 'AS Limited', 'Active', 5, 0.00, 'Monthly', 'AS123', 'AS Limited', '', '', '', '', '', '', '', '2026-03-11 04:07:13', '2026-03-13 12:33:25', 'moving_average');

-- --------------------------------------------------------

--
-- Table structure for table `custom_roles`
--

CREATE TABLE `custom_roles` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `custom_roles`
--

INSERT INTO `custom_roles` (`id`, `company_id`, `name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
('ROLE-cPbX8H813', 'tenant-LG2rhNmVn', 'Cashier', '', '{\"Dashboard\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false},\"POS\":{\"view\":true,\"create\":true,\"edit\":true,\"delete\":true},\"Inventory\":{\"view\":true,\"create\":true,\"edit\":true,\"delete\":true},\"Purchases\":{\"view\":true,\"create\":true,\"edit\":true,\"delete\":true},\"Sales\":{\"view\":true,\"create\":true,\"edit\":true,\"delete\":true},\"Returns\":{\"view\":true,\"create\":true,\"edit\":true,\"delete\":true},\"Parties\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false},\"Finance\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false},\"Reports\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false},\"Settings\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false},\"Roles\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false},\"Users\":{\"view\":false,\"create\":false,\"edit\":false,\"delete\":false}}', '2026-03-13 10:01:28', '2026-03-13 10:01:28');

-- --------------------------------------------------------

--
-- Table structure for table `document_sequences`
--

CREATE TABLE `document_sequences` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `prefix` varchar(255) NOT NULL DEFAULT '',
  `next_number` int(11) NOT NULL DEFAULT 1,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_sequences`
--

INSERT INTO `document_sequences` (`id`, `company_id`, `type`, `prefix`, `next_number`, `is_locked`, `created_at`, `updated_at`) VALUES
('SEQ-1fXxmZwkV', 'tenant-1', 'item_no', 'ITM-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-1JywM1eXb', 'tenant-2PofxxGvE', 'purchase_return', 'DM-', 1, 0, '2026-03-11 15:34:33', '2026-03-11 15:34:33'),
('SEQ-1TPDP3u0u', 'tenant-LG2rhNmVn', 'purchase_return', 'DM-', 5, 1, '2026-03-11 04:07:39', '2026-03-13 11:17:39'),
('SEQ-3VpkufG2L', 'tenant-1', 'po_number', 'PO-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-6wIWBkwzp', 'tenant-LG2rhNmVn', 'sale_invoice', 'INV-', 7, 1, '2026-03-11 04:07:39', '2026-03-13 09:52:43'),
('SEQ-aKQ3QIIgy', 'tenant-2PofxxGvE', 'sku', 'SKU-', 2, 1, '2026-03-11 15:34:33', '2026-03-11 15:38:13'),
('SEQ-B4x9ovFnN', 'tenant-1', 'sale_invoice', 'INV-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-bXIxrcxXV', 'tenant-LG2rhNmVn', 'sku', 'SKU-', 4, 1, '2026-03-11 04:07:39', '2026-03-11 14:52:24'),
('SEQ-dP7hCN2Jw', 'tenant-2PofxxGvE', 'vendor_no', 'VEND-', 1, 0, '2026-03-11 15:34:33', '2026-03-11 15:34:33'),
('SEQ-hFDhOvHit', 'tenant-2PofxxGvE', 'sale_return', 'CM-', 1, 0, '2026-03-11 15:34:33', '2026-03-11 15:34:33'),
('SEQ-JYEijoeB3', 'tenant-2PofxxGvE', 'po_number', 'PO-', 1, 0, '2026-03-11 15:34:33', '2026-03-11 15:34:33'),
('SEQ-KFwNz8XQJ', 'tenant-1', 'purchase_return', 'DM-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-l0xPlTIPi', 'tenant-LG2rhNmVn', 'po_number', 'PO-', 7, 1, '2026-03-11 04:07:39', '2026-03-13 11:17:17'),
('SEQ-L5LePsIiz', 'tenant-2PofxxGvE', 'item_no', 'ITM-', 46, 1, '2026-03-11 15:34:33', '2026-03-11 15:45:58'),
('SEQ-lyVyTHNIv', 'tenant-1', 'sku', 'SKU-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-LYZUSYEG5', 'tenant-LG2rhNmVn', 'sale_return', 'CM-', 4, 1, '2026-03-11 04:07:39', '2026-03-13 10:58:27'),
('SEQ-murFbdfvo', 'tenant-1', 'customer_no', 'CUST-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-suqFoDCdY', 'tenant-LG2rhNmVn', 'item_no', 'ITM-', 28, 1, '2026-03-11 04:07:39', '2026-03-11 15:14:49'),
('SEQ-V7Epn3mdf', 'tenant-2PofxxGvE', 'sale_invoice', 'INV-', 1, 0, '2026-03-11 15:34:33', '2026-03-11 15:34:33'),
('SEQ-VoOoGGBvd', 'tenant-2PofxxGvE', 'customer_no', 'CUST-', 1, 0, '2026-03-11 15:34:33', '2026-03-11 15:34:33'),
('SEQ-wAgH0pAdK', 'tenant-1', 'sale_return', 'CM-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54'),
('SEQ-wvTWXO9tg', 'tenant-LG2rhNmVn', 'vendor_no', 'VEND-', 7, 1, '2026-03-11 04:07:39', '2026-03-11 04:45:50'),
('SEQ-ZoHxz9AcA', 'tenant-LG2rhNmVn', 'customer_no', 'CUST-', 6, 1, '2026-03-11 04:07:39', '2026-03-11 04:38:09'),
('SEQ-zy0WIaW0v', 'tenant-1', 'vendor_no', 'VEND-', 1, 0, '2026-03-05 06:17:54', '2026-03-05 06:17:54');

-- --------------------------------------------------------

--
-- Table structure for table `entity_types`
--

CREATE TABLE `entity_types` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `entity_types`
--

INSERT INTO `entity_types` (`id`, `name`, `created_at`, `updated_at`) VALUES
('e1', 'Individual', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('e2', 'Business', '2026-03-05 06:16:13', '2026-03-05 06:16:13');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_cost_layers`
--

CREATE TABLE `inventory_cost_layers` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `reference_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_cost_layers`
--

INSERT INTO `inventory_cost_layers` (`id`, `company_id`, `product_id`, `quantity`, `remaining_quantity`, `unit_cost`, `reference_id`, `reference_type`, `created_at`, `updated_at`) VALUES
('CL-AhRD5nmOI', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', 5, 5, 3500.00, 'RCV-b5tg2sO30', 'purchase_receive', '2026-03-11 15:55:58', '2026-03-11 15:55:58'),
('CL-AjTSL8U0Z', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', 4, 4, 8000.00, 'RCV-UG5Bgq6M4', 'purchase_receive', '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('CL-AXgPygZrN', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', 5, 5, 90.00, 'RCV-NyIE7SFR7', 'purchase_receive', '2026-03-11 15:54:35', '2026-03-11 15:54:35'),
('CL-CJZqRZwhK', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', 2, 2, 8000.00, 'CM-00001', 'sale_return', '2026-03-13 10:44:48', '2026-03-13 10:44:48'),
('CL-dVjG2jMwo', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', 10, 10, 90.00, 'RCV-UG5Bgq6M4', 'purchase_receive', '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('CL-GBZc8isGC', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', 4, 4, 3000.00, 'RCV-BDqc3qmlk', 'purchase_receive', '2026-03-11 15:52:49', '2026-03-11 15:52:49'),
('CL-KWOWMTuiW', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', 4, 4, 8000.00, 'RCV-V3wtWKG3J', 'purchase_receive', '2026-03-11 15:17:33', '2026-03-11 15:17:33'),
('CL-NvfSilmxr', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', 5, 5, 17000.00, 'RCV-3Hjdh2pgM', 'purchase_receive', '2026-03-11 15:56:27', '2026-03-11 15:56:27'),
('CL-nwPHmsEju', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', 1, 1, 8000.00, 'CM-00003', 'sale_return', '2026-03-13 10:58:27', '2026-03-13 10:58:27'),
('CL-rn7JOHMFU', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', 6, 6, 3000.00, 'RCV-UG5Bgq6M4', 'purchase_receive', '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('CL-TBs079sRM', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', 3, 3, 17000.00, 'CM-00002', 'sale_return', '2026-03-13 10:58:13', '2026-03-13 10:58:13'),
('CL-xsb83RgNw', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', 2, 2, 3500.00, 'CM-00002', 'sale_return', '2026-03-13 10:58:13', '2026-03-13 10:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

CREATE TABLE `inventory_ledger` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `transaction_type` varchar(255) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_ledger`
--

INSERT INTO `inventory_ledger` (`id`, `company_id`, `product_id`, `created_at`, `transaction_type`, `quantity_change`, `reference_id`, `updated_at`) VALUES
('LEG-1', 'tenant-1', 'PRD-1', '2026-02-23 06:16:14', 'Purchase_Receive', 10, 'PO-5001', '2026-02-23 06:16:14'),
('LEG-2', 'tenant-1', 'PRD-1', '2026-03-01 06:16:14', 'Sale', -1, 'INV-1001', '2026-03-01 06:16:14'),
('LEG-29LPYYAfK', 'tenant-2PofxxGvE', 'PRD-Iha8qjStW', '2026-03-11 15:45:56', 'Adjustment_Internal', 100, 'OPENING', '2026-03-11 15:45:56'),
('LEG-2BefEV3ca', 'tenant-2PofxxGvE', 'PRD-YNIuxKXh3', '2026-03-11 15:45:57', 'Adjustment_Internal', 5, 'OPENING', '2026-03-11 15:45:57'),
('LEG-3', 'tenant-1', 'PRD-2', '2026-02-03 06:16:14', 'Adjustment_Internal', 17, 'OPENING', '2026-02-03 06:16:14'),
('LEG-3jtGkBkZ5', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 11:17:22', 'Purchase_Receive', 4, 'RCV-UG5Bgq6M4', '2026-03-13 11:17:22'),
('LEG-3m31SjLBT', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 11:15:53', 'Purchase_Return', -2, 'DM-00001', '2026-03-13 11:15:53'),
('LEG-3yPw10Uak', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', '2026-03-11 15:52:49', 'Purchase_Receive', 4, 'RCV-BDqc3qmlk', '2026-03-11 15:52:49'),
('LEG-4', 'tenant-1', 'PRD-2', '2026-03-03 06:16:14', 'Sale', -2, 'INV-1002', '2026-03-03 06:16:14'),
('LEG-4i2lxyLAM', 'tenant-2PofxxGvE', 'PRD-vznfdprhg', '2026-03-11 15:45:55', 'Adjustment_Internal', 16, 'OPENING', '2026-03-11 15:45:55'),
('LEG-4zG5HPcnQ', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 09:52:43', 'Sale', -1, 'INV-00006', '2026-03-13 09:52:43'),
('LEG-5', 'tenant-1', 'PRD-5', '2026-02-03 06:16:14', 'Adjustment_Internal', 43, 'OPENING', '2026-02-03 06:16:14'),
('LEG-6', 'tenant-1', 'PRD-5', '2026-03-04 06:16:14', 'Sale', -1, 'INV-1003', '2026-03-04 06:16:14'),
('LEG-60bK9XMV5', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', '2026-03-11 15:54:35', 'Purchase_Receive', 5, 'RCV-NyIE7SFR7', '2026-03-11 15:54:35'),
('LEG-7ADjJpagb', 'tenant-2PofxxGvE', 'PRD-4QuVKahNf', '2026-03-11 15:45:53', 'Adjustment_Internal', 12, 'OPENING', '2026-03-11 15:45:53'),
('LEG-8DGOLJ0Ov', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', '2026-03-11 05:46:40', 'Adjustment_Damage', 9, 'MANUAL_ADJ', '2026-03-11 05:46:40'),
('LEG-9MYOjcGrX', 'tenant-LG2rhNmVn', 'PRD-VuQvg7DPx', '2026-03-11 05:44:44', 'Adjustment_Found', 10, 'MANUAL_ADJ', '2026-03-11 05:44:44'),
('LEG-AIQ87deSb', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', '2026-03-11 05:45:42', 'Adjustment_Found', 4, 'MANUAL_ADJ', '2026-03-11 05:45:42'),
('LEG-ATY7hDfuL', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', '2026-03-13 09:17:45', 'Sale', -3, 'INV-00005', '2026-03-13 09:17:45'),
('LEG-AVIB0MOsX', 'tenant-LG2rhNmVn', 'PRD-VrTvh9qRh', '2026-03-11 15:14:49', 'Adjustment_Internal', 100, 'OPENING', '2026-03-11 15:14:49'),
('LEG-AvyigIn3x', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', '2026-03-11 15:54:55', 'Sale', -5, 'INV-00003', '2026-03-11 15:54:55'),
('LEG-B3GTpEZvP', 'tenant-2PofxxGvE', 'PRD-YJ4OwVOq5', '2026-03-11 15:45:54', 'Adjustment_Internal', 12, 'OPENING', '2026-03-11 15:45:54'),
('LEG-C5USeZCoZ', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', '2026-03-13 11:17:22', 'Purchase_Receive', 6, 'RCV-UG5Bgq6M4', '2026-03-13 11:17:22'),
('LEG-CTihbWoYm', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 09:17:45', 'Sale', -3, 'INV-00005', '2026-03-13 09:17:45'),
('LEG-ctPas7eUU', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 10:44:48', 'Sale_Return', 2, 'CM-00001', '2026-03-13 10:44:48'),
('LEG-cvPaqpzLV', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-11 05:46:12', 'Adjustment_Found', 11, 'MANUAL_ADJ', '2026-03-11 05:46:12'),
('LEG-dHHxdbPNx', 'tenant-2PofxxGvE', 'PRD-EfoOvftaJ', '2026-03-11 15:45:54', 'Adjustment_Internal', 12, 'OPENING', '2026-03-11 15:45:54'),
('LEG-dwn9kCN42', 'tenant-2PofxxGvE', 'PRD-rxoo63vIB', '2026-03-11 15:45:56', 'Adjustment_Internal', 100, 'OPENING', '2026-03-11 15:45:56'),
('LEG-DZAVTzRRu', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', '2026-03-13 10:58:13', 'Sale_Return', 3, 'CM-00002', '2026-03-13 10:58:13'),
('LEG-EZu2OgKHS', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', '2026-03-11 05:46:46', 'Adjustment_Damage', 20, 'MANUAL_ADJ', '2026-03-11 05:46:46'),
('LEG-GaPcF8Q6J', 'tenant-2PofxxGvE', 'PRD-KIsAou3tv', '2026-03-11 15:45:58', 'Adjustment_Internal', 9, 'OPENING', '2026-03-11 15:45:58'),
('LEG-gFS4MBqh7', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', '2026-03-11 05:45:27', 'Adjustment_Found', 5, 'MANUAL_ADJ', '2026-03-11 05:45:27'),
('LEG-GPHHagG6f', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', '2026-03-13 11:16:01', 'Purchase_Return', -3, 'DM-00002', '2026-03-13 11:16:01'),
('LEG-GsXsYGciO', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', '2026-03-11 15:53:18', 'Sale', -4, 'INV-00002', '2026-03-11 15:53:18'),
('LEG-gzOUVzAZ3', 'tenant-LG2rhNmVn', 'PRD-IICJ1iutp', '2026-03-11 15:14:49', 'Adjustment_Internal', 16, 'OPENING', '2026-03-11 15:14:49'),
('LEG-hw2z8CNiG', 'tenant-2PofxxGvE', 'PRD-hRfizzF5K', '2026-03-11 15:45:54', 'Adjustment_Internal', 12, 'OPENING', '2026-03-11 15:45:54'),
('LEG-KapVNDPs0', 'tenant-LG2rhNmVn', 'PRD-5jgT4Xd4W', '2026-03-13 11:17:39', 'Purchase_Return', -2, 'DM-00004', '2026-03-13 11:17:39'),
('LEG-leAflgsW6', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 11:17:39', 'Purchase_Return', -2, 'DM-00004', '2026-03-13 11:17:39'),
('LEG-m1xZ7GudT', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', '2026-03-11 15:56:27', 'Purchase_Receive', 5, 'RCV-3Hjdh2pgM', '2026-03-11 15:56:27'),
('LEG-M3W3GiIak', 'tenant-2PofxxGvE', 'PRD-Q3l4s47df', '2026-03-11 15:45:57', 'Adjustment_Internal', 15, 'OPENING', '2026-03-11 15:45:57'),
('LEG-mfW6R183M', 'tenant-2PofxxGvE', 'PRD-iouijBNdJ', '2026-03-11 15:45:56', 'Adjustment_Internal', 100, 'OPENING', '2026-03-11 15:45:56'),
('LEG-MHWs7mpxc', 'tenant-2PofxxGvE', 'PRD-ZbRXxJtNl', '2026-03-11 15:45:58', 'Adjustment_Internal', 20, 'OPENING', '2026-03-11 15:45:58'),
('LEG-N4kKHqxY4', 'tenant-2PofxxGvE', 'PRD-Sop5VD5Wt', '2026-03-11 15:45:57', 'Adjustment_Internal', 4, 'OPENING', '2026-03-11 15:45:57'),
('LEG-nMrxUQ3gd', 'tenant-2PofxxGvE', 'PRD-uzNRu5gZU', '2026-03-11 15:45:56', 'Adjustment_Internal', 100, 'OPENING', '2026-03-11 15:45:56'),
('LEG-NnfFNyTk9', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-11 15:51:20', 'Sale', -4, 'INV-00001', '2026-03-11 15:51:20'),
('LEG-OqpcWdyWQ', 'tenant-2PofxxGvE', 'PRD-kn3ZT7hRk', '2026-03-11 15:45:57', 'Adjustment_Internal', 6, 'OPENING', '2026-03-11 15:45:57'),
('LEG-QOhKVWTLZ', 'tenant-2PofxxGvE', 'PRD-cDGpcLd5J', '2026-03-11 15:45:57', 'Adjustment_Internal', 10, 'OPENING', '2026-03-11 15:45:57'),
('LEG-qrpbrYpiq', 'tenant-LG2rhNmVn', 'PRD-TmeRHOV4n', '2026-03-11 15:14:49', 'Adjustment_Internal', 12, 'OPENING', '2026-03-11 15:14:49'),
('LEG-QsbVb4GrF', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', '2026-03-11 15:56:56', 'Sale', -5, 'INV-00004', '2026-03-11 15:56:56'),
('LEG-R4kIS0zsH', 'tenant-2PofxxGvE', 'PRD-fnoDUMyu7', '2026-03-11 15:45:54', 'Adjustment_Internal', 12, 'OPENING', '2026-03-11 15:45:54'),
('LEG-swZ7xXMpQ', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-11 15:17:33', 'Purchase_Receive', 4, 'RCV-V3wtWKG3J', '2026-03-11 15:17:33'),
('LEG-SyBAQPoXG', 'tenant-LG2rhNmVn', 'PRD-8kxXsCPl2', '2026-03-13 09:52:43', 'Sale', -1, 'INV-00006', '2026-03-13 09:52:43'),
('LEG-T44CJYEaU', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', '2026-03-13 10:58:13', 'Sale_Return', 2, 'CM-00002', '2026-03-13 10:58:13'),
('LEG-tBM1WRfyL', 'tenant-LG2rhNmVn', 'PRD-w7mdPxe6Q', '2026-03-11 05:46:33', 'Adjustment_Damage', 6, 'MANUAL_ADJ', '2026-03-11 05:46:33'),
('LEG-UCi5keZaQ', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', '2026-03-11 15:56:56', 'Sale', -5, 'INV-00004', '2026-03-11 15:56:56'),
('LEG-uJXsH4VRQ', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', '2026-03-13 11:16:10', 'Purchase_Return', -1, 'DM-00003', '2026-03-13 11:16:10'),
('LEG-uQlp7y5NX', 'tenant-2PofxxGvE', 'PRD-TI3fSzvid', '2026-03-11 15:45:55', 'Adjustment_Internal', 16, 'OPENING', '2026-03-11 15:45:55'),
('LEG-uVX2kDd1p', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', '2026-03-13 11:17:39', 'Purchase_Return', -3, 'DM-00004', '2026-03-13 11:17:39'),
('LEG-WJeXNNNNG', 'tenant-2PofxxGvE', 'PRD-fDNOZBg4W', '2026-03-11 15:45:54', 'Adjustment_Internal', 16, 'OPENING', '2026-03-11 15:45:54'),
('LEG-WtglHSlp7', 'tenant-2PofxxGvE', 'PRD-HKoDbsykS', '2026-03-11 15:45:55', 'Adjustment_Internal', 16, 'OPENING', '2026-03-11 15:45:55'),
('LEG-Y8cHctHhG', 'tenant-2PofxxGvE', 'PRD-Ahzbyicxb', '2026-03-11 15:45:55', 'Adjustment_Internal', 16, 'OPENING', '2026-03-11 15:45:55'),
('LEG-Yq2YP940S', 'tenant-2PofxxGvE', 'PRD-wRhFmsw29', '2026-03-11 15:45:56', 'Adjustment_Internal', 100, 'OPENING', '2026-03-11 15:45:56'),
('LEG-zaqyy8hPR', 'tenant-LG2rhNmVn', 'PRD-6H3ZUj4uZ', '2026-03-13 10:58:27', 'Sale_Return', 1, 'CM-00003', '2026-03-13 10:58:27'),
('LEG-znClCfqYP', 'tenant-LG2rhNmVn', 'PRD-i7pLh5tQD', '2026-03-13 11:17:22', 'Purchase_Receive', 10, 'RCV-UG5Bgq6M4', '2026-03-13 11:17:22'),
('LEG-zZkcRFnEK', 'tenant-LG2rhNmVn', 'PRD-myEHgGJ1Y', '2026-03-11 15:55:58', 'Purchase_Receive', 5, 'RCV-b5tg2sO30', '2026-03-11 15:55:58');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_01_01_000001_create_erp_tables', 1),
(2, '2025_01_01_000002_add_api_token_to_users', 1),
(3, '2025_01_02_000001_add_costing_and_partial_receiving', 1),
(4, '2025_01_03_000001_create_document_sequences', 1),
(5, '2025_01_04_000001_add_item_number_to_products', 1),
(6, '2026_02_13_202854_add_admin_password_to_companies_table', 1),
(7, '2026_02_13_203523_add_password_plain_to_users_table', 1),
(8, '2026_02_14_074634_add_barcode_to_products_table', 1),
(9, '2026_02_14_075557_add_unique_barcode_per_company', 1),
(10, '2026_02_14_082114_add_returned_quantity_to_items', 1);

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sub_type` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `credit_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bank_details` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `company_id`, `code`, `type`, `name`, `phone`, `email`, `address`, `sub_type`, `payment_terms`, `credit_limit`, `bank_details`, `category`, `opening_balance`, `current_balance`, `created_at`, `updated_at`) VALUES
('PT-1', 'tenant-1', 'CUST-001', 'Customer', 'Alice Henderson', '0300-1122334', 'alice@example.com', 'Apartment 4B, Blue Tower, Karachi', 'Individual', 'Net 30', 500000.00, 'HBL-001223344', 'Retail', 0.00, 145000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PT-2', 'tenant-1', 'CUST-002', 'Customer', 'Tech Solutions Ltd', '021-3455667', 'billing@techsol.com', 'Plot 23, Industrial Area, Lahore', 'Business', 'Net 15', 2000000.00, 'Meezan-998877', 'Wholesale', 0.00, 0.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PT-3', 'tenant-1', 'VND-001', 'Vendor', 'Global Tech Wholesale', '042-9988112', 'sales@globaltech.com', 'Suite 9, Commercial Plaza, Islamabad', 'Business', 'COD', 0.00, 'Standard Chartered-1122', 'Wholesale', 0.00, 85000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PT-30Y6cqxvR', 'tenant-LG2rhNmVn', 'CUST-00004', 'Customer', 'Sameer', '0300 3345090', 'Abdullah@gmail.com', 'gyg Street Lahore', '', '2', 1000.00, 'meezan bank account', '', 100.00, 20000.00, '2026-03-11 04:38:09', '2026-03-13 10:44:48'),
('PT-3w2livdjm', 'tenant-LG2rhNmVn', 'VEND-00001', 'Vendor', 'abc', '0324 7267358', 'abc@gmail.com', 'abc street Lahore', '', '8', 0.00, 'meezan bank account', '', 0.00, 0.00, '2026-03-11 04:45:49', '2026-03-11 16:26:08'),
('PT-anOvIqtkZ', 'tenant-LG2rhNmVn', 'CUST-00005', 'Customer', 'Abdul Rehman', '0300 8046613', 'AbdulRehman@gmail.com', 'dds Street Lahore', '', '3', 7.00, 'allied bank account', '', 4.00, 0.00, '2026-03-11 04:38:09', '2026-03-11 16:24:02'),
('PT-CQ8hOaGmN', 'tenant-LG2rhNmVn', 'VEND-00006', 'Vendor', 'pqr', '0321 8046613', 'pqr@gmail.com', 'pqr Street Lahore', '', '10', 0.00, 'allied bank account', '', 0.00, 85000.00, '2026-03-11 04:45:50', '2026-03-11 16:26:19'),
('PT-jA68knngN', 'tenant-LG2rhNmVn', 'VEND-00003', 'Vendor', 'mno', '0329 3345070', 'mno@gmail.com', 'mno Street Lahore', '', '12', 0.00, 'HBL Bank Account', '', 0.00, 40630.00, '2026-03-11 04:45:50', '2026-03-13 11:17:39'),
('PT-NP4rGBxRB', 'tenant-LG2rhNmVn', 'VEND-00005', 'Vendor', 'asd', '0300 1403532', 'asd@gmail.com', 'asd Street Lahore', '', '', 0.00, '', '', 0.00, 450.00, '2026-03-11 04:45:50', '2026-03-11 15:54:35'),
('PT-QPyabfNOE', 'tenant-LG2rhNmVn', 'VEND-00004', 'Vendor', 'aim', '0300 4374597', 'aim@gmail.com', 'aim Street Lahore', '', '', 0.00, '', '', 0.00, 16000.00, '2026-03-11 04:45:50', '2026-03-13 11:15:53'),
('PT-rng8MeeTU', 'tenant-LG2rhNmVn', 'CUST-00003', 'Customer', 'Abdullah', '0329 4374597', 'sameer@gmail.com', 'asd Street Lahore', '', '8', 11.00, 'HBL Bank Account', '', 4.00, 50000.00, '2026-03-11 04:38:08', '2026-03-13 10:58:27'),
('PT-wF4CbMJfd', 'tenant-LG2rhNmVn', 'CUST-00001', 'Customer', 'Saad', '0321 1403532', 'saad@gmail.com', 'xyz street Lahore', '', '14', 19.00, 'MCB Bank Account', '', 9.00, 0.00, '2026-03-11 04:38:08', '2026-03-11 16:24:45'),
('PT-wwfbF4mtR', 'tenant-LG2rhNmVn', 'VEND-00002', 'Vendor', 'xyz', '0321 8578001', 'xyz@gmail.com', 'xyz Street Lahore', '', '', 0.00, '', '', 0.00, 3500.00, '2026-03-11 04:45:49', '2026-03-13 11:16:10'),
('PT-ZqCOQjY00', 'tenant-LG2rhNmVn', 'CUST-00002', 'Customer', 'Sami', '0324 8578001', 'sami@gmail.com', 'abc Street Lahore', '', '7', 12.00, 'Standard Chartered Account', '', 17.00, 55000.00, '2026-03-11 04:38:08', '2026-03-13 10:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `party_id` varchar(255) NOT NULL,
  `date` bigint(20) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `company_id`, `party_id`, `date`, `amount`, `payment_method`, `type`, `reference_no`, `notes`, `created_at`, `updated_at`) VALUES
('PAY-1', 'tenant-1', 'PT-1', 1772450173442, 70000.00, 'Bank', 'Receipt', 'INV-1001', 'Partial payment', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PAY-2', 'tenant-1', 'PT-3', 1772277373442, 95000.00, 'Bank', 'Payment', 'PO-5001', 'Vendor payment', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PAY-4mQKGXlAt', 'tenant-LG2rhNmVn', 'PT-30Y6cqxvR', 1773411465156, 90000.00, 'Cash', 'Payment Received', 'INV-00005', 'Auto-recorded from POS cash sale', '2026-03-13 09:17:45', '2026-03-13 09:17:45'),
('PAY-eLp9Oi5pc', 'tenant-LG2rhNmVn', 'PT-anOvIqtkZ', 1773262398478, 20000.00, 'Cash', 'Payment Received', 'INV-00002', 'Auto-recorded from POS cash sale', '2026-03-11 15:53:18', '2026-03-11 15:53:18'),
('PAY-Hlga5xT3u', 'tenant-LG2rhNmVn', 'PT-rng8MeeTU', 1773262495046, 750.00, 'Cash', 'Payment Received', 'INV-00003', 'Auto-recorded from POS cash sale', '2026-03-11 15:54:55', '2026-03-11 15:54:55'),
('PAY-OaehwzuB5', 'tenant-LG2rhNmVn', 'PT-rng8MeeTU', 1773413591983, 30000.00, 'Cash', 'Payment Received', 'INV-00006', '', '2026-03-13 09:53:11', '2026-03-13 09:53:11');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `item_number` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `uom` varchar(255) NOT NULL,
  `category_id` varchar(255) NOT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `company_id`, `sku`, `barcode`, `item_number`, `name`, `type`, `uom`, `category_id`, `current_stock`, `reorder_level`, `unit_cost`, `unit_price`, `created_at`, `updated_at`) VALUES
('PRD-1', 'tenant-1', 'LAP-001', NULL, '', 'MacBook Pro 14\"', 'Product', 'Pcs', 'cat-1', 8, 2, 180000.00, 215000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PRD-2', 'tenant-1', 'MS-023', NULL, '', 'Logitech MX Master 3S', 'Product', 'Pcs', 'cat-1', 15, 5, 12000.00, 18500.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PRD-3', 'tenant-1', 'DSK-99', NULL, '', 'Ergonomic Standing Desk', 'Product', 'Pcs', 'cat-2', 4, 1, 35000.00, 52000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PRD-4', 'tenant-1', 'CH-45', NULL, '', 'Aeron Executive Chair', 'Product', 'Pcs', 'cat-2', 0, 2, 85000.00, 115000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PRD-4QuVKahNf', 'tenant-2PofxxGvE', 'ITM-00010', '1234567890', 'ITM-00024', 'abc', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 12, 0, 20.00, 22.00, '2026-03-11 15:45:53', '2026-03-11 15:45:53'),
('PRD-5', 'tenant-1', 'ST-BOOK', NULL, '', 'Leather Bound Journal', 'Product', 'Box', 'cat-3', 42, 10, 450.00, 1200.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PRD-5jgT4Xd4W', 'tenant-LG2rhNmVn', 'SKU003', NULL, 'ITM-00007', 'Bed', 'Product', 'PCS', 'CAT-bvfSCJJzc', 9, 3, 3000.00, 5000.00, '2026-03-11 04:29:24', '2026-03-13 11:17:39'),
('PRD-6', 'tenant-1', 'SRV-01', NULL, '', 'IT Support (Hourly)', 'Service', 'Pcs', 'cat-1', 0, 0, 0.00, 2500.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('PRD-6H3ZUj4uZ', 'tenant-LG2rhNmVn', 'SKU001', NULL, 'ITM-00001', 'Refrigerator', 'Product', 'PCS', 'CAT-tAqxUvaKV', 10, 5, 8000.00, 10000.00, '2026-03-11 04:25:19', '2026-03-13 11:17:39'),
('PRD-8kxXsCPl2', 'tenant-LG2rhNmVn', 'SKU006', NULL, 'ITM-00008', 'Sofa', 'Product', 'PCS', 'CAT-bvfSCJJzc', 8, 2, 17000.00, 20000.00, '2026-03-11 05:23:21', '2026-03-13 10:58:13'),
('PRD-9JwomRwnc', 'tenant-LG2rhNmVn', 'ITM-00012', NULL, 'ITM-00018', 'abc3', 'Product', 'Pcs', 'CAT-tAqxUvaKV', 100, 50, 210.00, 222.00, '2026-03-11 14:56:16', '2026-03-11 14:56:16'),
('PRD-a16GasawC', 'tenant-LG2rhNmVn', 'ITM-00011', NULL, 'ITM-00017', 'abc2', 'Product', 'Pcs', 'CAT-bvfSCJJzc', 16, 6, 1.00, 2.00, '2026-03-11 14:56:16', '2026-03-11 14:56:16'),
('PRD-Ahzbyicxb', 'tenant-2PofxxGvE', 'SKU-00002', NULL, 'ITM-00033', 'abc2', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 16, 0, 1.00, 2.00, '2026-03-11 15:45:55', '2026-03-11 15:45:55'),
('PRD-cDGpcLd5J', 'tenant-2PofxxGvE', 'SKU002', NULL, 'ITM-00039', 'Air Conditioner', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 10, 0, 18000.00, 20000.00, '2026-03-11 15:45:57', '2026-03-11 15:45:57'),
('PRD-EfoOvftaJ', 'tenant-2PofxxGvE', 'ITM-00010', NULL, 'ITM-00026', 'abc', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 12, 0, 20.00, 22.00, '2026-03-11 15:45:54', '2026-03-11 15:45:54'),
('PRD-fDNOZBg4W', 'tenant-2PofxxGvE', 'ITM-00011', NULL, 'ITM-00029', 'abc2', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 16, 0, 1.00, 2.00, '2026-03-11 15:45:54', '2026-03-11 15:45:54'),
('PRD-fnoDUMyu7', 'tenant-2PofxxGvE', 'ITM-00010', NULL, 'ITM-00025', 'abc', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 12, 0, 20.00, 22.00, '2026-03-11 15:45:54', '2026-03-11 15:45:54'),
('PRD-HkhTxDMq6', 'tenant-LG2rhNmVn', 'ITM-00010', NULL, 'ITM-00016', 'abc', 'Product', 'Pcs', 'CAT-6XuqENomz', 12, 3, 20.00, 22.00, '2026-03-11 14:56:16', '2026-03-11 14:56:16'),
('PRD-HKoDbsykS', 'tenant-2PofxxGvE', 'ITM-00011', NULL, 'ITM-00032', 'abc2', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 16, 0, 1.00, 2.00, '2026-03-11 15:45:55', '2026-03-11 15:45:55'),
('PRD-hRfizzF5K', 'tenant-2PofxxGvE', 'SKU-00001', NULL, 'ITM-00028', 'abc', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 12, 0, 20.00, 22.00, '2026-03-11 15:45:54', '2026-03-11 15:45:54'),
('PRD-i7pLh5tQD', 'tenant-LG2rhNmVn', 'SKU005', NULL, 'ITM-00005', 'Eggs', 'Product', 'Dozen', 'CAT-6XuqENomz', 11, 2, 90.00, 150.00, '2026-03-11 04:25:20', '2026-03-13 11:17:39'),
('PRD-Iha8qjStW', 'tenant-2PofxxGvE', 'ITM-00012', NULL, 'ITM-00034', 'abc3', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 100, 0, 210.00, 222.00, '2026-03-11 15:45:56', '2026-03-11 15:45:56'),
('PRD-IICJ1iutp', 'tenant-LG2rhNmVn', 'ITM-00011', NULL, 'ITM-00026', 'abc2', 'Product', 'Pcs', 'CAT-bvfSCJJzc', 16, 6, 1.00, 2.00, '2026-03-11 15:14:49', '2026-03-11 15:14:49'),
('PRD-iouijBNdJ', 'tenant-2PofxxGvE', 'ITM-00012', NULL, 'ITM-00037', 'abc3', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 100, 0, 210.00, 222.00, '2026-03-11 15:45:56', '2026-03-11 15:45:56'),
('PRD-kFmxEGAD5', 'tenant-LG2rhNmVn', 'ITM-00012', NULL, 'ITM-00021', 'abc3', 'Product', 'Pcs', 'CAT-tAqxUvaKV', 100, 50, 210.00, 222.00, '2026-03-11 15:11:08', '2026-03-11 15:11:08'),
('PRD-KIsAou3tv', 'tenant-2PofxxGvE', 'SKU006', NULL, 'ITM-00044', 'Sofa', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 9, 0, 17000.00, 20000.00, '2026-03-11 15:45:58', '2026-03-11 15:45:58'),
('PRD-kn3ZT7hRk', 'tenant-2PofxxGvE', 'SKU004', NULL, 'ITM-00043', 'Rice', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 6, 0, 170.00, 300.00, '2026-03-11 15:45:57', '2026-03-11 15:45:57'),
('PRD-maOmXbWgE', 'tenant-LG2rhNmVn', 'SKU-00002', '234567', 'ITM-00011', 'abc2', 'Product', 'Pcs', 'CAT-bvfSCJJzc', 16, 6, 1.00, 2.00, '2026-03-11 14:52:24', '2026-03-11 14:52:24'),
('PRD-MMx3PQ7na', 'tenant-LG2rhNmVn', 'ITM-00010', NULL, 'ITM-00019', 'abc', 'Product', 'Pcs', 'CAT-6XuqENomz', 12, 3, 20.00, 22.00, '2026-03-11 15:11:07', '2026-03-11 15:11:07'),
('PRD-myEHgGJ1Y', 'tenant-LG2rhNmVn', 'SKU007', NULL, 'ITM-00009', 'Table', 'Product', 'PCS', 'CAT-bvfSCJJzc', 18, 3, 3500.00, 5000.00, '2026-03-11 05:23:21', '2026-03-13 11:16:10'),
('PRD-NVumz6YPl', 'tenant-LG2rhNmVn', 'ITM-00011', NULL, 'ITM-00023', 'abc2', 'Product', 'Pcs', 'CAT-bvfSCJJzc', 16, 6, 1.00, 2.00, '2026-03-11 15:11:48', '2026-03-11 15:11:48'),
('PRD-pWm8WBPVK', 'tenant-LG2rhNmVn', 'ITM-00011', NULL, 'ITM-00020', 'abc2', 'Product', 'Pcs', 'CAT-bvfSCJJzc', 16, 6, 1.00, 2.00, '2026-03-11 15:11:08', '2026-03-11 15:11:08'),
('PRD-Q3l4s47df', 'tenant-2PofxxGvE', 'SKU001', NULL, 'ITM-00042', 'Refrigerator', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 15, 0, 8000.00, 10000.00, '2026-03-11 15:45:57', '2026-03-11 15:45:57'),
('PRD-Q9ovdJ1Zx', 'tenant-LG2rhNmVn', 'SKU-00003', '345678', 'ITM-00012', 'abc3', 'Product', 'Pcs', 'CAT-tAqxUvaKV', 100, 50, 210.00, 222.00, '2026-03-11 14:52:24', '2026-03-11 14:52:24'),
('PRD-qJnOzGJn9', 'tenant-LG2rhNmVn', 'SKU-00001', '123456', 'ITM-00010', 'abc', 'Product', 'Pcs', 'CAT-6XuqENomz', 12, 3, 20.00, 22.00, '2026-03-11 14:52:23', '2026-03-11 14:52:23'),
('PRD-RKQcgdwPM', 'tenant-LG2rhNmVn', 'ITM-00012', NULL, 'ITM-00024', 'abc3', 'Product', 'Pcs', 'CAT-tAqxUvaKV', 100, 50, 210.00, 222.00, '2026-03-11 15:11:48', '2026-03-11 15:11:48'),
('PRD-rxoo63vIB', 'tenant-2PofxxGvE', 'SKU-00003', NULL, 'ITM-00038', 'abc3', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 100, 0, 210.00, 222.00, '2026-03-11 15:45:56', '2026-03-11 15:45:56'),
('PRD-Sop5VD5Wt', 'tenant-2PofxxGvE', 'SKU005', NULL, 'ITM-00041', 'Eggs', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 4, 0, 90.00, 150.00, '2026-03-11 15:45:57', '2026-03-11 15:45:57'),
('PRD-TI3fSzvid', 'tenant-2PofxxGvE', 'ITM-00011', NULL, 'ITM-00030', 'abc2', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 16, 0, 1.00, 2.00, '2026-03-11 15:45:55', '2026-03-11 15:45:55'),
('PRD-TmeRHOV4n', 'tenant-LG2rhNmVn', 'ITM-00010', NULL, 'ITM-00025', 'abc', 'Product', 'Pcs', 'CAT-6XuqENomz', 12, 3, 20.00, 22.00, '2026-03-11 15:14:49', '2026-03-11 15:14:49'),
('PRD-uzNRu5gZU', 'tenant-2PofxxGvE', 'ITM-00012', NULL, 'ITM-00035', 'abc3', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 100, 0, 210.00, 222.00, '2026-03-11 15:45:56', '2026-03-11 15:45:56'),
('PRD-VrTvh9qRh', 'tenant-LG2rhNmVn', 'ITM-00012', NULL, 'ITM-00027', 'abc3', 'Product', 'Pcs', 'CAT-tAqxUvaKV', 100, 50, 210.00, 222.00, '2026-03-11 15:14:49', '2026-03-11 15:14:49'),
('PRD-VuQvg7DPx', 'tenant-LG2rhNmVn', 'SKU002', NULL, 'ITM-00006', 'Air Conditioner', 'Product', 'PCS', 'CAT-bvfSCJJzc', 10, 2, 18000.00, 20000.00, '2026-03-11 04:29:24', '2026-03-11 05:44:44'),
('PRD-vznfdprhg', 'tenant-2PofxxGvE', 'ITM-00011', NULL, 'ITM-00031', 'abc2', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 16, 0, 1.00, 2.00, '2026-03-11 15:45:55', '2026-03-11 15:45:55'),
('PRD-w7mdPxe6Q', 'tenant-LG2rhNmVn', 'SKU004', NULL, 'ITM-00004', 'Rice', 'Product', 'kgs', 'CAT-6XuqENomz', 6, 5, 170.00, 300.00, '2026-03-11 04:25:20', '2026-03-11 05:46:33'),
('PRD-wRhFmsw29', 'tenant-2PofxxGvE', 'ITM-00012', NULL, 'ITM-00036', 'abc3', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 100, 0, 210.00, 222.00, '2026-03-11 15:45:56', '2026-03-11 15:45:56'),
('PRD-WtMifbqVK', 'tenant-LG2rhNmVn', 'ITM-00010', NULL, 'ITM-00022', 'abc', 'Product', 'Pcs', 'CAT-6XuqENomz', 12, 3, 20.00, 22.00, '2026-03-11 15:11:47', '2026-03-11 15:11:47'),
('PRD-YJ4OwVOq5', 'tenant-2PofxxGvE', 'ITM-00010', NULL, 'ITM-00027', 'abc', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 12, 0, 20.00, 22.00, '2026-03-11 15:45:54', '2026-03-11 15:45:54'),
('PRD-YNIuxKXh3', 'tenant-2PofxxGvE', 'SKU003', NULL, 'ITM-00040', 'Bed', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 5, 0, 3000.00, 5000.00, '2026-03-11 15:45:57', '2026-03-11 15:45:57'),
('PRD-ZbRXxJtNl', 'tenant-2PofxxGvE', 'SKU007', NULL, 'ITM-00045', 'Table', 'Product', 'PCS', 'CAT-nZL6Rqe0Z', 20, 0, 3500.00, 5000.00, '2026-03-11 15:45:58', '2026-03-11 15:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` varchar(255) NOT NULL,
  `purchase_order_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_line_cost` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `received_quantity` int(11) NOT NULL DEFAULT 0,
  `returned_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_order_id`, `product_id`, `quantity`, `unit_cost`, `total_line_cost`, `created_at`, `updated_at`, `received_quantity`, `returned_quantity`) VALUES
('PI-1', 'PO-5001', 'PRD-1', 1, 180000.00, 180000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13', 0, 0),
('PI-2', 'PO-5002', 'PRD-2', 10, 12000.00, 120000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13', 0, 0),
('PI-9qC3AMIzG', 'PO-00006', 'PRD-5jgT4Xd4W', 6, 3000.00, 18000.00, '2026-03-13 11:17:18', '2026-03-13 11:17:39', 6, 2),
('PI-DxQaRW8Xe', 'PO-00002', 'PRD-5jgT4Xd4W', 4, 3000.00, 12000.00, '2026-03-11 15:52:44', '2026-03-11 15:52:49', 4, 0),
('PI-JT5omRmGy', 'PO-00006', 'PRD-i7pLh5tQD', 10, 90.00, 900.00, '2026-03-13 11:17:17', '2026-03-13 11:17:39', 10, 3),
('PI-LqlsG29kj', 'PO-00005', 'PRD-8kxXsCPl2', 5, 17000.00, 85000.00, '2026-03-11 15:56:19', '2026-03-11 15:56:27', 5, 0),
('PI-oLA9YYiBa', 'PO-00003', 'PRD-i7pLh5tQD', 5, 90.00, 450.00, '2026-03-11 15:54:31', '2026-03-11 15:54:35', 5, 0),
('PI-UjwLa9RF1', 'PO-00006', 'PRD-6H3ZUj4uZ', 4, 8000.00, 32000.00, '2026-03-13 11:17:17', '2026-03-13 11:17:39', 4, 2),
('PI-wTqcdn7xm', 'PO-00001', 'PRD-6H3ZUj4uZ', 4, 8000.00, 32000.00, '2026-03-11 15:17:28', '2026-03-13 11:15:53', 4, 2),
('PI-YnvDs1MUo', 'PO-00004', 'PRD-myEHgGJ1Y', 5, 3500.00, 17500.00, '2026-03-11 15:55:51', '2026-03-13 11:16:10', 5, 4);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `vendor_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT NULL,
  `received_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `return_status` varchar(20) NOT NULL DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `company_id`, `vendor_id`, `created_at`, `status`, `total_amount`, `updated_at`, `received_amount`, `return_status`) VALUES
('PO-00001', 'tenant-LG2rhNmVn', 'PT-QPyabfNOE', '2026-03-11 15:17:28', 'Received', 32000.00, '2026-03-13 11:15:53', 32000.00, 'partial'),
('PO-00002', 'tenant-LG2rhNmVn', 'PT-jA68knngN', '2026-03-11 15:52:44', 'Received', 12000.00, '2026-03-11 15:52:49', 12000.00, 'none'),
('PO-00003', 'tenant-LG2rhNmVn', 'PT-NP4rGBxRB', '2026-03-11 15:54:31', 'Received', 450.00, '2026-03-11 15:54:35', 450.00, 'none'),
('PO-00004', 'tenant-LG2rhNmVn', 'PT-wwfbF4mtR', '2026-03-11 15:55:51', 'Received', 17500.00, '2026-03-13 11:16:01', 17500.00, 'partial'),
('PO-00005', 'tenant-LG2rhNmVn', 'PT-CQ8hOaGmN', '2026-03-11 15:56:19', 'Received', 85000.00, '2026-03-11 15:56:27', 85000.00, 'none'),
('PO-00006', 'tenant-LG2rhNmVn', 'PT-jA68knngN', '2026-03-13 11:17:17', 'Received', 50900.00, '2026-03-13 11:17:39', 50900.00, 'partial'),
('PO-5001', 'tenant-1', 'PT-3', '2026-02-23 06:16:14', 'Received', 180000.00, '2026-02-23 06:16:14', 0.00, 'none'),
('PO-5002', 'tenant-1', 'PT-3', '2026-03-04 06:16:14', 'Draft', 120000.00, '2026-03-04 06:16:14', 0.00, 'none');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_receives`
--

CREATE TABLE `purchase_receives` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `purchase_order_id` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_receives`
--

INSERT INTO `purchase_receives` (`id`, `company_id`, `purchase_order_id`, `notes`, `created_at`, `updated_at`) VALUES
('RCV-3Hjdh2pgM', 'tenant-LG2rhNmVn', 'PO-00005', '', '2026-03-11 15:56:27', '2026-03-11 15:56:27'),
('RCV-b5tg2sO30', 'tenant-LG2rhNmVn', 'PO-00004', '', '2026-03-11 15:55:58', '2026-03-11 15:55:58'),
('RCV-BDqc3qmlk', 'tenant-LG2rhNmVn', 'PO-00002', '', '2026-03-11 15:52:49', '2026-03-11 15:52:49'),
('RCV-NyIE7SFR7', 'tenant-LG2rhNmVn', 'PO-00003', '', '2026-03-11 15:54:35', '2026-03-11 15:54:35'),
('RCV-UG5Bgq6M4', 'tenant-LG2rhNmVn', 'PO-00006', '', '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('RCV-V3wtWKG3J', 'tenant-LG2rhNmVn', 'PO-00001', '', '2026-03-11 15:17:33', '2026-03-11 15:17:33');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_receive_items`
--

CREATE TABLE `purchase_receive_items` (
  `id` varchar(255) NOT NULL,
  `purchase_receive_id` varchar(255) NOT NULL,
  `purchase_item_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_receive_items`
--

INSERT INTO `purchase_receive_items` (`id`, `purchase_receive_id`, `purchase_item_id`, `product_id`, `quantity`, `unit_cost`, `created_at`, `updated_at`) VALUES
('RCI-5KFjo6kOZ', 'RCV-UG5Bgq6M4', 'PI-UjwLa9RF1', 'PRD-6H3ZUj4uZ', 4, 8000.00, '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('RCI-eSHEuHahd', 'RCV-UG5Bgq6M4', 'PI-JT5omRmGy', 'PRD-i7pLh5tQD', 10, 90.00, '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('RCI-G0jdHNARY', 'RCV-UG5Bgq6M4', 'PI-9qC3AMIzG', 'PRD-5jgT4Xd4W', 6, 3000.00, '2026-03-13 11:17:22', '2026-03-13 11:17:22'),
('RCI-jaZV6pKg8', 'RCV-b5tg2sO30', 'PI-YnvDs1MUo', 'PRD-myEHgGJ1Y', 5, 3500.00, '2026-03-11 15:55:58', '2026-03-11 15:55:58'),
('RCI-lCJAgMRQs', 'RCV-BDqc3qmlk', 'PI-DxQaRW8Xe', 'PRD-5jgT4Xd4W', 4, 3000.00, '2026-03-11 15:52:49', '2026-03-11 15:52:49'),
('RCI-NGtJQGrvD', 'RCV-NyIE7SFR7', 'PI-oLA9YYiBa', 'PRD-i7pLh5tQD', 5, 90.00, '2026-03-11 15:54:35', '2026-03-11 15:54:35'),
('RCI-R7aagMAJx', 'RCV-3Hjdh2pgM', 'PI-LqlsG29kj', 'PRD-8kxXsCPl2', 5, 17000.00, '2026-03-11 15:56:27', '2026-03-11 15:56:27'),
('RCI-xFYVP5Sfo', 'RCV-V3wtWKG3J', 'PI-wTqcdn7xm', 'PRD-6H3ZUj4uZ', 4, 8000.00, '2026-03-11 15:17:33', '2026-03-11 15:17:33');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `original_purchase_id` varchar(255) NOT NULL,
  `vendor_id` varchar(255) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `company_id`, `original_purchase_id`, `vendor_id`, `total_amount`, `reason`, `created_at`, `updated_at`) VALUES
('DM-00001', 'tenant-LG2rhNmVn', 'PO-00001', 'PT-QPyabfNOE', 16000.00, '', '2026-03-13 11:15:53', '2026-03-13 11:15:53'),
('DM-00002', 'tenant-LG2rhNmVn', 'PO-00004', 'PT-wwfbF4mtR', 10500.00, '', '2026-03-13 11:16:01', '2026-03-13 11:16:01'),
('DM-00003', 'tenant-LG2rhNmVn', 'PO-00004', 'PT-wwfbF4mtR', 3500.00, '', '2026-03-13 11:16:10', '2026-03-13 11:16:10'),
('DM-00004', 'tenant-LG2rhNmVn', 'PO-00006', 'PT-jA68knngN', 22270.00, '', '2026-03-13 11:17:39', '2026-03-13 11:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` varchar(255) NOT NULL,
  `purchase_return_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_line_cost` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_return_items`
--

INSERT INTO `purchase_return_items` (`id`, `purchase_return_id`, `product_id`, `quantity`, `unit_cost`, `total_line_cost`, `created_at`, `updated_at`) VALUES
('PRI-Aovtt28KR', 'DM-00001', 'PRD-6H3ZUj4uZ', 2, 8000.00, 16000.00, '2026-03-13 11:15:53', '2026-03-13 11:15:53'),
('PRI-IjCtg10QI', 'DM-00004', 'PRD-i7pLh5tQD', 3, 90.00, 270.00, '2026-03-13 11:17:39', '2026-03-13 11:17:39'),
('PRI-iqipK8g7n', 'DM-00003', 'PRD-myEHgGJ1Y', 1, 3500.00, 3500.00, '2026-03-13 11:16:10', '2026-03-13 11:16:10'),
('PRI-qadAdoXg1', 'DM-00004', 'PRD-6H3ZUj4uZ', 2, 8000.00, 16000.00, '2026-03-13 11:17:39', '2026-03-13 11:17:39'),
('PRI-rmO9jv7MU', 'DM-00002', 'PRD-myEHgGJ1Y', 3, 3500.00, 10500.00, '2026-03-13 11:16:01', '2026-03-13 11:16:01'),
('PRI-ti60zykBD', 'DM-00004', 'PRD-5jgT4Xd4W', 2, 3000.00, 6000.00, '2026-03-13 11:17:39', '2026-03-13 11:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` varchar(255) NOT NULL,
  `sale_order_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_line_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cogs` decimal(15,2) NOT NULL DEFAULT 0.00,
  `returned_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_order_id`, `product_id`, `quantity`, `unit_price`, `discount`, `total_line_price`, `created_at`, `updated_at`, `cogs`, `returned_quantity`) VALUES
('SI-1', 'INV-1001', 'PRD-1', 1, 215000.00, 0.00, 215000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13', 0.00, 0),
('SI-2', 'INV-1002', 'PRD-2', 2, 18500.00, 0.00, 37000.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13', 0.00, 0),
('SI-3', 'INV-1003', 'PRD-5', 1, 1200.00, 0.00, 1200.00, '2026-03-05 06:16:13', '2026-03-05 06:16:13', 0.00, 0),
('SI-C9mEtjDQ4', 'INV-00005', 'PRD-8kxXsCPl2', 3, 20000.00, 0.00, 60000.00, '2026-03-13 09:17:45', '2026-03-13 09:17:45', 51000.00, 0),
('SI-gMjTJuX4d', 'INV-00003', 'PRD-i7pLh5tQD', 5, 150.00, 0.00, 750.00, '2026-03-11 15:54:55', '2026-03-11 15:54:55', 450.00, 0),
('SI-LB0MEFzTa', 'INV-00001', 'PRD-6H3ZUj4uZ', 4, 10000.00, 0.00, 40000.00, '2026-03-11 15:51:20', '2026-03-13 10:44:48', 32000.00, 2),
('SI-lJvF9VthN', 'INV-00004', 'PRD-myEHgGJ1Y', 5, 5000.00, 0.00, 25000.00, '2026-03-11 15:56:56', '2026-03-13 10:58:13', 17500.00, 2),
('SI-RfjLfKKJF', 'INV-00006', 'PRD-6H3ZUj4uZ', 1, 10000.00, 0.00, 10000.00, '2026-03-13 09:52:43', '2026-03-13 10:58:27', 8000.00, 1),
('SI-s4vSDeuk9', 'INV-00004', 'PRD-8kxXsCPl2', 5, 20000.00, 0.00, 100000.00, '2026-03-11 15:56:56', '2026-03-13 10:58:13', 85000.00, 3),
('SI-Tact2ZtoT', 'INV-00006', 'PRD-8kxXsCPl2', 1, 20000.00, 0.00, 20000.00, '2026-03-13 09:52:43', '2026-03-13 09:52:43', 17000.00, 0),
('SI-wMNPp70o1', 'INV-00002', 'PRD-5jgT4Xd4W', 4, 5000.00, 0.00, 20000.00, '2026-03-11 15:53:18', '2026-03-11 15:53:18', 12000.00, 0),
('SI-z5oKiW0ta', 'INV-00005', 'PRD-6H3ZUj4uZ', 3, 10000.00, 0.00, 30000.00, '2026-03-13 09:17:45', '2026-03-13 09:17:45', 24000.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sale_orders`
--

CREATE TABLE `sale_orders` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_returned` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  `return_status` varchar(20) NOT NULL DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_orders`
--

INSERT INTO `sale_orders` (`id`, `company_id`, `customer_id`, `created_at`, `payment_method`, `total_amount`, `is_returned`, `updated_at`, `return_status`) VALUES
('INV-00001', 'tenant-LG2rhNmVn', 'PT-30Y6cqxvR', '2026-03-11 15:51:20', 'Credit', 40000.00, 0, '2026-03-13 10:44:48', 'partial'),
('INV-00002', 'tenant-LG2rhNmVn', 'PT-anOvIqtkZ', '2026-03-11 15:53:18', 'Cash', 20000.00, 0, '2026-03-11 15:53:18', 'none'),
('INV-00003', 'tenant-LG2rhNmVn', 'PT-rng8MeeTU', '2026-03-11 15:54:55', 'Cash', 750.00, 0, '2026-03-11 15:54:55', 'none'),
('INV-00004', 'tenant-LG2rhNmVn', 'PT-ZqCOQjY00', '2026-03-11 15:56:56', 'Credit', 125000.00, 0, '2026-03-13 10:58:13', 'partial'),
('INV-00005', 'tenant-LG2rhNmVn', 'PT-30Y6cqxvR', '2026-03-13 09:17:45', 'Cash', 90000.00, 0, '2026-03-13 09:17:45', 'none'),
('INV-00006', 'tenant-LG2rhNmVn', 'PT-rng8MeeTU', '2026-03-13 09:52:43', 'Credit', 30000.00, 0, '2026-03-13 10:58:27', 'partial'),
('INV-1001', 'tenant-1', 'PT-1', '2026-03-01 06:16:14', 'Card', 215000.00, 0, '2026-03-01 06:16:14', 'none'),
('INV-1002', 'tenant-1', 'PT-2', '2026-03-03 06:16:14', 'Cash', 37000.00, 0, '2026-03-03 06:16:14', 'none'),
('INV-1003', 'tenant-1', NULL, '2026-03-04 06:16:14', 'Cash', 1200.00, 0, '2026-03-04 06:16:14', 'none');

-- --------------------------------------------------------

--
-- Table structure for table `sale_returns`
--

CREATE TABLE `sale_returns` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `original_sale_id` varchar(255) NOT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_returns`
--

INSERT INTO `sale_returns` (`id`, `company_id`, `original_sale_id`, `customer_id`, `total_amount`, `reason`, `created_at`, `updated_at`) VALUES
('CM-00001', 'tenant-LG2rhNmVn', 'INV-00001', 'PT-30Y6cqxvR', 20000.00, '', '2026-03-13 10:44:48', '2026-03-13 10:44:48'),
('CM-00002', 'tenant-LG2rhNmVn', 'INV-00004', 'PT-ZqCOQjY00', 70000.00, '', '2026-03-13 10:58:13', '2026-03-13 10:58:13'),
('CM-00003', 'tenant-LG2rhNmVn', 'INV-00006', 'PT-rng8MeeTU', 10000.00, '', '2026-03-13 10:58:27', '2026-03-13 10:58:27');

-- --------------------------------------------------------

--
-- Table structure for table `sale_return_items`
--

CREATE TABLE `sale_return_items` (
  `id` varchar(255) NOT NULL,
  `sale_return_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_line_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_return_items`
--

INSERT INTO `sale_return_items` (`id`, `sale_return_id`, `product_id`, `quantity`, `unit_price`, `discount`, `total_line_price`, `created_at`, `updated_at`) VALUES
('SRI-9bCjAOgLR', 'CM-00002', 'PRD-8kxXsCPl2', 3, 20000.00, 0.00, 60000.00, '2026-03-13 10:58:13', '2026-03-13 10:58:13'),
('SRI-9otshBKg8', 'CM-00001', 'PRD-6H3ZUj4uZ', 2, 10000.00, 0.00, 20000.00, '2026-03-13 10:44:48', '2026-03-13 10:44:48'),
('SRI-DI7OoqeTH', 'CM-00003', 'PRD-6H3ZUj4uZ', 1, 10000.00, 0.00, 10000.00, '2026-03-13 10:58:27', '2026-03-13 10:58:27'),
('SRI-nJCWcvqsK', 'CM-00002', 'PRD-myEHgGJ1Y', 2, 5000.00, 0.00, 10000.00, '2026-03-13 10:58:13', '2026-03-13 10:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'currency', 'Rs.', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
(2, 'invoiceFormat', 'A4', '2026-03-05 06:16:13', '2026-03-05 06:16:13');

-- --------------------------------------------------------

--
-- Table structure for table `units_of_measure`
--

CREATE TABLE `units_of_measure` (
  `id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units_of_measure`
--

INSERT INTO `units_of_measure` (`id`, `company_id`, `name`, `created_at`, `updated_at`) VALUES
('uom-1', 'tenant-1', 'Pcs', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('uom-2', 'tenant-1', 'Kg', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('uom-3', 'tenant-1', 'Box', '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('UOM-mQqUZdOLj', 'tenant-LG2rhNmVn', 'pcs', '2026-03-11 04:08:41', '2026-03-11 04:08:41'),
('UOM-O93sZhfni', 'tenant-LG2rhNmVn', 'kgs', '2026-03-11 04:23:05', '2026-03-11 04:23:05'),
('UOM-vgtDx0BzA', 'tenant-LG2rhNmVn', 'Dozen', '2026-03-11 04:24:02', '2026-03-11 04:24:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_plain` varchar(255) DEFAULT NULL,
  `api_token` varchar(80) DEFAULT NULL,
  `system_role` varchar(255) NOT NULL,
  `role_id` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `password_plain`, `api_token`, `system_role`, `role_id`, `company_id`, `is_active`, `created_at`, `updated_at`) VALUES
('sys-admin', 'superadmin', '$2y$12$Favkn0UB.fn2rbd7ufgvlOehumnJKhV0TzPJpSkEI/nO.mj.uG.ZK', NULL, 'Prg4vW4JkesVl9gXU37MK9y0gGzcNktQQKONeKoY7aEo49zvybiMzPXOUJNG', 'Super Admin', NULL, NULL, 1, '2026-03-05 06:16:13', '2026-03-13 12:32:50'),
('u1', 'admin', '$2y$12$i7KYRSp3ZHGcUnX7uZ49IewXVbEXK19E9ymPOfaaarJkGDILtmU7.', NULL, 'u708cbT4hJATMvvTYSdQynxDkU2xI4j0b1qHRBNBBo7B6LDkbTLAxWIFNlfr', 'Company Admin', NULL, 'tenant-1', 1, '2026-03-05 06:16:13', '2026-03-05 06:17:52'),
('u2', 'sales_user', '$2y$12$WvNjxBfM.hhvyxzw218dhOoP9sJyXiyl3cLYI575ITXpJqir1L.f2', NULL, NULL, 'Standard User', NULL, 'tenant-1', 1, '2026-03-05 06:16:13', '2026-03-05 06:16:13'),
('user-vbLB4h97F', 'Sami@ASlimited', '$2y$12$WfTkgUWKW2nUNmSGjdeTc.o3FQYXyyUlpsxkkA8DaNgy79FxgstuC', 'Sami123', NULL, 'Standard User', NULL, 'tenant-LG2rhNmVn', 1, '2026-03-13 12:11:30', '2026-03-13 12:11:30'),
('user-vSdLCYz4z', 'admin@AS', '$2y$12$XivM8jjVG/Hbr3D64Kj.U.Clrf06RzJtGUMstRQvRM1joQFSZH5oq', 'AS123', 'Hu5vopqUw0Ps1cC9kxgToFX1Jixds0ZRVmryIl4BjxjoEZxlUvvvK7E1Gxeo', 'Company Admin', NULL, 'tenant-LG2rhNmVn', 1, '2026-03-11 04:07:13', '2026-03-13 12:14:30'),
('user-vvkEG2oMe', 'Saad@ASlmited', '$2y$12$GBYt3FdJERy.eGaNegu1leoeRDgw4jQKhMUbr2zcOwgJjoxpHP4Zi', 'saad123', NULL, 'Standard User', NULL, 'tenant-LG2rhNmVn', 1, '2026-03-13 12:10:58', '2026-03-13 12:10:58'),
('user-W79oGMcny', 'Ali@ASLimited', '$2y$12$gO4aPSIQaZTFPd3XaS832OHY/3A2Iw/Cblud1t0f0cBXZR0Iqj2GW', 'Ali123', NULL, 'Standard User', 'ROLE-cPbX8H813', 'tenant-LG2rhNmVn', 1, '2026-03-13 10:01:59', '2026-03-13 10:01:59'),
('user-xOpoW6s9I', 'Fazeel@ASlimited', '$2y$12$jlW.C0h/w75/FPd8f5/GK.SxL/0kwmz0FGUmQ6QFnpTCsv1haE2l2', '13579', NULL, 'Standard User', NULL, 'tenant-LG2rhNmVn', 1, '2026-03-13 12:12:01', '2026-03-13 12:12:01'),
('user-YVwzYtrg6', 'admin@sa', '$2y$12$sQ5AUfLi9fFqvfooGmkjkeGM.jxidiI./Ksn9MPZYTk/CKmDy5sLW', 'sa123', 'lhMiLkU6NLiPGgYBuDJKimBU0ijtGT5NPUapPmDOT8916lKvw9QYMI7D3WMe', 'Company Admin', NULL, 'tenant-2PofxxGvE', 1, '2026-03-11 15:34:01', '2026-03-11 15:34:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `business_categories`
--
ALTER TABLE `business_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categories_company_id_foreign` (`company_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_roles`
--
ALTER TABLE `custom_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `custom_roles_company_id_foreign` (`company_id`);

--
-- Indexes for table `document_sequences`
--
ALTER TABLE `document_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_sequences_company_id_type_unique` (`company_id`,`type`);

--
-- Indexes for table `entity_types`
--
ALTER TABLE `entity_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_cost_layers`
--
ALTER TABLE `inventory_cost_layers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_cost_layers_company_id_foreign` (`company_id`),
  ADD KEY `inventory_cost_layers_product_id_foreign` (`product_id`);

--
-- Indexes for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_ledger_company_id_foreign` (`company_id`),
  ADD KEY `inventory_ledger_product_id_foreign` (`product_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parties_company_id_foreign` (`company_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_company_id_foreign` (`company_id`),
  ADD KEY `payments_party_id_foreign` (`party_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_company_barcode_unique` (`company_id`,`barcode`),
  ADD KEY `products_category_id_foreign` (`category_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_items_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_orders_company_id_foreign` (`company_id`),
  ADD KEY `purchase_orders_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `purchase_receives`
--
ALTER TABLE `purchase_receives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_receives_company_id_foreign` (`company_id`),
  ADD KEY `purchase_receives_purchase_order_id_foreign` (`purchase_order_id`);

--
-- Indexes for table `purchase_receive_items`
--
ALTER TABLE `purchase_receive_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_receive_items_purchase_receive_id_foreign` (`purchase_receive_id`),
  ADD KEY `purchase_receive_items_purchase_item_id_foreign` (`purchase_item_id`),
  ADD KEY `purchase_receive_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_returns_company_id_foreign` (`company_id`),
  ADD KEY `purchase_returns_original_purchase_id_foreign` (`original_purchase_id`),
  ADD KEY `purchase_returns_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_return_items_purchase_return_id_foreign` (`purchase_return_id`),
  ADD KEY `purchase_return_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_items_sale_order_id_foreign` (`sale_order_id`),
  ADD KEY `sale_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `sale_orders`
--
ALTER TABLE `sale_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_orders_company_id_foreign` (`company_id`),
  ADD KEY `sale_orders_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_returns_company_id_foreign` (`company_id`),
  ADD KEY `sale_returns_original_sale_id_foreign` (`original_sale_id`),
  ADD KEY `sale_returns_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  ADD KEY `sale_return_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `units_of_measure`
--
ALTER TABLE `units_of_measure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `units_of_measure_company_id_foreign` (`company_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_api_token_unique` (`api_token`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_company_id_foreign` (`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_roles`
--
ALTER TABLE `custom_roles`
  ADD CONSTRAINT `custom_roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_sequences`
--
ALTER TABLE `document_sequences`
  ADD CONSTRAINT `document_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_cost_layers`
--
ALTER TABLE `inventory_cost_layers`
  ADD CONSTRAINT `inventory_cost_layers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_cost_layers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD CONSTRAINT `inventory_ledger_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ledger_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parties`
--
ALTER TABLE `parties`
  ADD CONSTRAINT `parties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_receives`
--
ALTER TABLE `purchase_receives`
  ADD CONSTRAINT `purchase_receives_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_receives_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_receive_items`
--
ALTER TABLE `purchase_receive_items`
  ADD CONSTRAINT `purchase_receive_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_receive_items_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_receive_items_purchase_receive_id_foreign` FOREIGN KEY (`purchase_receive_id`) REFERENCES `purchase_receives` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `purchase_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_returns_original_purchase_id_foreign` FOREIGN KEY (`original_purchase_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_returns_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `purchase_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_return_items_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_sale_order_id_foreign` FOREIGN KEY (`sale_order_id`) REFERENCES `sale_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_orders`
--
ALTER TABLE `sale_orders`
  ADD CONSTRAINT `sale_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_returns`
--
ALTER TABLE `sale_returns`
  ADD CONSTRAINT `sale_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sale_returns_original_sale_id_foreign` FOREIGN KEY (`original_sale_id`) REFERENCES `sale_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_return_items`
--
ALTER TABLE `sale_return_items`
  ADD CONSTRAINT `sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `units_of_measure`
--
ALTER TABLE `units_of_measure`
  ADD CONSTRAINT `units_of_measure_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `custom_roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

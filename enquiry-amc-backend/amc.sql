-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 08:43 PM
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
-- Database: `amc`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `employee_number` varchar(50) NOT NULL,
  `contact_no` varchar(15) NOT NULL,
  `email_id` varchar(100) DEFAULT NULL,
  `last_login_time` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_name`, `employee_number`, `contact_no`, `email_id`, `last_login_time`, `password`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(2, 'Admin', 'E01', '9876543210', 'adminuser@gmail.com', '2025-07-22 00:12:00', 'Test@123', 1, 'Admin', NULL, '2025-07-13 13:14:59', '2025-07-22 00:12:00'),
(4, 'Hemanand', 'E02', '9791005608', 'phemsin@gmail.com', NULL, 'Initial@123', 1, 'Admin', 'Admin', '2025-07-14 22:15:50', '2025-07-18 02:45:48'),
(6, 'Arun', 'E03', '9876543210', 'arun123@gmail.com', NULL, 'Arun@123', 1, 'Admin', NULL, '2025-07-21 23:09:04', '2025-07-21 23:09:04'),
(8, 'Nehru', 'E04', '8907654321', 'nehru123@gmail.com', NULL, 'Nehru@123', 1, 'Admin', NULL, '2025-07-21 23:11:37', '2025-07-21 23:11:37');

-- --------------------------------------------------------

--
-- Table structure for table `employee_roles`
--

CREATE TABLE `employee_roles` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_roles`
--

INSERT INTO `employee_roles` (`id`, `employee_id`, `role_id`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 'Admin', 'Admin', '2025-07-14 23:36:03', '2025-07-14 23:36:03'),
(3, 6, 3, 'Admin', 'Admin', '2025-07-21 23:18:53', '2025-07-21 23:18:53'),
(4, 8, 3, 'Admin', 'Admin', '2025-07-21 23:19:19', '2025-07-21 23:19:19');

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL,
  `enquiry_id` varchar(20) DEFAULT NULL,
  `client_name` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `contact_no1` varchar(15) NOT NULL,
  `contact_no2` varchar(15) DEFAULT NULL,
  `email_id` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `requirement` text NOT NULL,
  `requirement_category` varchar(100) NOT NULL,
  `source_of_enquiry` varchar(100) DEFAULT NULL,
  `enquiry_date` date DEFAULT NULL,
  `enquiry_status_id` int(11) NOT NULL,
  `follow_up_date` date DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `delivered_date` date DEFAULT NULL,
  `requested_delivery_date` date DEFAULT NULL,
  `amc_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`id`, `enquiry_id`, `client_name`, `contact_person_name`, `contact_no1`, `contact_no2`, `email_id`, `address`, `requirement`, `requirement_category`, `source_of_enquiry`, `enquiry_date`, `enquiry_status_id`, `follow_up_date`, `follow_up_notes`, `delivered_date`, `requested_delivery_date`, `amc_date`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'EQ001', 'ABC Corp', 'John Doe', '9876543210', '0123456789', 'john@example.com', '123 Industrial Area', 'Need 2 industrial pumps', 'Pumps', 'Website', '0000-00-00', 1, '2025-06-11', 'Initial discussion done.', '2025-08-08', '2025-06-20', '2025-12-20', 1, 'admin', 'Admin', '2025-07-13 19:39:08', '2025-07-22 00:09:55'),
(3, 'EQ003', 'ECS', 'Venkatram', '9876543210', '9876543210', 'venkat12@gmail.com', 'Chennai', 'Need of two units', 'Refilling ', 'Website', '0000-00-00', 1, '2025-07-31', 'New Enquiry', '2025-07-31', '2025-07-20', '2025-07-31', 1, 'Admin', 'Admin', '2025-07-14 22:22:41', '2025-07-22 00:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_followups`
--

CREATE TABLE `enquiry_followups` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `follow_up_date` date NOT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiry_followups`
--

INSERT INTO `enquiry_followups` (`id`, `enquiry_id`, `follow_up_date`, `follow_up_notes`, `created_at`) VALUES
(1, 1, '2025-06-11', 'Initial discussion done.', '2025-07-13 19:39:08'),
(3, 3, '2025-07-31', 'New Enquiry', '2025-07-14 22:22:41'),
(4, 3, '2025-07-31', 'New Enquiry', '2025-07-18 02:03:35'),
(5, 3, '2025-07-31', 'New Enquiry', '2025-07-18 02:08:43'),
(6, 3, '2025-07-31', 'New Enquiry', '2025-07-18 22:20:27'),
(7, 3, '2025-07-31', 'New Enquiry', '2025-07-21 23:49:11'),
(8, 3, '2025-07-31', 'New Enquiry', '2025-07-21 23:53:35'),
(9, 3, '2025-07-31', 'New Enquiry', '2025-07-22 00:02:43'),
(10, 1, '2025-06-11', 'Initial discussion done.', '2025-07-22 00:03:20'),
(11, 1, '2025-06-11', 'Initial discussion done.', '2025-07-22 00:04:01'),
(12, 1, '2025-06-11', 'Initial discussion done.', '2025-07-22 00:06:44'),
(13, 1, '2025-06-11', 'Initial discussion done.', '2025-07-22 00:09:55'),
(14, 3, '2025-07-31', 'New Enquiry', '2025-07-22 00:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_status`
--

CREATE TABLE `enquiry_status` (
  `id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiry_status`
--

INSERT INTO `enquiry_status` (`id`, `status_name`, `created_at`) VALUES
(1, 'Enquiry list', '2025-07-13 08:34:36'),
(2, 'Quotation sent', '2025-07-13 08:34:36'),
(3, 'In Progress', '2025-07-13 08:34:36'),
(4, 'Order confirmed', '2025-07-13 08:34:36'),
(5, 'Order delivered', '2025-07-13 08:34:36'),
(6, 'Order dropped', '2025-07-13 08:34:36'),
(7, 'Not interested', '2025-07-13 08:34:36'),
(8, 'Customer not reachable', '2025-07-13 08:34:36');

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_technician_map`
--

CREATE TABLE `enquiry_technician_map` (
  `id` int(11) NOT NULL,
  `enquiry_id` varchar(20) NOT NULL,
  `technician_employee_id` int(11) NOT NULL,
  `assigned_date` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `created_at`) VALUES
(1, 'Admin', '2025-07-13 08:27:53'),
(2, 'Manager', '2025-07-13 08:27:53'),
(3, 'Technician', '2025-07-13 08:27:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`);

--
-- Indexes for table `employee_roles`
--
ALTER TABLE `employee_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `enquiry_status_id` (`enquiry_status_id`);

--
-- Indexes for table `enquiry_followups`
--
ALTER TABLE `enquiry_followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enquiry_id` (`enquiry_id`);

--
-- Indexes for table `enquiry_status`
--
ALTER TABLE `enquiry_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `enquiry_technician_map`
--
ALTER TABLE `enquiry_technician_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `technician_employee_id` (`technician_employee_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employee_roles`
--
ALTER TABLE `employee_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enquiry_followups`
--
ALTER TABLE `enquiry_followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `enquiry_status`
--
ALTER TABLE `enquiry_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `enquiry_technician_map`
--
ALTER TABLE `enquiry_technician_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_roles`
--
ALTER TABLE `employee_roles`
  ADD CONSTRAINT `employee_roles_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD CONSTRAINT `enquiries_ibfk_1` FOREIGN KEY (`enquiry_status_id`) REFERENCES `enquiry_status` (`id`);

--
-- Constraints for table `enquiry_followups`
--
ALTER TABLE `enquiry_followups`
  ADD CONSTRAINT `enquiry_followups_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enquiry_technician_map`
--
ALTER TABLE `enquiry_technician_map`
  ADD CONSTRAINT `enquiry_technician_map_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`enquiry_id`),
  ADD CONSTRAINT `enquiry_technician_map_ibfk_2` FOREIGN KEY (`technician_employee_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

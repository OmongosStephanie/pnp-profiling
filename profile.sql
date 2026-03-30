-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 03:37 AM
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
-- Database: `profile`
--

-- --------------------------------------------------------

--
-- Table structure for table `biographical_profiles`
--

CREATE TABLE `biographical_profiles` (
  `id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `alias` varchar(200) DEFAULT NULL,
  `group_affiliation` varchar(200) DEFAULT NULL,
  `position_roles` text DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `pob` varchar(200) DEFAULT NULL,
  `educational_attainment` varchar(100) DEFAULT NULL,
  `occupation` text DEFAULT NULL,
  `company_office` varchar(200) DEFAULT NULL,
  `technical_skills` text DEFAULT NULL,
  `ethnic_group` varchar(100) DEFAULT NULL,
  `languages` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `provincial_address` text DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_ft` varchar(10) DEFAULT NULL,
  `eyes_color` varchar(50) DEFAULT NULL,
  `hair_color` varchar(50) DEFAULT NULL,
  `built` enum('Small','Medium','Large') DEFAULT NULL,
  `complexion` varchar(50) DEFAULT NULL,
  `distinguishing_marks` text DEFAULT NULL,
  `previous_arrest_record` text DEFAULT NULL,
  `previous_arrest` text DEFAULT NULL,
  `specific_charge` text DEFAULT NULL,
  `arresting_officer` varchar(200) DEFAULT NULL,
  `arresting_unit` varchar(200) DEFAULT NULL,
  `date_time_place_of_arrest` text DEFAULT NULL,
  `arresting_officer_name` varchar(255) DEFAULT NULL,
  `arresting_officer_unit` varchar(255) DEFAULT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `father_address` text DEFAULT NULL,
  `father_dob` date DEFAULT NULL,
  `father_pob` varchar(200) DEFAULT NULL,
  `father_age` int(11) DEFAULT NULL,
  `father_occupation` varchar(200) DEFAULT NULL,
  `mother_name` varchar(200) DEFAULT NULL,
  `mother_address` text DEFAULT NULL,
  `mother_dob` date DEFAULT NULL,
  `mother_pob` varchar(200) DEFAULT NULL,
  `mother_age` int(11) DEFAULT NULL,
  `mother_occupation` varchar(200) DEFAULT NULL,
  `spouse_name` varchar(200) DEFAULT NULL,
  `spouse_age` int(11) DEFAULT NULL,
  `spouse_occupation` varchar(200) DEFAULT NULL,
  `spouse_address` text DEFAULT NULL,
  `drugs_involved` text DEFAULT NULL,
  `source_relationship` varchar(200) DEFAULT NULL,
  `source_address` varchar(200) DEFAULT NULL,
  `source_name` varchar(200) DEFAULT NULL,
  `source_nickname` varchar(100) DEFAULT NULL,
  `source_full_address` text DEFAULT NULL,
  `source_other_drugs` varchar(200) DEFAULT NULL,
  `subgroup_name` varchar(200) DEFAULT NULL,
  `specific_aor` varchar(200) DEFAULT NULL,
  `other_source_name` varchar(200) DEFAULT NULL,
  `other_source_alias` varchar(100) DEFAULT NULL,
  `other_source_details` text DEFAULT NULL,
  `drugs_pushed` text DEFAULT NULL,
  `other_drugs_pushed` varchar(200) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `vehicles_used` text DEFAULT NULL,
  `armaments` text DEFAULT NULL,
  `companions_arrest` text DEFAULT NULL,
  `recruitment_summary` text DEFAULT NULL,
  `modus_operandi` text DEFAULT NULL,
  `organizational_structure` text DEFAULT NULL,
  `ci_matters` text DEFAULT NULL,
  `other_revelations` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','archived','delisted') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `biographical_profiles`
--

INSERT INTO `biographical_profiles` (`id`, `full_name`, `alias`, `group_affiliation`, `position_roles`, `age`, `sex`, `dob`, `pob`, `educational_attainment`, `occupation`, `company_office`, `technical_skills`, `ethnic_group`, `languages`, `present_address`, `provincial_address`, `civil_status`, `citizenship`, `religion`, `height_cm`, `weight_kg`, `height_ft`, `eyes_color`, `hair_color`, `built`, `complexion`, `distinguishing_marks`, `previous_arrest_record`, `previous_arrest`, `specific_charge`, `arresting_officer`, `arresting_unit`, `date_time_place_of_arrest`, `arresting_officer_name`, `arresting_officer_unit`, `father_name`, `father_address`, `father_dob`, `father_pob`, `father_age`, `father_occupation`, `mother_name`, `mother_address`, `mother_dob`, `mother_pob`, `mother_age`, `mother_occupation`, `spouse_name`, `spouse_age`, `spouse_occupation`, `spouse_address`, `drugs_involved`, `source_relationship`, `source_address`, `source_name`, `source_nickname`, `source_full_address`, `source_other_drugs`, `subgroup_name`, `specific_aor`, `other_source_name`, `other_source_alias`, `other_source_details`, `drugs_pushed`, `other_drugs_pushed`, `profile_picture`, `vehicles_used`, `armaments`, `companions_arrest`, `recruitment_summary`, `modus_operandi`, `organizational_structure`, `ci_matters`, `other_revelations`, `recommendation`, `created_by`, `created_at`, `updated_at`, `status`) VALUES
(3, 'Stephanie Omongos', 'teptep', 'none', 'User, Pusher', 21, 'Female', '2004-09-07', 'lingion', 'College Level', 'none', 'none', '', '', '', 'lingion manolo fortich bukidnon', 'bukidnon', 'Single', 'Filipino', 'roman catholic', NULL, 60.00, '5\'3', '', '', 'Small', '', 'none', NULL, '', '', '', '', '2017-06-10T05:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', 'none', 'none', '', '', 'none', 'none', '', '', '', '', 'Shabu, Marijuana', '', 'uploads/1773622278_CANSHN Magnetic Compatible with iPhone 15 Plus Case, Upgraded [Full Camera Protection] [Compatible with Magsafe] [Translucent Matte] Shockproof.jpg', 'none', 'none', '', 'none', 'none', 'NO ORGANIZATIONAL GROUP', 'NONE', '', 'For Investigation', 1, '2026-03-16 01:26:10', '2026-03-24 09:39:01', 'active'),
(4, 'JOHN FRICH MAGAN', 'FRICH', 'NONE', 'Pusher, Runner', 22, 'Male', '2003-06-10', 'MANTIBUGAO', 'College Graduate', 'unemployed', 'none', 'NONE', 'NONE', 'BISAYA', 'MANTIBUGAO, MANOLO FORTICH,BUKIDNON', 'BUKIDNON', 'Single', 'Filipino', 'ROMAN CATHOLIC', NULL, 60.00, '5\'6', 'BLACK', 'BLACK', 'Small', 'NONE', 'NONE', NULL, '', 'NONE', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'HGDYUSGDYUD', 'KJDUIYWDUI', 'DMEJKHD', 'EKJUR3YENWJKE', 'KJDWIOUE', '', 'NONE', 'DSDFD', 'DSEFRER', 1, '2026-03-18 03:19:37', '2026-03-18 03:19:37', 'active');

--
-- Triggers `biographical_profiles`
--
DELIMITER $$
CREATE TRIGGER `sync_arrest_fields` BEFORE INSERT ON `biographical_profiles` FOR EACH ROW BEGIN
    SET NEW.date_time_place_of_arrest = CONCAT(
        IFNULL(DATE_FORMAT(NEW.arrest_datetime, '%Y-%m-%d %H:%i'), ''),
        IF(NEW.arrest_datetime IS NOT NULL AND NEW.arrest_place IS NOT NULL, ' - ', ''),
        IFNULL(NEW.arrest_place, '')
    );
    SET NEW.previous_arrest_record = NEW.previous_arrest;
    SET NEW.arresting_officer_name = NEW.arresting_officer;
    SET NEW.arresting_officer_unit = NEW.arresting_unit;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `siblings`
--

CREATE TABLE `siblings` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `occupation` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siblings`
--

INSERT INTO `siblings` (`id`, `profile_id`, `name`, `age`, `occupation`, `status`, `address`) VALUES
(1, 4, 'NBHJSDI', 21, 'NCHJDSGF', 'MDNSJHDE', 'DMNJKHD');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `rank` varchar(50) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `role` enum('admin','investigator','viewer') DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `rank`, `unit`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$lSORDmWp7cnwDExxO9sBP.LCG8V/7tXhH6V65z3/XhQcNeN1dR6Kq', 'Admin User', 'Police Captain', 'Manolo Fortich Police Station', 'admin', '2026-03-12 01:05:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `biographical_profiles`
--
ALTER TABLE `biographical_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `siblings`
--
ALTER TABLE `siblings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_id` (`profile_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `biographical_profiles`
--
ALTER TABLE `biographical_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `siblings`
--
ALTER TABLE `siblings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `biographical_profiles`
--
ALTER TABLE `biographical_profiles`
  ADD CONSTRAINT `biographical_profiles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `siblings`
--
ALTER TABLE `siblings`
  ADD CONSTRAINT `siblings_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `biographical_profiles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

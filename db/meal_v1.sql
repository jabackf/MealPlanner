-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2017 at 12:55 AM
-- Server version: 10.1.21-MariaDB
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meal`
--

-- --------------------------------------------------------

--
-- Table structure for table `calendars`
--

CREATE TABLE `calendars` (
  `calendarId` int(6) UNSIGNED NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `calendars`
--

INSERT INTO `calendars` (`calendarId`, `name`) VALUES
(2, 'calendar');

-- --------------------------------------------------------

--
-- Table structure for table `foodgroups`
--

CREATE TABLE `foodgroups` (
  `groupId` int(6) UNSIGNED NOT NULL,
  `name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `foodgroups`
--

INSERT INTO `foodgroups` (`groupId`, `name`) VALUES
(1, 'Grains/Bread'),
(2, 'Meat/Meat Alternate'),
(3, 'Fruit/Vegetable'),
(4, 'Milk');

-- --------------------------------------------------------

--
-- Table structure for table `fooditems`
--

CREATE TABLE `fooditems` (
  `foodId` int(6) UNSIGNED NOT NULL,
  `name` varchar(90) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fooditems`
--

INSERT INTO `fooditems` (`foodId`, `name`) VALUES
(1, 'Spaghetti'),
(2, 'Hot Dogs'),
(3, 'Corn Dogs'),
(4, 'Steamed Veggies'),
(5, 'Chicken Nuggets'),
(6, 'PB&J'),
(7, 'Peas'),
(8, 'Milk'),
(9, 'Yogurt'),
(10, 'Juice'),
(11, 'Cereal'),
(12, 'Toast'),
(13, 'Cornbread'),
(14, 'Cooked Dry Beans'),
(15, 'Fish Sticks'),
(16, 'Apples');

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `foodId` int(6) UNSIGNED DEFAULT NULL,
  `groupId` int(6) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`foodId`, `groupId`) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 2),
(3, 1),
(4, 3),
(5, 2),
(6, 1),
(6, 2),
(7, 3),
(8, 4),
(9, 2),
(10, 3),
(11, 1),
(12, 1),
(13, 1),
(14, 2),
(15, 2),
(16, 3);

-- --------------------------------------------------------

--
-- Table structure for table `mealitems`
--

CREATE TABLE `mealitems` (
  `id` int(6) UNSIGNED NOT NULL,
  `mealTypeId` int(6) UNSIGNED DEFAULT NULL,
  `foodId` int(6) UNSIGNED DEFAULT NULL,
  `date` date DEFAULT NULL,
  `calendarId` int(6) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `mealitems`
--

INSERT INTO `mealitems` (`id`, `mealTypeId`, `foodId`, `date`, `calendarId`) VALUES
(70, 1, 8, '2017-10-25', 2),
(71, 1, 10, '2017-10-25', 2),
(72, 1, 1, '2017-10-25', 2),
(73, 1, 6, '2017-10-25', 2),
(78, 3, 3, '2017-10-22', 2),
(79, 3, 3, '2017-10-22', 2),
(84, 5, 6, '2017-10-22', 2),
(85, 5, 2, '2017-10-22', 2),
(86, 5, 4, '2017-10-22', 2),
(96, 1, 2, '2017-10-22', 2),
(121, 1, 9, '2017-10-23', 2),
(122, 1, 6, '2017-10-23', 2),
(123, 1, 14, '2017-10-23', 2),
(124, 1, 2, '2017-10-23', 2),
(125, 2, 6, '2017-10-23', 2),
(126, 2, 15, '2017-10-23', 2),
(127, 2, 2, '2017-10-23', 2),
(128, 3, 9, '2017-10-23', 2),
(129, 3, 15, '2017-10-23', 2),
(130, 3, 2, '2017-10-23', 2),
(131, 4, 15, '2017-10-23', 2),
(132, 4, 2, '2017-10-23', 2),
(133, 4, 8, '2017-10-23', 2),
(134, 5, 15, '2017-10-23', 2),
(135, 5, 2, '2017-10-23', 2),
(136, 5, 8, '2017-10-23', 2),
(137, 5, 8, '2017-10-23', 2),
(138, 1, 10, '2017-10-24', 2);

-- --------------------------------------------------------

--
-- Table structure for table `mealtypes`
--

CREATE TABLE `mealtypes` (
  `mealTypeId` int(6) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `mealtypes`
--

INSERT INTO `mealtypes` (`mealTypeId`, `type`) VALUES
(1, 'Breakfast'),
(2, 'AM'),
(3, 'Lunch'),
(4, 'PM'),
(5, 'Dinner');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user` varchar(60) NOT NULL,
  `passwordHash` char(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calendars`
--
ALTER TABLE `calendars`
  ADD PRIMARY KEY (`calendarId`);

--
-- Indexes for table `foodgroups`
--
ALTER TABLE `foodgroups`
  ADD PRIMARY KEY (`groupId`);

--
-- Indexes for table `fooditems`
--
ALTER TABLE `fooditems`
  ADD PRIMARY KEY (`foodId`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD KEY `foodId` (`foodId`),
  ADD KEY `groupId` (`groupId`);

--
-- Indexes for table `mealitems`
--
ALTER TABLE `mealitems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mealTypeId` (`mealTypeId`),
  ADD KEY `foodId` (`foodId`),
  ADD KEY `calendarId` (`calendarId`);

--
-- Indexes for table `mealtypes`
--
ALTER TABLE `mealtypes`
  ADD PRIMARY KEY (`mealTypeId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calendars`
--
ALTER TABLE `calendars`
  MODIFY `calendarId` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `foodgroups`
--
ALTER TABLE `foodgroups`
  MODIFY `groupId` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `fooditems`
--
ALTER TABLE `fooditems`
  MODIFY `foodId` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `mealitems`
--
ALTER TABLE `mealitems`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;
--
-- AUTO_INCREMENT for table `mealtypes`
--
ALTER TABLE `mealtypes`
  MODIFY `mealTypeId` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`foodId`) REFERENCES `fooditems` (`foodId`),
  ADD CONSTRAINT `foods_ibfk_2` FOREIGN KEY (`groupId`) REFERENCES `foodgroups` (`groupId`);

--
-- Constraints for table `mealitems`
--
ALTER TABLE `mealitems`
  ADD CONSTRAINT `mealitems_ibfk_1` FOREIGN KEY (`mealTypeId`) REFERENCES `mealtypes` (`mealTypeId`),
  ADD CONSTRAINT `mealitems_ibfk_2` FOREIGN KEY (`foodId`) REFERENCES `fooditems` (`foodId`),
  ADD CONSTRAINT `mealitems_ibfk_3` FOREIGN KEY (`calendarId`) REFERENCES `calendars` (`calendarId`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

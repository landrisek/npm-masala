-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Host: 77.78.105.137:3306
-- Generation Time: Jun 26, 2017 at 09:18 PM
-- Server version: 5.6.33
-- PHP Version: 5.2.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cz_4camping`
--

-- --------------------------------------------------------

--
-- Table structure for table `fc_write`
--

CREATE TABLE IF NOT EXISTS `write` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_czech_ci COMMENT '@hidden@cke3',
  `language` enum('en_GB','sk_SK','cs_CZ') CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL DEFAULT 'cs_CZ' COMMENT '@hidden',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `fc_write`
--

INSERT INTO `fc_write` (`id`, `name`, `content`, `language`) VALUES
(4, 'kalhoty', '{"0":{"0":"Pánské","1":"Dámské","2":"Dětské","type":"select"},"2":{"0":"outdoorové","1":"městské","type":"select"},"3":"kalhoty.","plain":"","select":""}\n', 'cs_CZ'),
(5, 'boty', '{"0":{"0":"Pánské","1":"Dámské","type":"select"},"1":"outdoorová kotníková obuv."}', 'cs_CZ');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

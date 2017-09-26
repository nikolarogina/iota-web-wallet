-- phpMyAdmin SQL Dump
-- version 4.0.10.14
-- http://www.phpmyadmin.net
-- PHP version: 5.6.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


-- --------------------------------------------------------

--
-- Table structure for table `nodes`
--

CREATE TABLE IF NOT EXISTS `nodes` (
  `id` varchar(4) NOT NULL,
  `name` varchar(64) NOT NULL,
  `url` varchar(128) NOT NULL,
  `port` mediumint(6) NOT NULL,
  `type` varchar(6) NOT NULL DEFAULT 'http',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pow_time`
--

CREATE TABLE IF NOT EXISTS `pow_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` mediumint(8) DEFAULT NULL,
  `device` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `system`
--

CREATE TABLE IF NOT EXISTS `system` (
  `id` smallint(2) NOT NULL,
  `recaptcha_sitekey` varchar(128) NOT NULL,
  `recaptcha_secretkey` varchar(128) NOT NULL,
  `g_analytics` text NOT NULL,
  `avg_pow_time` text,
  `donation_address` varchar(243) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `user` varchar(8) NOT NULL,
  `created` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(8) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `seed` varchar(512) NOT NULL,
  `hint` varchar(64) DEFAULT '-',
  `pin_type` varchar(8) NOT NULL DEFAULT 'simple',
  `hash` varchar(128) DEFAULT NULL,
  `joined` datetime NOT NULL,
  `joined_ip` varchar(16) NOT NULL,
  `last_login` datetime NOT NULL,
  `last_login_ip` varchar(16) DEFAULT NULL,
  `failed_login` smallint(2) NOT NULL DEFAULT '0',
  `level` tinyint(1) NOT NULL DEFAULT '3',
  `status` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

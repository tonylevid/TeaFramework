-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2014 年 05 月 20 日 10:07
-- 服务器版本: 5.5.24-log
-- PHP 版本: 5.3.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `test`
--

-- --------------------------------------------------------

--
-- 表的结构 `test`
--

CREATE TABLE IF NOT EXISTS `test` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` char(32) NOT NULL DEFAULT '',
  `hits` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

--
-- 转存表中的数据 `test`
--

INSERT INTO `test` (`id`, `name`, `hits`) VALUES
(1, 'tonylevid', 802),
(2, 'tony', 263),
(4, 'tony', 263),
(5, 'tony', 263),
(6, 'tony', 263),
(7, 'tony', 263),
(8, 'tony', 263),
(9, 'tony', 263),
(10, 'tony', 263),
(11, 'tony', 313),
(12, 'tony', 313),
(13, 'tonylevid', 313),
(14, 'tonylevid', 313),
(15, 'tonylevid', 313),
(16, 'tonylevid', 313),
(17, 'tonylevi', 313),
(18, 'tonylevi', 313),
(19, 'tonylevi', 313),
(20, 'tonylevi', 313),
(21, 'tonylevi', 313),
(22, 'tonylevi', 313),
(23, 'tonylevi', 313),
(24, 'tonylevi', 313),
(25, 'tonylevi', 313);

-- --------------------------------------------------------

--
-- 表的结构 `test_detail`
--

CREATE TABLE IF NOT EXISTS `test_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `addr` varchar(255) NOT NULL DEFAULT '',
  `qq` int(15) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `test_detail`
--

INSERT INTO `test_detail` (`id`, `parent_id`, `addr`, `qq`) VALUES
(1, 1, 'foo city bar street', 123456);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

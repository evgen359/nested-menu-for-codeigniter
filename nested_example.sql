-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Anamakine: localhost
-- Üretim Zamanı: 31 Mayıs 2012 saat 21:14:35
-- Sunucu sürümü: 5.5.9
-- PHP Sürümü: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Veritabanı: `nested_example`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21 ;

--
-- Tablo döküm verisi `pages`
--

INSERT INTO `pages` VALUES(10, 137, 'denemelik bir yazi2', '<p><span style="text-decoration: line-through;">asdasd</span> as<em></em> dasd<em>da das</em> as<strong>d asd asd a</strong></p>');
INSERT INTO `pages` VALUES(11, 137, 'adsasgha', '<p>asdfa<span style="text-decoration: line-through;"><em>s as</em></span><span style="text-decoration: underline;"><strong>d asdas asd</strong></span></p>');
INSERT INTO `pages` VALUES(12, 131, 'testas åäöÅÄÖ', '<p>teadasfg</p>');
INSERT INTO `pages` VALUES(13, 131, 'babababa', '<p>babababab</p>');
INSERT INTO `pages` VALUES(14, 131, 'babasdad', '<p>basdagagfsdgsgsdf</p>');
INSERT INTO `pages` VALUES(15, 128, 'asdabafsadas', '<p>abanafasdvbsdfsdfsd sdfsdf sdg qgagbasdh</p>');
INSERT INTO `pages` VALUES(16, 128, 'bsgdsdbsbsdbsd', '<p>dhbsgsdfsdgshshs</p>');
INSERT INTO `pages` VALUES(17, 127, 'shbsbsvsdvsd', '<p>gshbsbsdb<strong>sdbsd fsdf sdf</strong></p>');
INSERT INTO `pages` VALUES(18, 128, 'asgfas', '<p>fsdghsdgsd</p>');
INSERT INTO `pages` VALUES(19, 134, 'saga', '<p>ghadfas</p>');
INSERT INTO `pages` VALUES(20, 133, 'dajgakrea asd ghh', '<p>gsgasfsdgsdgsgsfsd sdf sdfs dgsdfsdf</p>');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lft` int(11) unsigned NOT NULL,
  `rgt` int(11) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=139 ;

--
-- Tablo döküm verisi `sections`
--

INSERT INTO `sections` VALUES(1, 1, 26, 'ROOT', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(127, 2, 3, 'Home', '2012-05-31 20:59:48');
INSERT INTO `sections` VALUES(128, 4, 9, 'Computer', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(129, 12, 13, 'Mobil', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(130, 14, 23, 'Internet', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(131, 24, 25, 'Security', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(132, 10, 11, 'Tech', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(133, 5, 6, 'Hardware', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(134, 7, 8, 'Software', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(135, 15, 16, 'Social Networks', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(136, 17, 18, 'Video Sharing', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(137, 21, 22, 'Web Applications', '2012-05-31 20:59:50');
INSERT INTO `sections` VALUES(138, 19, 20, 'Blogs', '2012-05-31 20:59:50');

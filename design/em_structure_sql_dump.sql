-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Ven 20 Novembre 2015 à 20:13
-- Version du serveur: 5.5.46-0ubuntu0.14.04.2
-- Version de PHP: 5.5.9-1ubuntu4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `em`
--
CREATE DATABASE IF NOT EXISTS `em` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `em`;

-- --------------------------------------------------------

--
-- Structure de la table `bookmark`
--

CREATE TABLE IF NOT EXISTS `bookmark` (
  `bookmark_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` smallint(5) unsigned DEFAULT NULL,
  `bookmark_title` varchar(255) DEFAULT NULL,
  `bookmark_url` varchar(255) DEFAULT NULL,
  `bookmark_type` varchar(255) DEFAULT NULL COMMENT 'Type tel que défini par schema.org',
  `bookmark_rss_url` varchar(255) DEFAULT NULL,
  `bookmark_description` tinytext,
  `bookmark_creator` varchar(255) DEFAULT NULL,
  `bookmark_publisher` varchar(255) DEFAULT NULL,
  `bookmark_language` enum('en','fr','it') DEFAULT NULL,
  `bookmark_creation_date` datetime DEFAULT NULL,
  `bookmark_private` char(1) NOT NULL DEFAULT '0',
  `user_id` tinyint(3) unsigned DEFAULT NULL,
  `bookmark_lastedit_date` datetime DEFAULT NULL,
  `bookmark_lastedit_user_id` tinyint(3) unsigned DEFAULT NULL,
  `bookmark_login` tinytext,
  `bookmark_password` tinytext,
  `bookmark_thumbnail_filename` tinytext,
  PRIMARY KEY (`bookmark_id`),
  KEY `topic` (`topic_id`),
  KEY `lastedit_user` (`bookmark_lastedit_user_id`),
  KEY `creation_user` (`user_id`),
  KEY `publisher` (`bookmark_publisher`),
  KEY `bookmark_type` (`bookmark_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Les signets' AUTO_INCREMENT=4078 ;

-- --------------------------------------------------------

--
-- Structure de la table `hit`
--

CREATE TABLE IF NOT EXISTS `hit` (
  `bookmark_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_id` tinyint(3) unsigned DEFAULT NULL,
  `hit_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `coords_latitude` double DEFAULT NULL,
  `coords_longitude` double DEFAULT NULL,
  `coords_altitude` double DEFAULT NULL,
  `coords_accuracy` double DEFAULT NULL,
  `coords_altitudeAccuracy` double DEFAULT NULL,
  `coords_heading` double DEFAULT NULL,
  `coords_speed` double DEFAULT NULL,
  KEY `bookmark` (`bookmark_id`),
  KEY `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `shortcut`
--

CREATE TABLE IF NOT EXISTS `shortcut` (
  `from` smallint(5) unsigned NOT NULL,
  `to` smallint(5) unsigned NOT NULL,
  KEY `to` (`to`),
  KEY `from` (`from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Raccourcis entre catégories';

-- --------------------------------------------------------

--
-- Structure de la table `topic`
--

CREATE TABLE IF NOT EXISTS `topic` (
  `topic_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `topic_title` tinytext,
  `topic_description` tinytext,
  `topic_image_url` tinytext,
  `user_id` tinyint(3) unsigned DEFAULT NULL,
  `topic_private` char(1) NOT NULL DEFAULT '0',
  `topic_creation_date` datetime DEFAULT NULL,
  `lastModification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `topic_interval_lowerlimit` smallint(5) unsigned DEFAULT NULL,
  `topic_interval_higherlimit` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`topic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=400 ;

--
-- Déclencheurs `topic`
--
DROP TRIGGER IF EXISTS `privacy_check_onInsert`;
DELIMITER //
CREATE TRIGGER `privacy_check_onInsert` BEFORE INSERT ON `topic`
 FOR EACH ROW IF NEW.topic_private=0 AND(SELECT COUNT(*) FROM topic WHERE NEW.topic_interval_lowerlimit>topic_interval_lowerlimit AND NEW.topic_interval_higherlimit<topic_interval_higherlimit AND topic_private=1 GROUP BY topic_id) > 0 THEN
		SET NEW.topic_private=1;
	END IF
//
DELIMITER ;
DROP TRIGGER IF EXISTS `privacy_check_onUpdate`;
DELIMITER //
CREATE TRIGGER `privacy_check_onUpdate` BEFORE UPDATE ON `topic`
 FOR EACH ROW IF NEW.topic_private=0 AND (SELECT COUNT(*) FROM topic WHERE NEW.topic_interval_lowerlimit>topic_interval_lowerlimit AND NEW.topic_interval_higherlimit<topic_interval_higherlimit AND topic_private=1 GROUP BY topic_id) > 0 THEN
	SET NEW.topic_private=1;
END IF
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(20) DEFAULT NULL,
  `email` tinytext,
  `lastModification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Structure de la table `user_session`
--

CREATE TABLE IF NOT EXISTS `user_session` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` tinyint(3) unsigned NOT NULL,
  `expiration_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Session dépassant le cadre d''une session navigateur' AUTO_INCREMENT=1081 ;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `bookmark`
--
ALTER TABLE `bookmark`
  ADD CONSTRAINT `bookmark_ibfk_11` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`topic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `bookmark_ibfk_12` FOREIGN KEY (`bookmark_lastedit_user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `bookmark_ibfk_13` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `hit`
--
ALTER TABLE `hit`
  ADD CONSTRAINT `hit_ibfk_1` FOREIGN KEY (`bookmark_id`) REFERENCES `bookmark` (`bookmark_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hit_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `shortcut`
--
ALTER TABLE `shortcut`
  ADD CONSTRAINT `shortcut_ibfk_1` FOREIGN KEY (`from`) REFERENCES `topic` (`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `shortcut_ibfk_2` FOREIGN KEY (`to`) REFERENCES `topic` (`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `topic`
--
ALTER TABLE `topic`
  ADD CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_session`
--
ALTER TABLE `user_session`
  ADD CONSTRAINT `user_session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

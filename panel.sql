-- phpMyAdmin SQL Dump
-- version 3.2.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 22, 2014 at 05:44 AM
-- Server version: 5.5.35
-- PHP Version: 5.5.3-1ubuntu2.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `anypanel`
--
DROP DATABASE IF EXISTS anypanel;
CREATE DATABASE anypanel;
USE anypanel;
-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE IF NOT EXISTS `servers` (
  `ipaddress` varchar(64) NOT NULL,
  `ownername` varchar(32) NOT NULL,
  `credentials` varchar(255) NOT NULL,
  `modules` varchar(255) NOT NULL,
  `distro` varchar(32) NOT NULL,
  `privhostkey` text NOT NULL,
  `prompts` varchar(128) NOT NULL,
  `active` enum('0','1') NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`ipaddress`),
  KEY `ownername` (`ownername`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `ownername` varchar(32) NOT NULL,
  `password` varchar(40) NOT NULL,
  `email` varchar(200) NOT NULL,
  `active` enum('0','1') NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`ownername`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

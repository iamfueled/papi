<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */

$installer = $this;
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('amorderexport/link')}` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(128) NOT NULL,
  `table_name` varchar(128) NOT NULL,
  `base_key` varchar(96) NOT NULL,
  `referenced_key` varchar(96) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
");


$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('amorderexport/profile')}` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `store_ids` varchar(255) NOT NULL,
  `filename` varchar(128) NOT NULL,
  `path` varchar(255) NOT NULL,
  `ftp_use` tinyint(1) NOT NULL,
  `ftp_host` varchar(255) NOT NULL,
  `ftp_login` varchar(128) NOT NULL,
  `ftp_password` varchar(128) NOT NULL,
  `ftp_is_passive` tinyint(1) unsigned NOT NULL,
  `ftp_path` varchar(255) NOT NULL,
  `ftp_delete_local` tinyint(1) NOT NULL DEFAULT '0',
  `format` enum('csv','xml') NOT NULL,
  `csv_delim` varchar(12) NOT NULL DEFAULT ',',
  `csv_enclose` varchar(12) NOT NULL DEFAULT '\"',
  `export_include_fieldnames` tinyint(1) unsigned NOT NULL,
  `export_allfields` tinyint(1) unsigned NOT NULL,
  `filter_number_from` varchar(96) NOT NULL,
  `filter_date_from` date NOT NULL,
  `filter_date_to` date NOT NULL,
  `created_at` datetime NOT NULL,
  `lastrun_at` datetime NOT NULL,
  `last_increment_id` varchar(50) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
");

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('amorderexport/profile_field')}` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` int(10) unsigned NOT NULL,
  `field_table` varchar(128) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `mapped_name` varchar(255) NOT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `profile_id` (`profile_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
");

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('amorderexport/profile_history')}` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` int(10) unsigned NOT NULL,
  `run_at` datetime NOT NULL,
  `last_increment_id` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `run_time` double unsigned NOT NULL,
  `run_records` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `profile_id` (`profile_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
");

$installer->endSetup(); 
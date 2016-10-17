<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('amorderexport/static_field')}` (
      `entity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `profile_id` int(10) unsigned NOT NULL,
      `label` varchar(255) NOT NULL,
      `value` varchar(255) NOT NULL,
      `position` smallint(5) unsigned NOT NULL,
      PRIMARY KEY (`entity_id`),
      KEY `profile_id` (`profile_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
");

$installer->endSetup();
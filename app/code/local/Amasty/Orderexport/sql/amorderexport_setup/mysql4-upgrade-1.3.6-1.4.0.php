<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `export_custom_options` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `export_allfields` ;
");

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` 
    CHANGE `filter_date_from` `filter_date_from` DATE NULL ,
    CHANGE `filter_date_to` `filter_date_to` DATE NULL ;
");

$installer->endSetup();
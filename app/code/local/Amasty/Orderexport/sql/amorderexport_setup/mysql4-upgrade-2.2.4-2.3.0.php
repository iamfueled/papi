<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
     ALTER TABLE `{$this->getTable('amorderexport/profile')}` 
     add column `filter_invoice_from` VARCHAR(96) NOT NULL AFTER filter_shipment_to,
     add column `filter_invoice_to` VARCHAR(96) NOT NULL AFTER `filter_invoice_from`,
     add column `filter_invoice_from_skip` TINYINT(1) NOT NULL AFTER filter_invoice_to,
     add column `invoice_increment_auto` TINYINT(1) NOT NULL AFTER increment_auto;
     
    ALTER TABLE `{$this->getTable('amorderexport/profile_history')}` 
    ADD COLUMN `last_invoice_increment_id` VARCHAR(50) NOT NULL AFTER last_increment_id;
    
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` 
    ADD COLUMN `last_invoice_increment_id` VARCHAR(50) NOT NULL AFTER last_increment_id;

");

$installer->endSetup();
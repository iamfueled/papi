<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `filter_skip_zero_price` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `filter_date_to` ;
");

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile_field')}` ADD `sorting_order` SMALLINT(3) UNSIGNED NOT NULL ;
");

$installer->endSetup();
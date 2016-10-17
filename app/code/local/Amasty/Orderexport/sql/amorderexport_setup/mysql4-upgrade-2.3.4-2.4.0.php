<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `export_attributes_info` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `export_custom_options` ;
");

$installer->endSetup();
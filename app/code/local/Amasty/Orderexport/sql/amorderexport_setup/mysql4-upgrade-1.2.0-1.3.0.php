<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile_field')}` ADD `handler` VARCHAR( 32 ) NOT NULL ;
");

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `increment_auto` TINYINT( 1 ) NOT NULL ;
");

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `filter_number_from_skip` TINYINT( 1 ) NOT NULL AFTER `filter_number_from` ;
");

$installer->endSetup();
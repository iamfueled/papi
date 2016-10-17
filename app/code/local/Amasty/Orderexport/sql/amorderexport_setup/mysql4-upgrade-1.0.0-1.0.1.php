<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `filter_status` VARCHAR( 196 ) NOT NULL AFTER `filter_number_from` ;
");

$installer->endSetup();
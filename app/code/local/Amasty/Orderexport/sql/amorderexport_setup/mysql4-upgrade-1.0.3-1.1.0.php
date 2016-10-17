<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `filter_customergroup` SMALLINT(3) unsigned NOT NULL AFTER `filter_status` ;
");

$installer->endSetup();
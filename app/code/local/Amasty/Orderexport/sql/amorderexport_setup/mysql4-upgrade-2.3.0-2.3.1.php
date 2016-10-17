<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$resource = Mage::getSingleton('core/resource');

$readConnection = $resource->getConnection('core_read');
$query = "SHOW COLUMNS FROM `{$this->getTable('amorderexport/profile')}` WHERE `field`='run_after_order_creation'";

$column = $readConnection->fetchOne($query);

if ($column == FALSE){
    $installer->run("
         ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD  `run_after_order_creation` TINYINT( 1 ) UNSIGNED NOT NULL; 
    ");
}
     
$installer->endSetup();
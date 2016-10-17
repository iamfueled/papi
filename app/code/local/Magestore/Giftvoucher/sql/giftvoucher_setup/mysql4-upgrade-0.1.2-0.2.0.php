<?php

$installer = $this;

$setup = new Mage_Eav_Model_Entity_Setup('catalog_setup');
$installer->startSetup();

$weight = Mage::getModel('catalog/resource_eav_attribute')->load($setup->getAttributeId('catalog_product','weight'));
$applyTo = explode(',',$weight->getData('apply_to'));
$applyTo[] = 'giftvoucher';
$weight->addData(array('apply_to' => $applyTo))->save();

$installer->endSetup(); 
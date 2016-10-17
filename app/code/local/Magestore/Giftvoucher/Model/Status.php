<?php

class Magestore_Giftvoucher_Model_Status extends Varien_Object
{
    const STATUS_PENDING	= 1;
    const STATUS_ACTIVE		= 2;
    const STATUS_DISABLED	= 3;
    const STATUS_USED		= 4;
    const STATUS_EXPIRED	= 5;
    const STATUS_DELETED	= 6;

    static public function getOptionArray(){
        return array(
            self::STATUS_PENDING	=> Mage::helper('giftvoucher')->__('Pending'),
            self::STATUS_ACTIVE		=> Mage::helper('giftvoucher')->__('Active'),
            self::STATUS_DISABLED	=> Mage::helper('giftvoucher')->__('Disabled'),
            self::STATUS_USED		=> Mage::helper('giftvoucher')->__('Used'),
            self::STATUS_EXPIRED	=> Mage::helper('giftvoucher')->__('Expired'),
        );
    }
    
    static public function getOptions(){
    	$options = array();
    	foreach (self::getOptionArray() as $value=>$label)
    		$options[] = array(
				'value'	=> $value,
				'label'	=> $label
			);
    	return $options;
    }
    
    public function toOptionArray(){
    	return self::getOptions();
    }
}
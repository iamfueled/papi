<?php

class Magestore_Giftvoucher_Model_Actions extends Varien_Object
{
    const ACTIONS_CREATE		= 1;
    const ACTIONS_UPDATE		= 2;
    const ACTIONS_MASS_UPDATE	= 3;
    const ACTIONS_EMAIL			= 4;
    const ACTIONS_SPEND_ORDER	= 5;
    const ACTIONS_REFUND		= 6;
    const ACTIONS_REDEEM        = 7;

    static public function getOptionArray(){
        return array(
            self::ACTIONS_CREATE	=> Mage::helper('giftvoucher')->__('Create'),
            self::ACTIONS_UPDATE	=> Mage::helper('giftvoucher')->__('Update'),
            self::ACTIONS_MASS_UPDATE	=> Mage::helper('giftvoucher')->__('Mass update'),
            self::ACTIONS_EMAIL		=> Mage::helper('giftvoucher')->__('Send email'),
            self::ACTIONS_SPEND_ORDER	=> Mage::helper('giftvoucher')->__('Spend for order'),
            self::ACTIONS_REFUND		=> Mage::helper('giftvoucher')->__('Refund'),
            self::ACTIONS_REDEEM        => Mage::helper('giftvoucher')->__('Redeem'),
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
}
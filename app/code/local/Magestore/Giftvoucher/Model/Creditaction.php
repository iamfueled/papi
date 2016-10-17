<?php

class Magestore_Giftvoucher_Model_Creditaction extends Varien_Object
{
    const ACTIONS_REDEEM		= 'Redeem';
    const ACTIONS_APIREDEEM		= 'Api_re';
    const ACTIONS_APIUPDATE     = 'Apiupdate';
    const ACTIONS_ADMINUPDATE   = 'Adminupdate';
    const ACTIONS_SPEND         = 'Spend';
    const ACTIONS_REFUND               = 'Refund';

    static public function getOptionArray(){
        return array(
            self::ACTIONS_REDEEM	=> Mage::helper('giftvoucher')->__('Customer Redeem'),
            self::ACTIONS_APIREDEEM	=> Mage::helper('giftvoucher')->__('API User Redeem'),
            self::ACTIONS_APIUPDATE	=> Mage::helper('giftvoucher')->__('API User Update'),
            self::ACTIONS_ADMINUPDATE		=> Mage::helper('giftvoucher')->__('Admin Update'),
            self::ACTIONS_SPEND		=> Mage::helper('giftvoucher')->__('Customer Spend'),
            self::ACTIONS_REFUND		=> Mage::helper('giftvoucher')->__('Admin Refund'),
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
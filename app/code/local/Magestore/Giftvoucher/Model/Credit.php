<?php

class Magestore_Giftvoucher_Model_Credit extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/credit');
    }
    public function getCreditAccountLogin(){
        $customer = Mage::getSingleton('customer/session')->getCustomer();
    	$customerId = $customer->getId();
        return $this->getCreditByCustomerId($customerId);
        
    }
    public function getCreditByCustomerId($customerId){
        $collection=$this->getCollection()->addFieldToFilter('customer_id',$customerId);
        if($collection->getSize()){
            $id = $collection->getFirstItem()->getId();
            $this->load($id);
        }
        return $this;
    }
}
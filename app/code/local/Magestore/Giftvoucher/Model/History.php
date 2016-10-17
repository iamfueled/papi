<?php

class Magestore_Giftvoucher_Model_History extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/history');
    }
    
    public function getCollectionByOrderAction($giftVoucher,$order,$action){
    	return $this->getCollection()
    		->addFieldToFilter('giftvoucher_id',$giftVoucher->getId())
    		->addFieldToFilter('action',$action)
    		->addFieldToFilter('order_increment_id',$order->getIncrementId());
    }
    
    public function getTotalSpent($giftVoucher,$order){
    	$total = 0;
    	foreach ($this->getCollectionByOrderAction($giftVoucher,$order,Magestore_Giftvoucher_Model_Actions::ACTIONS_SPEND_ORDER) as $history){
    		$total += $history->getAmount();
    	}
    	return $total;
    }
    
    public function getTotalRefund($giftVoucher,$order){
    	$total = 0;
    	foreach ($this->getCollectionByOrderAction($giftVoucher,$order,Magestore_Giftvoucher_Model_Actions::ACTIONS_REFUND) as $history){
    		$total += $history->getAmount();
    	}
    	return $total;
    }
}
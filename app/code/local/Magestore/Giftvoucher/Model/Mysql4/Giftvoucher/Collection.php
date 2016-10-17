<?php

class Magestore_Giftvoucher_Model_Mysql4_Giftvoucher_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/giftvoucher');
    }
    
    public function joinHistory(){
    	if ($this->hasFlag('join_history') && $this->getFlag('join_history')) return $this;
    	$this->setFlag('join_history',true);
    	$this->getSelect()->joinLeft(
			array('history' => $this->getTable('giftvoucher/history')),
			'main_table.giftvoucher_id = history.giftvoucher_id',
			array(
				'history_amount'	=> 'amount',
				'history_currency'	=> 'currency',
				'created_at',
				'extra_content',
				'order_increment_id'
			)
		)->where('history.action = ?',Magestore_Giftvoucher_Model_Actions::ACTIONS_CREATE);
    	return $this;
    }
    
    public function getAvailable(){
    	$this->addFieldToFilter('main_table.status',array('neq' => Magestore_Giftvoucher_Model_Status::STATUS_DELETED));
    	return $this;
    }
    
    public function addItemFilter($itemId){
    	if ($this->hasFlag('add_item_filer') && $this->getFlag('add_item_filer')) return $this;
    	$this->setFlag('add_item_filer',true);
    	
    	$this->getSelect()->joinLeft(
    		array('history'	=> $this->getTable('giftvoucher/history')),
    		'main_table.giftvoucher_id = history.giftvoucher_id',
			array('order_item_id')
		)->where('history.order_item_id = ?',$itemId)
		->where('history.action = ?',Magestore_Giftvoucher_Model_Actions::ACTIONS_CREATE);
    	
    	return $this;
    }
    
    public function addExpireAfterDaysFilter($dayBefore){
    	$date = Mage::getModel('core/date')->gmtDate();
    	$zendDate = new Zend_Date($date);
    	$dayAfter = $zendDate->addDay($dayBefore)->toString('YYYY-MM-dd');
    	$this->getSelect()->where('date(expired_at) = ?',$dayAfter);
    	return $this;
    }
}
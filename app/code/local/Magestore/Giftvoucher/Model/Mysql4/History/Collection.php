<?php

class Magestore_Giftvoucher_Model_Mysql4_History_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/history');
    }
    
    public function joinGiftVoucher(){
    	if ($this->hasFlag('join_giftvoucher') && $this->getFlag('join_giftvoucher')) return $this;
    	$this->setFlag('join_giftvoucher',true);
    	$this->getSelect()->joinLeft(
			array('giftvoucher' => $this->getTable('giftvoucher/giftvoucher')),
			'main_table.giftvoucher_id = giftvoucher.giftvoucher_id',
			array(
				'gift_code'
			)
		)->where('main_table.action = ?',Magestore_Giftvoucher_Model_Actions::ACTIONS_SPEND_ORDER);
    	return $this;
    }
}
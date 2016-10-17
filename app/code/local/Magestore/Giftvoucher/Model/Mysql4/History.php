<?php

class Magestore_Giftvoucher_Model_Mysql4_History extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/history', 'history_id');
    }
}
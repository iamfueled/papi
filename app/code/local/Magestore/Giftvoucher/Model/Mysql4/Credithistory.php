<?php

class Magestore_Giftvoucher_Model_Mysql4_Credithistory extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/credithistory', 'history_id');
    }
}
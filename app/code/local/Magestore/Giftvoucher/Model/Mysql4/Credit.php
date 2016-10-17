<?php

class Magestore_Giftvoucher_Model_Mysql4_Credit extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/credit', 'credit_id');
    }
}
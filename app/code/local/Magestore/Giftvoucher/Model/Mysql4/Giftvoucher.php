<?php

class Magestore_Giftvoucher_Model_Mysql4_Giftvoucher extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/giftvoucher', 'giftvoucher_id');
    }
}
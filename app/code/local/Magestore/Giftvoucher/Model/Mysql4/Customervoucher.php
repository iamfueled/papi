<?php

class Magestore_Giftvoucher_Model_Mysql4_Customervoucher extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/customervoucher', 'customer_voucher_id');
    }
}
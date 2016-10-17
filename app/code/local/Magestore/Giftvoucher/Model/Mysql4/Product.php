<?php

class Magestore_Giftvoucher_Model_Mysql4_Product extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/product', 'giftcard_product_id');
    }
}
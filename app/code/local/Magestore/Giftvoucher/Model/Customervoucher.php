<?php

class Magestore_Giftvoucher_Model_Customervoucher extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/customervoucher');
    }
}
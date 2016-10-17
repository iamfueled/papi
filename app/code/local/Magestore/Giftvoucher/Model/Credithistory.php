<?php

class Magestore_Giftvoucher_Model_Credithistory extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/credithistory');
    }
}
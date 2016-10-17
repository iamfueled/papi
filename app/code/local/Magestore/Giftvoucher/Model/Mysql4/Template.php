<?php

class Magestore_Giftvoucher_Model_Mysql4_Template extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct(){
        $this->_init('giftvoucher/template', 'template_id');
    }
}
<?php
class Magestore_Giftvoucher_Block_Addorredeem extends Mage_Core_Block_Template
{
	public function _prepareLayout(){
		return parent::_prepareLayout();
    }
    
    public function getFormActionUrl(){
    	return $this->getUrl('giftvoucher/index/addlist');
    }
}
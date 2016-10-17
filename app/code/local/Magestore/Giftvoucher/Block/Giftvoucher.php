<?php
class Magestore_Giftvoucher_Block_Giftvoucher extends Mage_Core_Block_Template
{
	public function _prepareLayout(){
		return parent::_prepareLayout();
    }
    
    public function getFormActionUrl(){
    	return $this->getUrl('giftvoucher/index/check');
    }
    
    public function getCode(){
    	return Mage::app()->getRequest()->getParam('code',null);
    }
    
    public function getCodeTxt(){
    	return Mage::helper('giftvoucher')->getHiddenCode($this->getCode());
    }
    
    public function getGiftVoucher(){
    	if ($code = $this->getCode()){
			$codes = Mage::getSingleton('giftvoucher/session')->getCodes();
			$codes[] = $code;
			$codes = array_unique($codes);
			if ($max = Mage::helper('giftvoucher')->getGeneralConfig('maximum'))
				if (count($codes) > $max)
					return null;
			
			Mage::getSingleton('giftvoucher/session')->setCodes($codes);
    		$giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
    		if ($giftVoucher->getId())
    			return $giftVoucher;
    	}
    	return null;
    }
    
    public function getBalanceFormat($giftVoucher){
    	$currency = Mage::getModel('directory/currency')->load($giftVoucher->getCurrency());
    	return $currency->format($giftVoucher->getBalance());
    }
    
    public function getStatus($gifVoucher){
    	$status = $gifVoucher->getStatus();
    	$statusArray = Mage::getSingleton('giftvoucher/status')->getOptionArray();
    	return $statusArray[$status];
    }
}
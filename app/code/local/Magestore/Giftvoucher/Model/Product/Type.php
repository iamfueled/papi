<?php

class Magestore_Giftvoucher_Model_Product_Type extends Mage_Catalog_Model_Product_Type_Abstract
{
	public function prepareForCart(Varien_Object $buyRequest, $product = null){
		if (version_compare(Mage::getVersion(),'1.5.0','>='))
			return parent::prepareForCart($buyRequest,$product);
		if (is_null($product))
			$product = $this->getProduct();
		$result = parent::prepareForCart($buyRequest, $product);
		if (is_string($result))
			return $result;
		reset($result);
		$product = current($result);
		$result = $this->_prepareGiftVoucher($buyRequest,$product);
		return $result;
	}
	
	protected function _prepareProduct(Varien_Object $buyRequest, $product, $processMode){
		if (version_compare(Mage::getVersion(),'1.5.0','<'))
			return parent::_prepareProduct($buyRequest,$product,$processMode);
		if (is_null($product))
			$product = $this->getProduct();
		$result = parent::_prepareProduct($buyRequest, $product, $processMode);
		if (is_string($result))
			return $result;
		reset($result);
		$product = current($result);
		$result = $this->_prepareGiftVoucher($buyRequest,$product);
		return $result;
	}
	
	protected function _prepareGiftVoucher(Varien_Object $buyRequest, $product){
		$store = Mage::app()->getStore();
		if ($store->isAdmin()){
			$amount = $product->getPrice();
		}else{
			if ($buyRequest->getAmount()){
				$amount = $buyRequest->getAmount();
				$includeTax = ( Mage::getStoreConfig('tax/display/type') != 1 );
				$amount = $amount * 100 / Mage::helper('tax')->getPrice($product, 100, $includeTax);
			}else
				$amount = $product->getPrice();
			if (!$amount)
				return Mage::helper('giftvoucher')->__('Please enter gift card information');
		}
		if (!$buyRequest->getAmount())
			$buyRequest->setAmount($amount);
		$product->addCustomOption('amount', $amount);
		
		$fields = array();
		foreach (Mage::helper('giftvoucher')->getFullGiftVoucherOptions() as $key=>$label){
			if ($value = $buyRequest->getData($key))
				$product->addCustomOption($key,$value);
		}
		
		return array($product);
	}
	
	public function isVirtual($product = null){
		if (is_null($product))
			$product = $this->getProduct();
		if (!Mage::helper('giftvoucher')->getInterfaceConfig('postoffice',$product->getStoreId())) return true;
		
		$item = Mage::getModel('checkout/session')->getQuote()->getItemByProduct($product);
		if (!$item) return false;
		
		$options = array();
		foreach ($item->getOptions() as $option) $options[$option->getCode()] = $option->getValue();
		if (empty($options['recipient_ship'])) return true;
		
		return false;
	}
	
	public function hasRequiredOptions($product = null){
		return true;
		//if ($this->getProduct($product)->getPrice() == 0)
		//	return true;
		//return false;
	}
}
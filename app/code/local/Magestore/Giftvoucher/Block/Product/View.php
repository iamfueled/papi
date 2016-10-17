<?php
class Magestore_Giftvoucher_Block_Product_View extends Mage_Catalog_Block_Product_View_Abstract
{
	protected function _prepareLayout(){
		parent::_prepareLayout();
		if (Mage::helper('giftvoucher')->getInterfaceConfig('preview')
			&& Mage::helper('giftvoucher')->getInterfaceConfig('enable')){
			$media = $this->getLayout()->getBlock('product.info.media');
			if ($media)
				$media->setTemplate('giftvoucher/product/media.phtml');
		}
	}
	public function getGiftAmount($product){
		$giftAmount = $product->getGiftAmount();
		if (!$giftAmount)
			$giftAmount = Mage::helper('giftvoucher')->getInterfaceConfig('amount');
		$giftAmount = Mage::helper('giftvoucher')->getGiftAmount($giftAmount);
		$store = Mage::app()->getStore();
		switch ($giftAmount['type']){
			case 'range':
				$giftAmount['from']	= $this->convertPrice($product,$giftAmount['from']);
				$giftAmount['to']	= $this->convertPrice($product,$giftAmount['to']);
				$giftAmount['from_txt']	= $store->formatPrice($giftAmount['from']);
				$giftAmount['to_txt']	= $store->formatPrice($giftAmount['to']);
				break;
			case 'dropdown':
				$giftAmount['options'] = $this->_convertPrices($product,$giftAmount['options']);
				$giftAmount['options_txt'] = $this->_formatPrices($giftAmount['options']);
				break;
			case 'static':
				$giftAmount['value'] = $this->convertPrice($product,$giftAmount['value']);
				$giftAmount['value_txt'] = $store->formatPrice($giftAmount['value']);
				break;
			default:
				$giftAmount['type'] = 'any';
		}
		return $giftAmount;
	}
	
	protected function _convertPrices($product,$basePrices){
		//$store = Mage::app()->getStore();

		foreach ($basePrices as $key => $price)
			$basePrices[$key] = $this->convertPrice($product,$price);
		return $basePrices;
	}
	
	public function convertPrice($product, $price){
		$includeTax = ( Mage::getStoreConfig('tax/display/type') != 1 );
		$store = Mage::app()->getStore();
		
		$priceWithTax = Mage::helper('tax')->getPrice($product, $price, $includeTax);
		return $store->convertPrice($priceWithTax);
	}
	
	protected function _formatPrices($prices){
		$store = Mage::app()->getStore();
		foreach ($prices as $key => $price)
			$prices[$key] = $store->formatPrice($price,false);
		return $prices;
	}
	
	public function enableCustomMessage(){
		return Mage::helper('giftvoucher')->getInterfaceConfig('enable');
	}
	
	public function messageMaxLen(){
		return (int)Mage::helper('giftvoucher')->getInterfaceConfig('max');
	}
	
	public function enablePhysicalMail(){
		return Mage::helper('giftvoucher')->getInterfaceConfig('postoffice');
	}
	
	public function getFormConfigData(){
		$request = Mage::app()->getRequest();
		$action = $request->getRequestedRouteName().'_'.$request->getRequestedControllerName().'_'.$request->getRequestedActionName();
		if ($action == 'checkout_cart_configure' && $request->getParam('id')){
			$request = Mage::app()->getRequest();
			$options = Mage::getModel('sales/quote_item_option')->getCollection()->addItemFilter($request->getParam('id'));
			$formData = array();
			foreach ($options as $option)
				$formData[$option->getCode()] = $option->getValue();
			return new Varien_Object($formData);
		}
		return new Varien_Object();
	}
    
    public function enableScheduleSend() {
        return Mage::helper('giftvoucher')->getInterfaceConfig('schedule');
    }
    
    public function getGiftAmountDescription() {
        if (!$this->hasData('gift_amount_description')) {
            $product = $this->getProduct();
            $this->setData('gift_amount_description', '');
            if ($product->getShowGiftAmountDesc()){
                if ($product->getGiftAmountDesc()) {
                    $this->setData('gift_amount_description', $product->getGiftAmountDesc());
                } else {
                    $this->setData('gift_amount_description',
                        Mage::helper('giftvoucher')->getInterfaceConfig('description')
                    );
                }
            }
        }
        return $this->getData('gift_amount_description');
    }
}

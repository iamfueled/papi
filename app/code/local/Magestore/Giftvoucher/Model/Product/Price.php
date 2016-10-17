<?php

class Magestore_Giftvoucher_Model_Product_Price extends Mage_Catalog_Model_Product_Type_Price
{
	const PRICE_TYPE_FIXED		= 1;
	const PRICE_TYPE_DYNAMIC	= 0;
	
	public function getGiftAmount($product = null){
		if ($product)
			$giftAmount = $product->getGiftAmount();
		if (!$giftAmount)
			$giftAmount = Mage::helper('giftvoucher')->getInterfaceConfig('amount');
		$giftAmount = Mage::helper('giftvoucher')->getGiftAmount($giftAmount);
		switch ($giftAmount['type']){
			case 'range':
				$giftAmount['min_price'] = $giftAmount['from'];
				$giftAmount['max_price'] = $giftAmount['to'];
				$giftAmount['price'] = $giftAmount['from'];
				if ($giftAmount['min_price'] == $giftAmount['max_price'])
					$giftAmount['price_type'] = self::PRICE_TYPE_FIXED;
				$giftAmount['price_type'] = self::PRICE_TYPE_DYNAMIC;
				break;
			case 'dropdown':
				$giftAmount['min_price'] = min($giftAmount['options']);
				$giftAmount['max_price'] = max($giftAmount['options']);
				$giftAmount['price'] = $giftAmount['options'][0];
				if ($giftAmount['min_price'] == $giftAmount['max_price'])
					$giftAmount['price_type'] = self::PRICE_TYPE_FIXED;
				$giftAmount['price_type'] = self::PRICE_TYPE_DYNAMIC;
				break;
			case 'static':
				$giftAmount['price'] = $giftAmount['value'];
				$giftAmount['price_type'] = self::PRICE_TYPE_FIXED;
				break;
			default:
				$giftAmount['min_price'] = 0;
				$giftAmount['price_type'] = self::PRICE_TYPE_DYNAMIC;
				$giftAmount['price'] = 0;
		}
		return $giftAmount;
	}
	
	public function getPrice($product){
		$giftAmount = $this->getGiftAmount($product);
		return $giftAmount['price'];
	}
	
	protected function _applyOptionsPrice($product, $qty, $finalPrice){
		if ($amount = $product->getCustomOption('amount')){
			$store = Mage::app()->getStore();
			$finalPrice = $amount->getValue();
			$finalPrice /= $store->convertPrice(1);
		}
		return parent::_applyOptionsPrice($product,$qty,$finalPrice);
	}
	
	public function getPrices($product, $which = null){
		return $this->getPricesDependingOnTax($product,$which);
	}
	
	public function getPricesDependingOnTax($product, $which = null, $includeTax = null){
		$giftAmount = $this->getGiftAmount($product);
		if (isset($giftAmount['min_price']) && isset($giftAmount['max_price'])){
			$minimalPrice = Mage::helper('tax')->getPrice($product,$giftAmount['min_price'],$includeTax);
			$maximalPrice = Mage::helper('tax')->getPrice($product,$giftAmount['max_price'],$includeTax);
		}else{
			$minimalPrice = $maximalPrice = Mage::helper('tax')->getPrice($product,$giftAmount['price'],$includeTax);
		}
        
        // catalog rule
        if ($minimalPrice != $maximalPrice) {
            $minPrice = Mage::getSingleton('giftvoucher/rule')->calcProductPriceRule($product, $minimalPrice);
            if (!is_null($minPrice)) {
                $minimalPrice = $minPrice;
            }
            $maxPrice = Mage::getSingleton('giftvoucher/rule')->calcProductPriceRule($product, $maximalPrice);
            if (!is_null($maxPrice)) {
                $maximalPrice = $maxPrice;
            }
        }
		
		if ($which == 'max')
			return $maximalPrice;
		elseif ($which == 'min')
			return $minimalPrice;
		return array($minimalPrice,$maximalPrice);
	}
	
	public function getMinimalPrice($product){
		return $this->getPrices($product,'min');
	}
	
	public function getMaximalPrice($product){
		return $this->getPrices($product,'max');
	}
    
    // get final price for catalog rule
    public function getFinalPrice($qty=null, $product) {
        $finalPrice = parent::getFinalPrice($qty, $product);
        $fnPrice = Mage::getSingleton('giftvoucher/rule')->calcProductPriceRule($product, $finalPrice);
        if (is_null($fnPrice)) {
            return $finalPrice;
        }
        return $fnPrice;
    }
}

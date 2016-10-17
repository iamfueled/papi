<?php

class Magestore_Giftvoucher_Model_Total_Quote_Giftvoucher extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
	public function __construct(){
		$this->setCode('giftvoucher');
	}
	
	public function collect(Mage_Sales_Model_Quote_Address $address){
		$quote = $address->getQuote();
		$session = Mage::getSingleton('checkout/session');
		
		if ($address->getAddressType() == 'billing' && !$quote->isVirtual() || !$session->getUseGiftCard()) {
            return $this;
        }
		
		if ($codes = $session->getGiftCodes()){
			$codesArray = array_unique(explode(',',$codes));
			$store = $quote->getStore();
			
			// if ($address->getAllBaseTotalAmounts()) {
				// $baseTotal = array_sum($address->getAllBaseTotalAmounts());
                // if ($address->getBaseUseGiftCreditAmount()) {
                    // $baseTotal -= $address->getBaseUseGiftCreditAmount();
                // }
            // } else {
				$baseTotal = $address->getBaseGrandTotal();
            // }
            $giftCardAmount = 0;
            foreach ($address->getAllItems() as $item) {
                if ($item->getProduct()->getTypeId() == 'giftvoucher') {
                    $giftCardAmount += $item->getBaseRowTotal();
                }
            }
            $baseTotal -= $giftCardAmount;
            // if ($baseTotal < 0.0001) {
                // return $this;
            // }
			
			$baseTotalDiscount = 0;
			$totalDiscount = 0;
			
			$codesBaseDiscount = array();
			$codesDiscount = array();
				
			$baseSessionAmountUsed = explode(',',$session->getBaseAmountUsed());
			$baseAmountUsed = array_combine($codesArray,$baseSessionAmountUsed);
			$amountUsed = $baseAmountUsed;
            
            $giftMaxUseAmount = unserialize($session->getGiftMaxUseAmount());
            if (!is_array($giftMaxUseAmount)) {
                $giftMaxUseAmount = array();
            }
			
			foreach ($codesArray as $code){
				$model = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
				
				if ($baseTotal == 0
					|| $model->getStatus() != Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
					|| $model->getBalance() == 0
					|| $model->getBaseBalance() <= $baseAmountUsed[$code]
                    || !$model->validate($address)
                ){
					$codesBaseDiscount[] = 0;
					$codesDiscount[] = 0;
				}else{
                    if (Mage::helper('giftvoucher')->canUseCode($model)) {
                        $baseBalance = $model->getBaseBalance() - $baseAmountUsed[$code];
                        $baseDiscount = min($baseBalance,$baseTotal);
                        if (array_key_exists($code, $giftMaxUseAmount)) {
                            $maxDiscount = max(floatval($giftMaxUseAmount[$code]), 0) / $store->convertPrice(1, false, false);
                            $baseDiscount = min($baseDiscount, $maxDiscount);
                        }
                    } else {
                        $baseDiscount = 0;
                    }
					$discount = $store->convertPrice($baseDiscount);
					
					$baseAmountUsed[$code] += $baseDiscount;
					$amountUsed[$code] = $store->convertPrice($baseAmountUsed[$code]);
					
					$baseTotal -= $baseDiscount;
					
					$baseTotalDiscount += $baseDiscount;
					$totalDiscount += $discount;
					
					$codesBaseDiscount[] = $baseDiscount;
					$codesDiscount[] = $discount;
				}
			}
			$codesBaseDiscountString = implode(',',$codesBaseDiscount);
			$codesDiscountString = implode(',',$codesDiscount);
			
			//update session
			$session->setBaseAmountUsed(implode(',',$baseAmountUsed));
			
			$session->setBaseGiftVoucherDiscount($session->getBaseGiftVoucherDiscount()+$baseTotalDiscount);
			$session->setGiftVoucherDiscount($session->getGiftVoucherDiscount()+$totalDiscount);
			
			$session->setCodesBaseDiscount($session->getBaseAmountUsed());
			$session->setCodesDiscount(implode(',',$amountUsed));
			
			//update address
			$address->setBaseGrandTotal($baseTotal + $giftCardAmount);
			$address->setGrandTotal($store->convertPrice($baseTotal + $giftCardAmount));
			
			$address->setBaseGiftVoucherDiscount($baseTotalDiscount);
			$address->setGiftVoucherDiscount($totalDiscount);
			
			$address->setGiftCodes($codes);
			$address->setCodesBaseDiscount($codesBaseDiscountString);
			$address->setCodesDiscount($codesDiscountString);
			
			//update quote
			$quote->setBaseGiftVoucherDiscount($session->getBaseGiftVoucherDiscount());
			$quote->setGiftVoucherDiscount($session->getGiftVoucherDiscount());
			
			$quote->setGiftCodes($codes);
			$quote->setCodesBaseDiscount($session->getCodesBaseDiscount());
			$quote->setCodesDiscount($session->getCodesDiscount());
		}
		
		return $this;
	}
	
	public function fetch(Mage_Sales_Model_Quote_Address $address){
		if ($giftVoucherDiscount = $address->getGiftVoucherDiscount()){
			$address->addTotal(array(
				'code'			=> $this->getCode(),
				'title'			=> Mage::helper('giftvoucher')->__('Gift Card'),
				'value'			=> -$giftVoucherDiscount,
				'gift_codes'		=> $address->getGiftCodes(),
				'codes_base_discount'	=> $address->getCodesBaseDiscount(),
				'codes_discount'	=> $address->getCodesDiscount()
			));
		}
		return $this;
	}
}
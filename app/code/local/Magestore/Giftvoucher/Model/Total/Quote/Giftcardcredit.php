<?php

class Magestore_Giftvoucher_Model_Total_Quote_Giftcardcredit extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
	public function __construct(){
		$this->setCode('giftcardcredit');
	}
	
	public function collect(Mage_Sales_Model_Quote_Address $address){
		$quote = $address->getQuote();
		$session = Mage::getSingleton('checkout/session');
        if (!is_object($session)) {
            return $this;
        }
        if (!Mage::helper('giftvoucher')->getGeneralConfig('enablecredit', $quote->getStoreId())) {
            $session->setBaseUseGiftCreditAmount(0);
            $session->setUseGiftCreditAmount(0);
            return $this;
        }
        if (Mage::app()->getStore()->isAdmin()) {
            $customer = Mage::getSingleton('adminhtml/session_quote')->getCustomer();
        } else {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
        }
		
		if ($address->getAddressType() == 'billing' && !$quote->isVirtual()
            || !$session->getUseGiftCardCredit() || !$customer->getId()
        ) {
            $session->setBaseUseGiftCreditAmount(0);
            $session->setUseGiftCreditAmount(0);
            return $this;
        }
        $credit = Mage::getModel('giftvoucher/credit')->load(
                $customer->getId(),
                'customer_id'
            );
        if ($credit->getBalance() < 0.0001) {
            $session->setBaseUseGiftCreditAmount(0);
            $session->setUseGiftCreditAmount(0);
            return $this;
        }
        $store = $quote->getStore();
        $baseBalance = 0;
        if ($rate = $store->getBaseCurrency()->getRate($credit->getData('currency'))) {
            $baseBalance = $credit->getBalance() / $rate;
        }
        if ($baseBalance < 0.0001) {
            $session->setBaseUseGiftCreditAmount(0);
            $session->setUseGiftCreditAmount(0);
            return $this;
        }
        // if ($address->getAllBaseTotalAmounts()) {
            // $baseTotal = array_sum($address->getAllBaseTotalAmounts());
        // } else {
            $baseTotal = $address->getBaseGrandTotal();
        // }
        $giftCardAmount = 0;
        foreach ($address->getAllItems() as $item) {
            if ($item->getProduct()->getTypeId() == 'giftvoucher') {
                $giftCardAmount += $item->getBaseRowTotal();
            }
        }
        if ($baseTotal - $giftCardAmount < 0.0001) {
            $session->setBaseUseGiftCreditAmount(0);
            $session->setUseGiftCreditAmount(0);
            return $this;
        }
        
        $baseDiscount = min($baseBalance, $baseTotal - $giftCardAmount);
        if ($session->getMaxCreditUsed() > 0.0001) {
            $baseDiscount = min($baseDiscount, floatval($session->getMaxCreditUsed())/$store->convertPrice(1, false, false));
        }
        $discount = $store->convertPrice($baseDiscount);
        
        if ($baseDiscount && $discount) {
            $session->setBaseUseGiftCreditAmount($baseDiscount);
            $session->setUseGiftCreditAmount($discount);
            
            $address->setGiftcardCreditAmount($baseDiscount * $rate);
            $address->setBaseUseGiftCreditAmount($baseDiscount);
            $address->setUseGiftCreditAmount($discount);
            
            $address->setBaseGrandTotal($baseTotal - $baseDiscount);
            $address->setGrandTotal($store->convertPrice($address->getBaseGrandTotal()));
        }
        return $this;
	}
	
	public function fetch(Mage_Sales_Model_Quote_Address $address){
		if ($amount = $address->getUseGiftCreditAmount()){
			$address->addTotal(array(
				'code'			=> $this->getCode(),
				'title'			=> Mage::helper('giftvoucher')->__('Customer Credit'),
				'value'			=> -$amount
			));
		}
		return $this;
	}
}

<?php
class Magestore_Giftvoucher_Block_Payment_Form extends Mage_Payment_Block_Form
{
	protected function _construct(){
		parent::_construct();
		$this->setTemplate('giftvoucher/payment/form.phtml');
	}
	
	public function getDescription(){
		return Mage::getStoreConfig('payment/giftvoucher/description');
	}
	
	public function isPassed(){
		return (Mage::app()->getStore()->roundPrice($this->getGrandTotal()) == 0);
	}
	
	public function getGiftVoucherDiscount(){
		$session = Mage::getSingleton('checkout/session');
		$discounts = array();
		if ($codes = $session->getGiftCodes()){
			$codesArray = explode(',',$codes);
			$codesDiscountArray = explode(',',$session->getCodesDiscount());
			$discounts = array_combine($codesArray,$codesDiscountArray);
		}
		return $discounts;
	}
	
	public function getGrandTotal(){
		if (!$this->hasData('grand_total')){
			$quote = Mage::getSingleton('checkout/session')->getQuote()->collectTotals();
			$grandTotal = $quote->getGrandTotal();
			/*
			$addresses = Mage::getSingleton('checkout/session')->getQuote()->getAllShippingAddresses();
			$grandTotal = 0;
			foreach ($addresses as $address)
				$grandTotal += $address->getGrandTotal();
			*/
			$this->setData('grand_total',$grandTotal);
		}
		return $this->getData('grand_total');
	}
	
	public function getAddGiftVoucherUrl(){
		return trim($this->getUrl('giftvoucher/checkout/addgift'),'/');
	}
    
    /**
     * check customer use gift card to checkout
     * 
     * @return boolean
     */
    public function getUseGiftVoucher() {
        return Mage::getSingleton('checkout/session')->getUseGiftCard();
    }
    
    public function checkCustomerIsLoggedIn() {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }
    
    /**
     * get existed gift Card
     * 
     * @return array
     */
    public function getExistedGiftCard() {
        $session = Mage::getSingleton('customer/session');
        if (!$session->isLoggedIn()) {
            return array();
        }
        $customerId = $session->getCustomer()->getId();
        $collection = Mage::getResourceModel('giftvoucher/customervoucher_collection')
            ->addFieldToFilter('main_table.customer_id', $customerId);
        $voucherTable = $collection->getTable('giftvoucher/giftvoucher');
        $collection->getSelect()
            ->join(array('v' => $voucherTable),
                'main_table.voucher_id = v.giftvoucher_id',
                array('gift_code', 'balance', 'currency', 'conditions_serialized')
            )->where('v.status = ?', Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE)
            ->where("v.recipient_name IS NULL OR v.recipient_name = '' OR (v.customer_id <> '" .
                $customerId . "' AND v.customer_email <> ?)",
                $session->getCustomer()->getEmail()
            );
            // ->where("v.recipient_name IS NULL OR v.recipient_name = ''")
            // ->where("v.recipient_email IS NULL OR v.recipient_email = ''");
        $giftCards = array();
        $addedCodes = array();
        if ($codes = Mage::getSingleton('checkout/session')->getGiftCodes()) {
            $addedCodes = explode(',', $codes);
        }
        $helper = Mage::helper('giftvoucher');
        $conditions = Mage::getSingleton('giftvoucher/giftvoucher')->getConditions();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->setQuote($quote);
        foreach ($collection as $item) {
            if (in_array($item->getGiftCode(), $addedCodes)) {
                continue;
            }
            if ($item->getConditionsSerialized()) {
                $conditionsArr = unserialize($item->getConditionsSerialized());
                if (!empty($conditionsArr) && is_array($conditionsArr)) {
                    $conditions->setConditions(array())->loadArray($conditionsArr);
                    if (!$conditions->validate($quote)) {
                        continue;
                    }
                }
            }
            $giftCards[] = array(
                'gift_code'     => $item->getGiftCode(),
                'hidden_code'   => $helper->getHiddenCode($item->getGiftCode()),
                'balance'       => $this->getGiftCardBalance($item)
            );
        }
        return $giftCards;
    }
    
    public function getGiftCardBalance($item) {
        $cardCurrency = Mage::getModel('directory/currency')->load($item->getCurrency());
        /* @var Mage_Core_Model_Store */
        $store = Mage::app()->getStore();
        $baseCurrency = $store->getBaseCurrency();
        $currentCurrency = $store->getCurrentCurrency();
        if ($cardCurrency->getCode() == $currentCurrency->getCode()) {
            return $store->formatPrice($item->getBalance());
        }
        if ($cardCurrency->getCode() == $baseCurrency->getCode()) {
            return $store->convertPrice($item->getBalance(), true);
        }
        if ($baseCurrency->convert(100, $cardCurrency)) {
            $amount = $item->getBalance() * $baseCurrency->convert(100, $currentCurrency) / $baseCurrency->convert(100, $cardCurrency);
            return $store->formatPrice($amount);
        }
        return $cardCurrency->format($item->getBalance(), array(), true);
    }
    
    /**
     * get customer Credit to checkout
     * 
     * @return Magestore_Giftvoucher_Model_Credit
     */
    public function getCustomerCredit() {
        if ($this->checkCustomerIsLoggedIn()) {
            $credit = Mage::getModel('giftvoucher/credit')->load(
                    Mage::getSingleton('customer/session')->getCustomerId(),
                    'customer_id'
                );
            if ($credit->getBalance() > 0.0001) {
                return $credit;
            }
        }
        return false;
    }
    
    public function formatBalance($credit, $showUpdate = false) {
        if ($showUpdate) {
            $cardCurrency = Mage::getModel('directory/currency')->load($credit->getCurrency());
            /* @var Mage_Core_Model_Store */
            $store = Mage::app()->getStore();
            $baseCurrency = $store->getBaseCurrency();
            $currentCurrency = $store->getCurrentCurrency();
            if ($cardCurrency->getCode() == $currentCurrency->getCode()) {
                return $store->formatPrice($credit->getBalance() - $this->getUseGiftCreditAmount());
            }
            if ($cardCurrency->getCode() == $baseCurrency->getCode()) {
                $amount = $store->convertPrice($credit->getBalance(), false);
                return $store->formatPrice($amount - $this->getUseGiftCreditAmount());
            }
            if ($baseCurrency->convert(100, $cardCurrency)) {
                $amount = $credit->getBalance() * $baseCurrency->convert(100, $currentCurrency) / $baseCurrency->convert(100, $cardCurrency);
                return $store->formatPrice($amount - $this->getUseGiftCreditAmount());
            }
            return $cardCurrency->format($credit->getBalance(), array(), true);
        }
        return $this->getGiftCardBalance($credit);
    }
    
    /**
     * check customer use gift credit to checkout
     * 
     * @return boolean
     */
    public function getUseGiftCredit() {
        return Mage::getSingleton('checkout/session')->getUseGiftCardCredit();
    }
    
    public function getUsingAmount() {
        return Mage::app()->getStore()->formatPrice(
            Mage::getSingleton('checkout/session')->getUseGiftCreditAmount()
        );
    }
    
    public function getUseGiftCreditAmount() {
        return Mage::getSingleton('checkout/session')->getUseGiftCreditAmount();
    }
    
    public function getOneStepCheckOutEnabled(){
        return Mage::helper('giftvoucher')->isModuleOutputEnabled('Magestore_Onestepcheckout');
    }
}

<?php

class Magestore_Giftvoucher_Helper_Data extends Mage_Core_Helper_Data {

    public function getGeneralConfig($code, $store = null) {
        return Mage::getStoreConfig('giftvoucher/general/' . $code, $store);
    }

    public function getInterfaceConfig($code, $store = null) {
        return Mage::getStoreConfig('giftvoucher/interface/' . $code, $store);
    }

    public function getEmailConfig($code, $store = null) {
        return Mage::getStoreConfig('giftvoucher/email/' . $code, $store);
    }

    public function calcCode($expression) {
        if ($this->isExpression($expression)) {
            return preg_replace_callback('#\[([AN]{1,2})\.([0-9]+)\]#', array($this, 'convertExpression'), $expression);
        } else {
            return $expression;
        }
    }

    public function convertExpression($param) {
        $alphabet = (strpos($param[1], 'A')) === false ? '' : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphabet .= (strpos($param[1], 'N')) === false ? '' : '0123456789';
        return $this->getRandomString($param[2], $alphabet);
    }

    public function isExpression($string) {
        return preg_match('#\[([AN]{1,2})\.([0-9]+)\]#', $string);
    }

    public function getGiftAmount($amountStr) {
        $amountStr = trim(str_replace(array(' ', "\r", "\t"), '', $amountStr));
        if ($amountStr == '' || $amountStr == '-') {
            return array('type' => 'any');
        }

        $values = explode('-', $amountStr);
        if (count($values) == 2) {
            return array('type' => 'range', 'from' => $values[0], 'to' => $values[1]);
        }

        $values = explode(',', $amountStr);
        if (count($values) > 1) {
            return array('type' => 'dropdown', 'options' => $values);
        }

        $value = floatval($amountStr);
        return array('type' => 'static', 'value' => $value);
    }

    public function getGiftVoucherOptions() {
        return array(
            'recipient_name' => $this->__('Recipient name'),
            'recipient_email' => $this->__('Recipient email'),
            'recipient_ship' => $this->__('Ship to recipient'),
            'recipient_address' => $this->__('Recipient address'),
            'message' => $this->__('Custom message'),
            'day_to_send' => $this->__('Day To Send'),
        );
    }

    public function getFullGiftVoucherOptions() {
        return array(
            'send_friend' => $this->__('Send Gift Card to friend'),
            'recipient_name' => $this->__('Recipient name'),
            'recipient_email' => $this->__('Recipient email'),
            'recipient_ship' => $this->__('Ship to recipient'),
            'recipient_address' => $this->__('Recipient address'),
            'message' => $this->__('Custom message'),
            'day_to_send' => $this->__('Day To Send'),
        );
    }

    public function getHiddenCode($code) {
        $prefix = $this->getGeneralConfig('showprefix');
        $prefixCode = substr($code, 0, $prefix);
        $suffixCode = substr($code, $prefix);
        if ($suffixCode) {
            $hiddenChar = $this->getGeneralConfig('hiddenchar');
            if (!$hiddenChar)
                $hiddenChar = 'X';
            else
                $hiddenChar = substr($hiddenChar, 0, 1);
            $suffixCode = preg_replace('#([A-Z,0-9]{1})#', $hiddenChar, $suffixCode);
        }
        return $prefixCode . $suffixCode;
    }

    public function isAvailableToAddCode() {
        $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
        if ($max = Mage::helper('giftvoucher')->getGeneralConfig('maximum'))
            if (count($codes) >= $max)
                return false;
        return true;
    }

    /**
     * check code can used to checkout or not
     * 
     * @param mixed $code
     * @return boolean
     */
    public function canUseCode($code) {
        if (!$code) {
            return false;
        }
        if (is_string($code)) {
            $code = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        }
        if (!($code instanceof Magestore_Giftvoucher_Model_Giftvoucher)) {
            return false;
        }
        if (!$code->getId()) {
            return false;
        }
        if (Mage::app()->getStore()->isAdmin()) {
            return true;
        }
        $shareCard = intval($this->getGeneralConfig('share_card'));
        if ($shareCard < 1) {
            return true;
        }
        $customersUsed = $code->getCustomerIdsUsed();
        if ($shareCard > count($customersUsed)
            || in_array(Mage::getSingleton('customer/session')->getCustomerId(), $customersUsed)
        ) {
            return true;
        }
        return false;
    }

    public function getAllowedCurrencies() {
        $optionArray=array();
        $baseCode = Mage::app()->getBaseCurrencyCode();
        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCode, array_values($allowedCurrencies));
        
        foreach ($rates as $key => $value) {
            $test = Mage::app()->getLocale()->currency($key);
            $optionArray[]=array('value'=>$key,'label'=>$test->getName());
        }
        
        if(!count($optionArray)){
            $test = Mage::app()->getLocale()->currency($baseCode);
            $optionArray[]=array('value'=>$baseCode,'label'=>$test->getName());
        }
        
        return $optionArray;
    }
    
    public function getCheckGiftCardUrl(){
    	return Mage::getUrl('giftvoucher/index/check');
    }
}

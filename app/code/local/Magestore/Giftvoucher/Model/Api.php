<?php

class Magestore_Giftvoucher_Model_Api extends Mage_Api_Model_Resource_Abstract {

    public function items($filters = NULL) {
        $collection = Mage::getModel('giftvoucher/giftvoucher')->getCollection();
        if (is_array($filters)) {
            try {
                foreach ($filters as $key => $value) {
                    $collection->addFieldToFilter($key, $value);
                }
            } catch (Mage_Core_Exception $e) {
                $this->_fault('filters_invalid', $e->getMessage());
            }
        }
        $result = array();
        foreach ($collection as $giftvoucher) {
            $result[] = $giftvoucher->toArray();
        }
        return $result;
    }

    public function check($code) {
        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        if (!$giftVoucher->getId()) {
            $this->_fault('not_exists', Mage::helper('giftvoucher')->__('Gift card "%s" is not exists', $code));
        }
        return $giftVoucher->toArray();
    }

    public function create($data) {
        $data = $this->_prepareData($data);
        try {
            if (!isset($data['balance']) || $data['balance'] <= 0)
                $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Balance must be greater than 0.'));
            $data['amount'] = $data['balance'];
            $data = $this->processData($data);

            $model = Mage::getModel('giftvoucher/giftvoucher');
            $model->setData($data)
                ->setIncludeHistory(true)
                ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $model->toArray();
//        return $model->getId();
    }

    public function massCreate($data) {
        $data = $this->_prepareData($data);
        try {
            if (!isset($data['balance']) || $data['balance'] <= 0)
                $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('The balance must be greater than 0.'));

            if (!Mage::helper('giftvoucher')->isExpression($data['pattern']))
                $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Invalid pattern'));
            $data = $this->processData($data);

            $model = Mage::getModel('giftvoucher/template');
            $model->setData($data)
                ->save();
            $giftcard = $model->getData();
            $giftcard['gift_code'] = $giftcard['pattern'];
            $giftcard['template_id'] = $model->getId();
            $giftcard['amount'] = $giftcard['balance'];
            $giftcard['status'] = Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE;
            $amount = $model->getAmount();
            $result = array();
            for ($i = 1; $i <= $amount; $i++) {
                $giftvoucher = Mage::getModel('giftvoucher/giftvoucher')->setData($giftcard)->setIncludeHistory(true)->save();
                $result[] = $giftvoucher->toArray();
            }
            $model->setIsGenerated(1)->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $result;
    }

    public function history($code) {
        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        if (!$giftVoucher->getId()) {
            $this->_fault('invalid_code', Mage::helper('giftvoucher')->__('Gift card "%s" is not exists', $code));
        }
        $collection = Mage::getModel('giftvoucher/history')
            ->getCollection()
            ->addFieldToFilter('giftvoucher_id', $giftVoucher->getId());
        $result = array();
        foreach ($collection as $giftvoucherhistory) {
            $result[] = $giftvoucherhistory->toArray();
        }
        return $result;
    }

    public function update($code, $data) {
        $data = $this->_prepareData($data);
        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        if (!$giftVoucher->getId()) {
            $this->_fault('invalid_code', Mage::helper('giftvoucher')->__('Gift card "%s" is not exists', $code));
        }
        try {
            if (isset($data['balance'])) {
                $data['amount'] = $data['balance'];
            } else {
                $data['amount'] = $giftVoucher->getBalance();
            }
            $data['action'] = Magestore_Giftvoucher_Model_Actions::ACTIONS_UPDATE;
            $data['extra_content'] = Mage::helper('giftvoucher')->__('Updated by Api user');
            $giftVoucher->addData($data)
                ->setIncludeHistory(true)
                ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return True;
    }

    public function sendEmail($data) {
        $data = $this->_prepareData($data);
        if (!isset($data['gift_code']) || !isset($data['type']))
            $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Invalid data'));
            $code = $data['gift_code'];
            $sendtofriend = $data['type'];
        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        if (!$giftVoucher->getId()) {
            $this->_fault('invalid_code', Mage::helper('giftvoucher')->__('Gift card "%s" is not exists', $code));
        }
        if ($sendtofriend == 'to_friend') {
            if ($giftVoucher->getRecipientEmail()) {
                $giftVoucher->sendEmailToFriend();
            } else {
                $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Email of Gift card Recipient does not exist.'));
            }
        } elseif ($sendtofriend == 'to_all') {
            if ($giftVoucher->getCustomerEmail()) {
                $giftVoucher->sendEmail();
            } else {
                $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Email of Gift card Purchaser does not exist.'));
            }
        } elseif ($sendtofriend == 'to_owner') {
            if ($giftVoucher->getCustomerEmail()) {
                $giftVoucher->setRecipientEmail('');
                $giftVoucher->sendEmail();
            } else {
                $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Email of Gift card Purchaser does not exist.'));
            }
        } else {
            $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Invalid data'));
        }
        return TRUE;
    }

    public function redeemToCredit($customerId, $code) {
        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        if (!$giftVoucher->getId()) {
            $this->_fault('invalid_code', Mage::helper('giftvoucher')->__('Gift card "%s" is not exists', $code));
        }
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getEmail()) {
            $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Customer ID does not exist.'));
        }
        
        if (!Mage::helper('giftvoucher')->canUseCode($giftVoucher)) {
            $this->_fault('limited_code', $code . Mage::helper('giftvoucher')->__('This Gift card limits the number of users'));
        }

        if ($giftVoucher->getBalance() == 0) {
            $this->_fault('invalid_code', $code . Mage::helper('giftvoucher')->__(' - Balance of gift card is 0'));
        }
        if ($giftVoucher->getStatus() != 2 && $giftVoucher->getStatus() != 4) {
            $this->_fault('invalid_code', $code . Mage::helper('giftvoucher')->__('Gift card "%s" is not avaliable'));
        } else {
            $balance = $giftVoucher->getBalance();

            $credit = Mage::getModel('giftvoucher/credit')->getCreditByCustomerId($customerId);
            
            $creditCurrencyCode = $credit->getCurrency();
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
            if (!$creditCurrencyCode) {
                $creditCurrencyCode = $baseCurrencyCode;
                $credit->setCurrency($creditCurrencyCode);
                $credit->setCustomerId($customer->getId());
            }

            $voucherCurrency = Mage::getModel('directory/currency')->load($giftVoucher->getCurrency());
            $baseCurrency = Mage::getModel('directory/currency')->load($baseCurrencyCode);
            $creditCurrency = Mage::getModel('directory/currency')->load($creditCurrencyCode);

            $amount_temp = $balance * $balance / $baseCurrency->convert($balance, $voucherCurrency);
            $amount = $baseCurrency->convert($amount_temp, $creditCurrency);

            $credit->setBalance($credit->getBalance() + $amount);

            $credithistory = Mage::getModel('giftvoucher/credithistory')
                ->setCustomerId($customer->getId())
                ->setAction('Api_re')
                ->setCurrencyBalance($credit->getBalance())
                ->setGiftcardCode($giftVoucher->getGiftCode())
                ->setBalanceChange($balance)
                ->setCurrency($giftVoucher->getCurrency())
                ->setCreatedDate(now());
            $history = Mage::getModel('giftvoucher/history')->setData(array(
                'order_increment_id' => '',
                'giftvoucher_id' => $giftVoucher->getId(),
                'created_at' => now(),
                'action' => Magestore_Giftvoucher_Model_Actions::ACTIONS_REDEEM,
                'amount' => $balance,
                'balance' => 0.0,
                'currency' => $giftVoucher->getCurrency(),
                'status' => $giftVoucher->getStatus(),
                'order_amount' => '',
                'comments' => Mage::helper('giftvoucher')->__('Redeem to the credit balance'),
                'extra_content' => Mage::helper('giftvoucher')->__('Redeemed by Api'),
                ));
            try {
                $credit->save();
            } catch (Exception $e) {
                $this->_fault('data_invalid', $e->getMessage());
            }
            try {
                $giftVoucher->setBalance(0)->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_USED)->save();
            } catch (Exception $e) {
                $credit->setBalance($credit->getBalance() - $amount)->save();
                $this->_fault('data_invalid', $e->getMessage());
            }
            try {
                $history->save();
                $credithistory->save();
            } catch (Exception $e) {
                $giftVoucher->setBalance($balance)->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE)->save();
                $credit->setBalance($credit->getBalance() - $amount)->save();
                $this->_fault('data_invalid', $e->getMessage());
            }
        }
        return $credithistory->toArray();
    }

    public function updateCredit($customerId, $balance, $currency = NULL) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getEmail()) {
            $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Customer ID does not exist.'));
        }
        $credit = Mage::getModel('giftvoucher/credit')->getCreditByCustomerId($customerId);
        if (!$currency)
            $currency = Mage::app()->getStore()->getDefaultCurrencyCode();
        $creditCurrencyCode = $credit->getCurrency();
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        if (!$creditCurrencyCode) {
            $creditCurrencyCode = $baseCurrencyCode;
            $credit->setCurrency($creditCurrencyCode);
            $credit->setCustomerId($customerId);
        }

        $voucherCurrency = Mage::getModel('directory/currency')->load($currency);
        $baseCurrency = Mage::getModel('directory/currency')->load($baseCurrencyCode);
        $creditCurrency = Mage::getModel('directory/currency')->load($creditCurrencyCode);

        $amount_temp = $balance * $balance / $baseCurrency->convert($balance, $voucherCurrency);
        $amount = $baseCurrency->convert($amount_temp, $creditCurrency);

        $credit->setBalance($credit->getBalance() + $amount);

        $credithistory = Mage::getModel('giftvoucher/credithistory')
            ->setCustomerId($customerId)
            ->setAction('Apiupdate')
            ->setCurrencyBalance($credit->getBalance())
            ->setBalanceChange($balance)
            ->setCurrency($currency)
            ->setCreatedDate(now());

        try {
            $credit->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        try {
            $credithistory->save();
        } catch (Mage_Core_Exception $e) {
            $credit->setBalance($credit->getBalance() - $amount)->save();
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $credithistory->toArray();
    }

    public function info($customerId) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getEmail()) {
            $this->_fault('data_invalid', Mage::helper('giftvoucher')->__('Customer ID does not exist.'));
        }
        $credit = Mage::getModel('giftvoucher/credit')->getCreditByCustomerId($customer->getId());
        return $credit->toArray();
    }

    protected function _filterDates($array, $dateFields) {
        if (empty($dateFields)) {
            return $array;
        }
        $filterInput = new Zend_Filter_LocalizedToNormalized(array(
                'date_format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
            ));
        $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
                'date_format' => Varien_Date::DATE_INTERNAL_FORMAT
            ));

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $array) && !empty($dateField)) {
                $array[$dateField] = $filterInput->filter($array[$dateField]);
                $array[$dateField] = $filterInternal->filter($array[$dateField]);
            }
        }
        return $array;
    }

    public function processData($data) {
        if (!isset($data['template_name']))
            $data['template_name'] = Mage::helper('giftvoucher')->__('Created by Api user');
        if (!isset($data['currency']))
            $data['currency'] = Mage::app()->getStore()->getDefaultCurrencyCode();
        // if (isset($data['expired_at']))
        //     $data = $this->_filterDates($data, array('expired_at'));
        if (!isset($data['expired_at']))
            $data['expired_at'] = null;
        if (!isset($data['status']))
            $data['status'] = Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE;
        $data['extra_content'] = Mage::helper('giftvoucher')->__('Created by Api user');
        return $data;
    }
    
    /**
     * Prepare data to create/massCreate/update/sendEmail.
     * Using API v1
     * 
     * @param array $data
     * @return array
     */
    protected function _prepareData($data)
    {
        return $data;
    }
}

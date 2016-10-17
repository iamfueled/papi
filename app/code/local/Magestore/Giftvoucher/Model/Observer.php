<?php

class Magestore_Giftvoucher_Model_Observer {

    public function couponPostAction($observer) {
        $action = $observer->getEvent()->getControllerAction();
        $code = trim($action->getRequest()->getParam('coupon_code'));
        if (!$code) {
            return $this;
        }
        if (!Mage::helper('magenotification')->checkLicenseKey('Giftvoucher')) {
            return;
        }
        if (!Mage::helper('giftvoucher')->isAvailableToAddCode()) {
            return $this;
            $session = Mage::getSingleton('checkout/session');
            $session->addError(Mage::helper('giftvoucher')->__('The maximum number of times to enter gift card code is %d!', Mage::helper('giftvoucher')->getGeneralConfig('maximum')));
            $action->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $action->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return $this;
        }
        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if ($giftVoucher->getId()
            && $giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
            && $giftVoucher->getBaseBalance() > 0
            && $giftVoucher->validate($quote->setQuote($quote))
        ) {
            $session = Mage::getSingleton('checkout/session');
            $giftVoucher->addToSession($session);
            $session->setUseGiftCard(1);
            $session->addSuccess(Mage::helper('giftvoucher')->__('Gift card "%s" was applied to your order.', Mage::helper('giftvoucher')->getHiddenCode($giftVoucher->getGiftCode())));
            $action->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $action->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        }
        return $this;
    }

    public function collectTotalsAfter($observer) {
        if ($code = trim(Mage::app()->getRequest()->getParam('coupon_code'))) {
            $quote = $observer->getEvent()->getQuote();
            if ($code != $quote->getCouponCode()) {
                $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
                $codes[] = $code;
                $codes = array_unique($codes);
                Mage::getSingleton('giftvoucher/session')->setCodes($codes);
            }
        }
    }

    public function collectTotalsBefore($observer) {
        $session = Mage::getSingleton('checkout/session');
        if ($codes = $session->getGiftCodes()) {
            $codesArray = array_unique(explode(',', $codes));
            foreach ($codesArray as $key => $value)
                $codesArray[$key] = 0;
            $session->setBaseAmountUsed(implode(',', $codesArray));
        } else {
            $session->setBaseAmountUsed(null);
        }
        $session->setBaseGiftVoucherDiscount(0);
        $session->setGiftVoucherDiscount(0);
        $session->setUseGiftCreditAmount(0);
    }

    public function orderPlaceBefore($observer) {
        $session = Mage::getSingleton('checkout/session');
        if ($codes = $session->getGiftCodes()) {
            $codesArray = explode(',', $codes);
            $baseSessionAmountUsed = explode(',', $session->getBaseAmountUsed());
            $baseAmountUsed = array_combine($codesArray, $baseSessionAmountUsed);

            foreach ($baseAmountUsed as $code => $amount) {
                $model = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
                if (!$model
                        || $model->getStatus() != Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
                        || $model->getBaseBalance() < $amount) {
                    Mage::app()->getResponse()
                            ->setHeader('HTTP/1.1', '403 Session Expired')
                            ->setHeader('Login-Required', 'true')
                            ->sendResponse();
                    exit;
                }
            }
        }
    }

    public function orderPlaceAfter($observer) {
        if (!Mage::helper('magenotification')->checkLicenseKey('Giftvoucher')) {
            return;
        }
        $order = $observer->getEvent()->getOrder();
        $this->_addGiftVoucherForOrder($order);
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getUseGiftCard() && !($session->getUseGiftCardCredit())) {
            return;
        }
        if ($codes = $order->getGiftCodes()) {
            $codesArray = explode(',', $codes);
            $codesBaseDiscount = explode(',', $order->getCodesBaseDiscount());
            $codesDiscount = explode(',', $order->getCodesDiscount());

            $baseDiscount = array_combine($codesArray, $codesBaseDiscount);
            $discount = array_combine($codesArray, $codesDiscount);
            foreach ($codesArray as $code) {
                if (!$baseDiscount[$code] || Mage::app()->getStore()->roundPrice($baseDiscount[$code]) == 0)
                    continue;
                $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);

                $baseCurrencyCode = $order->getBaseCurrencyCode();
                $codeDiscount = Mage::helper('directory')->currencyConvert($baseDiscount[$code], $baseCurrencyCode, $giftVoucher->getData('currency'));
                $balance = $giftVoucher->getBalance() - $codeDiscount;

                $giftVoucher->setData('balance', $balance)->save();

                $history = Mage::getModel('giftvoucher/history')->setData(array(
                            'order_increment_id' => $order->getIncrementId(),
                            'giftvoucher_id' => $giftVoucher->getId(),
                            'created_at' => now(),
                            'action' => Magestore_Giftvoucher_Model_Actions::ACTIONS_SPEND_ORDER,
                            'amount' => $baseDiscount[$code],
                            'balance' => $balance,
                            'currency' => $baseCurrencyCode,
                            'status' => $giftVoucher->getStatus(),
                            'order_amount' => $discount[$code],
                            'comments' => Mage::helper('giftvoucher')->__('Spend for order %s', $order->getIncrementId()),
                            'extra_content' => Mage::helper('giftvoucher')->__('Used by %s %s', $order->getData('customer_firstname'), $order->getData('customer_lastname')),
                            'customer_id' => $order->getData('customer_id'),
                            'customer_email' => $order->getData('customer_email')
                        ))->save();
                // add gift code to customer list
                if ($order->getCustomerId()) {
                    $collection = Mage::getResourceModel('giftvoucher/customervoucher_collection')
                        ->addFieldToFilter('customer_id', $order->getCustomerId())
                        ->addFieldToFilter('voucher_id', $giftVoucher->getId());
                    if (!$collection->getSize()) {
                        try {
                            Mage::getModel('giftvoucher/customervoucher')
                                ->setCustomerId($order->getCustomerId())
                                ->setVoucherId($giftVoucher->getId())
                                ->setAddedDate(now())
                                ->save();
                        } catch (Exception $e) {}
                    }
                }
            }
            // Create invoice for Order payed by Giftvoucher
            if (Mage::app()->getStore()->roundPrice($order->getGrandTotal()) == 0
                    && ($order->getPayment()->getMethod() == 'giftvoucher'
                    || $order->getPayment()->getMethod() == 'free')
                    && Mage::getStoreConfigFlag('payment/giftvoucher/invoice')
                    && $order->canInvoice()) {
                try {
                    $itemQtys = array();
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($itemQtys);
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();

                    $invoice->getOrder()->setIsInProcess(true);
                    $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                } catch (Exception $e) {
                    
                }
            }
        }
        if ($order->getGiftcardCreditAmount() && $order->getCustomerId()) {
            $credit = Mage::getModel('giftvoucher/credit')->load($order->getCustomerId(), 'customer_id');
            if ($credit->getId()) {
                try {
                    $credit->setBalance($credit->getBalance() - $order->getGiftcardCreditAmount());
                    $credit->save();
                    $credithistory = Mage::getModel('giftvoucher/credithistory')->setData($credit->getData());
                    $credithistory->addData(array(
                        'action' => 'Spend',
                        'currency_balance' => $credit->getBalance(),
                        'order_id' => $order->getId(),
                        'order_number' => $order->getIncrementId(),
                        'balance_change' => $order->getGiftcardCreditAmount(),
                        'created_date' => now(),
                        'base_amount' => $order->getBaseUseGiftCreditAmount(),
                        'amount' => $order->getUseGiftCreditAmount()
                    ))->setId(null)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
        if ($session->getUseGiftCard())
            $session->setUseGiftCard(null)
                    ->setGiftCodes(null)
                    ->setBaseAmountUsed(null)
                    ->setBaseGiftVoucherDiscount(null)
                    ->setGiftVoucherDiscount(null)
                    ->setCodesBaseDiscount(null)
                    ->setCodesDiscount(null)
                    ->setGiftMaxUseAmount(null);
        if ($session->getUseGiftCardCredit()) {
            $session->setUseGiftCardCredit(null)
                    ->setMaxCreditUsed(null)
                    ->setBaseUseGiftCreditAmount(null)
                    ->setUseGiftCreditAmount(null);
        }
    }

    protected function _loadOrderData($order) {
        $giftVouchers = Mage::getModel('giftvoucher/history')->getCollection()->joinGiftVoucher()
                ->addFieldToFilter('main_table.order_increment_id', $order->getIncrementId());
        $codesArray = array();
        $baseDiscount = 0;
        $discount = 0;
        foreach ($giftVouchers as $giftVoucher) {
            $codesArray[] = $giftVoucher->getGiftCode();
            $baseDiscount += $giftVoucher->getAmount();
            $discount += $giftVoucher->getOrderAmount();
        }
        if ($baseDiscount) {
            $order->setGiftCodes(implode(',', $codesArray));
            $order->setBaseGiftVoucherDiscount($baseDiscount);
            $order->setGiftVoucherDiscount($discount);
        }
        $creditHistory = Mage::getResourceModel('giftvoucher/credithistory_collection')
                ->addFieldToFilter('action', 'Spend')
                ->addFieldToFilter('order_id', $order->getId())
                ->getFirstItem();
        if ($creditHistory && $creditHistory->getId()) {
            $order->setGiftcardCreditAmount($creditHistory->getBalanceChange());
            $order->setBaseUseGiftCreditAmount($creditHistory->getBaseAmount());
            $order->setUseGiftCreditAmount($creditHistory->getAmount());
        }
        return $this;
    }

    public function paypalPrepareItems($observer) {
        if (version_compare(Mage::getVersion(), '1.4.2', '>=')) {
            $paypalCart = $observer->getEvent()->getPaypalCart();
            if ($paypalCart) {
                $salesEntity = $paypalCart->getSalesEntity();
                if ($salesEntity->getBaseGiftVoucherDiscount())
                    $paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_DISCOUNT, abs((float) $salesEntity->getBaseGiftVoucherDiscount()), Mage::helper('giftvoucher')->__('Gift Card Discount'));
                if ($salesEntity->getBaseUseGiftCreditAmount() && Mage::helper('giftvoucher')->getGeneralConfig('enablecredit'))
                    $paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_DISCOUNT, abs((float) $salesEntity->getBaseUseGiftCreditAmount()), Mage::helper('giftvoucher')->__('Customer Credit'));
            }
        }else {
            $salesEntity = $observer->getSalesEntity();
            $additional = $observer->getAdditional();
            if ($salesEntity && $additional) {
                $items = $additional->getItems();
                $items[] = new Varien_Object(array(
                            'name' => Mage::helper('giftvoucher')->__('Gift Card Discount'),
                            'qty' => 1,
                            'amount' => -(abs((float) $salesEntity->getBaseGiftVoucherDiscount())),
                        ));
                if (Mage::helper('giftvoucher')->getGeneralConfig('enablecredit')) {
                    $items[] = new Varien_Object(array(
                                'name' => Mage::helper('giftvoucher')->__('Customer Credit'),
                                'qty' => 1,
                                'amount' => - (abs((float) $salesEntity->getBaseUseGiftCreditAmount())),
                            ));
                }
                $additional->setItems($items);
            }
        }
    }

    public function orderLoadAfter($observer) {
        $order = $observer->getEvent()->getOrder();
        $this->_loadOrderData($order);
        if ((abs($order->getGiftVoucherDiscount()) < 0.0001 && abs($order->getUseGiftCreditAmount()) < 0.0001)
                || Mage::app()->getStore()->roundPrice($order->getGrandTotal()) > 0
                || $order->getState() === Mage_Sales_Model_Order::STATE_CLOSED
                || $order->isCanceled()
                || $order->canUnhold()) {
            return $this;
        }
        foreach ($order->getAllItems() as $item) {
            if (($item->getQtyInvoiced() - $item->getQtyRefunded() - $item->getQtyCanceled()) > 0) {
                $order->setForcedCanCreditmemo(true);
                return $this;
            }
        }
    }

    public function creditmemoRegisterBefore($observer) {
        $input = $observer->getEvent()->getRequest()->getParam('creditmemo');
        if (isset($input['giftcard_refund'])) {
            $refund = $input['giftcard_refund'];
            if ($refund < 0)
                return $this;

            $creditmemo = $observer->getEvent()->getCreditmemo();
            $maxAmount = 0;
            if ($creditmemo->getUseGiftCreditAmount() && Mage::helper('giftvoucher')->getGeneralConfig('enablecredit', $creditmemo->getStoreId())) {
                $maxAmount += floatval($creditmemo->getUseGiftCreditAmount());
            }
            if ($creditmemo->getGiftVoucherDiscount()) {
                $maxAmount += floatval($creditmemo->getGiftVoucherDiscount());
            }
            $creditmemo->setGiftcardRefundAmount(min(floatval($refund), $maxAmount));
        }
    }

    public function creditmemoSaveAfter($observer) {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $baseGrandTotal = $creditmemo->getBaseGrandTotal();
        $order = $creditmemo->getOrder();

        // manual save in Backend
        if (Mage::app()->getStore()->isAdmin() && $creditmemo->getGiftcardRefundAmount()) {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            if ($customer->getId() && Mage::helper('giftvoucher')->getGeneralConfig('enablecredit', $creditmemo->getStoreId())) {
                $credit = Mage::getModel('giftvoucher/credit')->load($customer->getId(), 'customer_id');
                if (!$credit->getId()) {
                    $credit->setCustomerId($customer->getId())
                            ->setCurrency($order->getBaseCurrencyCode())
                            ->setBalance(0);
                }
                $refundAmount = 0;
                $baseCurrency = Mage::app()->getStore($order->getStoreId())->getBaseCurrency();
                if ($rate = $baseCurrency->getRate($order->getOrderCurrencyCode())) {
                    $refundAmount = $creditmemo->getGiftcardRefundAmount() / $rate;
                }
                if ($refundAmount && $baseCurrency->getRate($credit->getCurrency())) {
                    $creditBalance = $refundAmount * $baseCurrency->getRate($credit->getCurrency());
                    try {
                        $credit->setBalance($credit->getBalance() + $creditBalance)
                                ->save();
                        $credithistory = Mage::getModel('giftvoucher/credithistory')->setData($credit->getData());
                        $credithistory->addData(array(
                            'action' => 'Refund',
                            'currency_balance' => $credit->getBalance(),
                            'order_id' => $order->getId(),
                            'order_number' => $order->getIncrementId(),
                            'balance_change' => $creditBalance,
                            'created_date' => now(),
                            'base_amount' => $refundAmount,
                            'amount' => $creditmemo->getGiftcardRefundAmount()
                        ))->setId(null)->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            } else {
                $refundAmount = 0;
                $baseCurrency = Mage::app()->getStore($order->getStoreId())->getBaseCurrency();
                if ($rate = $baseCurrency->getRate($order->getOrderCurrencyCode())) {
                    $refundAmount = $creditmemo->getGiftcardRefundAmount() / $rate;
                }
                if ($refundAmount) {
                    $this->_refundOffline($order, $refundAmount);
                }
            }
            return $this;
        }
        // online save in frontend
        if (!Mage::app()->getStore()->isAdmin() && Mage::helper('giftvoucher')->getGeneralConfig('online_refund')) {
            if ($creditmemo->getBaseGiftVoucherDiscount()) {
                $maxAmount = floatval($creditmemo->getBaseGiftVoucherDiscount());
                $this->_refundOffline($order, $maxAmount);
            }
        }
        // refund for Giftvoucher payment method
        if ($order->getPayment()->getMethod() == 'giftvoucher') {
            $this->_refundOffline($order, $baseGrandTotal);
        }
    }

    protected function _refundOffline($order, $baseGrandTotal) {
        if ($codes = $order->getGiftCodes()) {
            $codesArray = explode(',', $codes);
            foreach ($codesArray as $code) {
                if (Mage::app()->getStore()->roundPrice($baseGrandTotal) == 0)
                    return $this;

                $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
                $history = Mage::getModel('giftvoucher/history');

                $availableDiscount = $history->getTotalSpent($giftVoucher, $order) - $history->getTotalRefund($giftVoucher, $order);
                if (Mage::app()->getStore()->roundPrice($availableDiscount) == 0)
                    continue;

                if ($availableDiscount < $baseGrandTotal) {
                    $baseGrandTotal = $baseGrandTotal - $availableDiscount;
                } else {
                    $availableDiscount = $baseGrandTotal;
                    $baseGrandTotal = 0;
                }
                $baseCurrencyCode = $order->getBaseCurrencyCode();
                $discountRefund = Mage::helper('directory')->currencyConvert($availableDiscount, $baseCurrencyCode, $giftVoucher->getData('currency'));

                $balance = $giftVoucher->getBalance() + $discountRefund;
                if ($giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_USED)
                    $giftVoucher->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE);
                $giftVoucher->setData('balance', $balance)->save();

                $history->setData(array(
                    'order_increment_id' => $order->getIncrementId(),
                    'giftvoucher_id' => $giftVoucher->getId(),
                    'created_at' => now(),
                    'action' => Magestore_Giftvoucher_Model_Actions::ACTIONS_REFUND,
                    'amount' => $availableDiscount,
                    'balance' => $balance,
                    'currency' => $baseCurrencyCode,
                    'status' => $giftVoucher->getStatus(),
                    'comments' => Mage::helper('giftvoucher')->__('Refund from order %s', $order->getIncrementId()),
                    'customer_id' => $order->getData('customer_id'),
                    'customer_email' => $order->getData('customer_email'),
                ))->save();
            }
        }
        if ($order->getGiftcardCreditAmount() && $order->getCustomerId() && Mage::helper('giftvoucher')->getGeneralConfig('enablecredit', $order->getStoreId())) {
            $credit = Mage::getModel('giftvoucher/credit')->load($order->getCustomerId(), 'customer_id');
            if ($credit->getId()) {
                // check order is refunded to credit balance
                $histories = Mage::getResourceModel('giftvoucher/credithistory_collection')
                        ->addFieldToFilter('customer_id', $order->getCustomerId())
                        ->addFieldToFilter('action', 'Refund')
                        ->addFieldToFilter('order_id', $order->getId())
                        ->getFirstItem();
                if ($histories && $histories->getId()) {
                    return $this;
                }
                try {
                    $credit->setBalance($credit->getBalance() + $order->getGiftcardCreditAmount());
                    $credit->save();
                    $credithistory = Mage::getModel('giftvoucher/credithistory')->setData($credit->getData());
                    $credithistory->addData(array(
                        'action' => 'Refund',
                        'currency_balance' => $credit->getBalance(),
                        'order_id' => $order->getId(),
                        'order_number' => $order->getIncrementId(),
                        'balance_change' => $order->getGiftcardCreditAmount(),
                        'created_date' => now(),
                        'base_amount' => $order->getBaseUseGiftCreditAmount(),
                        'amount' => $order->getUseGiftCreditAmount()
                    ))->setId(null)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
        return $this;
    }

    public function orderSaveAfter($observer) {
        $order = $observer->getEvent()->getOrder();
        if ($order->getStatus() == Mage_Sales_Model_Order::STATE_COMPLETE) {
            $this->_addGiftVoucherForOrder($order);
        }
        // $refundState = explode(',', Mage::helper('giftvoucher')->getGeneralConfig('refund_orderstatus'));
        $refundState = array('canceled');
        if (in_array($order->getStatus(), $refundState) && Mage::helper('giftvoucher')->getGeneralConfig('cancel_refund')) {
            $this->_refundOffline($order, $order->getBaseGiftVoucherDiscount());
        }
        if (Mage::helper('giftvoucher')->getGeneralConfig('autochange')) {
            $expireState = explode(',', Mage::helper('giftvoucher')->getGeneralConfig('expire_orderstatus'));
            if (in_array($order->getStatus(), $expireState)) {
                $this->_expireGiftVoucherOfOrder($order);
            }
        }
    }

    protected function _addGiftVoucherForOrder($order) {
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() != 'giftvoucher')
                continue;

            $options = $item->getProductOptions();
            $buyRequest = $options['info_buyRequest'];

            $giftVouchers = Mage::getModel('giftvoucher/giftvoucher')->getCollection()->addItemFilter($item->getId());
            if ($order->getStatus() == Mage_Sales_Model_Order::STATE_COMPLETE
                    && Mage::helper('giftvoucher')->getGeneralConfig('autochange', $order->getStoreId())) {
                foreach ($giftVouchers as $giftVoucher) {
                    if ($giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_PENDING) {
                        $giftVoucher->addData(array(
                            'status' => Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE,
                            'comments' => Mage::helper('giftvoucher')->__('Active when order complete'),
                            'amount' => $giftVoucher->getBalance(),
                            'action' => Magestore_Giftvoucher_Model_Actions::ACTIONS_UPDATE,
                        ))->setIncludeHistory(true);
                        try {
                            if ($giftVoucher->getDayToSend()
                                    && strtotime($giftVoucher->getDayToSend()) > time()
                            ) {
                                $giftVoucher->setData('dont_send_email_to_recipient', 1);
                            }
                            if (!empty($buyRequest['recipient_ship']) && !Mage::helper('giftvoucher')->getEmailConfig('send_with_ship', $order->getStoreId())) {
                                $giftVoucher->setData('dont_send_email_to_recipient', 1);
                                $giftVoucher->setData('is_sent', 1);
                            }
                            $giftVoucher->save();
                            if (Mage::helper('giftvoucher')->getEmailConfig('enable', $order->getStoreId()))
                                $giftVoucher->sendEmail();
                        } catch (Exception $e) {
                            
                        }
                    }
                }
            }

            for ($i = 0; $i < $item->getQtyOrdered() - $giftVouchers->getSize(); $i++) {
                $giftVoucher = Mage::getModel('giftvoucher/giftvoucher');

                //$amount = isset($buyRequest['amount']) ? $buyRequest['amount'] : $item->getPrice();
                //$product = $item->getProduct();
                //if (!$product)
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                if (isset($buyRequest['amount'])) {
                    $amount = $buyRequest['amount'];
                    $includeTax = ( Mage::getStoreConfig('tax/display/type') != 1 );
                    $amount = $amount * 100 / Mage::helper('tax')->getPrice($product, 100, $includeTax);
                } else {
                    $amount = $item->getPrice();
                }

                $giftVoucher->setBalance($amount)->setAmount($amount);
                $giftVoucher->setOrderAmount($item->getBasePrice());

                $giftVoucher->setDescription($product->getGiftcardDescription());
                $giftProduct = Mage::getModel('giftvoucher/product')->loadByProduct($product);
                if ($giftProduct->getId()) {
                    $conditionsArr = unserialize($giftProduct->getConditionsSerialized());
                    if (!empty($conditionsArr) && is_array($conditionsArr)) {
                        $giftVoucher->getConditions()->loadArray($conditionsArr);
                    }
                }

                if (isset($buyRequest['recipient_name']))
                    $giftVoucher->setRecipientName($buyRequest['recipient_name']);
                if (isset($buyRequest['recipient_email']))
                    $giftVoucher->setRecipientEmail($buyRequest['recipient_email']);
                if (isset($buyRequest['message']))
                    $giftVoucher->setMessage($buyRequest['message']);
                if (isset($buyRequest['day_to_send']) && $buyRequest['day_to_send'])
                    $giftVoucher->setDayToSend(date('Y-m-d', strtotime($buyRequest['day_to_send'])));

                if (isset($buyRequest['recipient_ship']) && $buyRequest['recipient_ship'] != null
                        && $address = $order->getShippingAddress())
                    $giftVoucher->setRecipientAddress($address->getFormated());

                $giftVoucher->setCurrency($order->getOrderCurrencyCode());

                if ($order->getStatus() == Mage_Sales_Model_Order::STATE_COMPLETE
                        && Mage::helper('giftvoucher')->getGeneralConfig('autochange', $order->getStoreId()))
                    $giftVoucher->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE);
                else
                    $giftVoucher->setStatus(Mage::helper('giftvoucher')->getGeneralConfig('status', $order->getStoreId()));

                if ($timeLife = Mage::helper('giftvoucher')->getGeneralConfig('expire', $order->getStoreId())) {
                    //$currentDate = Mage::getModel('core/date')->gmtDate();
                    $expire = new Zend_Date(); //$currentDate);
                    $expire->addDay($timeLife);
                    $giftVoucher->setExpiredAt($expire->toString('YYYY-MM-dd HH:mm:ss'));
                }

                $giftVoucher->setCustomerId($order->getCustomerId())
                        ->setCustomerName($order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname'))
                        ->setCustomerEmail($order->getCustomerEmail())
                        ->setStoreId($order->getStoreId());

                $giftVoucher->setAction(Magestore_Giftvoucher_Model_Actions::ACTIONS_CREATE)
                        ->setComments(Mage::helper('giftvoucher')->__('Created for order %s', $order->getIncrementId()))
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setOrderItemId($item->getId())
                        ->setExtraContent(Mage::helper('giftvoucher')->__('Created by customer %s', $order->getData('customer_firstname')))
                        ->setIncludeHistory(true);
                try {
                    if ($giftVoucher->getDayToSend()
                            && strtotime($giftVoucher->getDayToSend()) > time()
                    ) {
                        $giftVoucher->setData('dont_send_email_to_recipient', 1);
                    }
                    if (!empty($buyRequest['recipient_ship']) && !Mage::helper('giftvoucher')->getEmailConfig('send_with_ship', $order->getStoreId())) {
                        $giftVoucher->setData('dont_send_email_to_recipient', 1);
                        $giftVoucher->setData('is_sent', 1);
                    }
                    $giftVoucher->save();
                    if (Mage::helper('giftvoucher')->getEmailConfig('enable', $order->getStoreId()))
                        $giftVoucher->sendEmail();
                    if ($order->getCustomerId()) {
                        Mage::getModel('giftvoucher/customervoucher')
                                ->setCustomerId($order->getCustomerId())
                                ->setVoucherId($giftVoucher->getId())
                                ->setAddedDate(now(true))
                                ->save();
                    }
                } catch (Exception $e) {
                    
                }
            }
        }
        return $this;
    }

    protected function _expireGiftVoucherOfOrder($order) {
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() != 'giftvoucher')
                continue;

            $giftVouchers = Mage::getModel('giftvoucher/giftvoucher')->getCollection()->addItemFilter($item->getId());
            foreach ($giftVouchers as $giftVoucher) {
                if ($giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_PENDING
                        || $giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE) {
                    $giftVoucher->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_DISABLED);
                    try {
                        $giftVoucher->save();
                    } catch (Exception $e) {
                        
                    }
                }
            }
        }
        return $this;
    }

    public function autoSendMail() {
        if (Mage::helper('giftvoucher')->getEmailConfig('autosend')) {
            $giftVouchers = Mage::getModel('giftvoucher/giftvoucher')->getCollection()
                    ->addFieldToFilter('status', array('neq' => Magestore_Giftvoucher_Model_Status::STATUS_DELETED))
                    ->addExpireAfterDaysFilter(Mage::helper('giftvoucher')->getEmailConfig('daybefore'));
            foreach ($giftVouchers as $giftVoucher)
                $giftVoucher->sendEmail();
        }
    }

    /**
     * send schedule for friend
     */
    public function sendScheduleEmail() {
        $collection = Mage::getResourceModel('giftvoucher/giftvoucher_collection');
        $collection->addFieldToFilter('is_sent', array('neq' => 1))
                ->addFieldToFilter('day_to_send', array('notnull' => true))
                ->addFieldToFilter('day_to_send', array('to' => now(true)));
        if (count($collection)) {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            foreach ($collection as $giftCard) {
                if ($giftCard->sendEmailToRecipient()) {
                    try {
                        $giftCard->setData('is_sent', 1)
                                ->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
            $translate->setTranslateInline(true);
        }
    }

    /*
     * redirect when admin edit gift product
     */

    public function adminhtmlCatalogProductNewAfter($observer) {
        $product = $observer->getEvent()->getProduct();
        if ($product->getTypeId() != 'giftvoucher')
            return;
        if (!($stockItem = $product->getStockItem())) {
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->assignProduct($product)
                    ->setData('stock_id', 1)
                    ->setData('store_id', 1);
        }
        $stockItem->setData('manage_stock', 0);
        $stockItem->setData('use_config_manage_stock', 0);
        $stockItem->setData('use_config_min_sale_qty', 1);
        $stockItem->setData('use_config_max_sale_qty', 1);
        $product->getStockItem();
    }

    public function adminhtmlCatalogProductSaveAfter($observer) {
        $action = $observer->getEvent()->getControllerAction();
        $back = $action->getRequest()->getParam('back');
        $session = Mage::getSingleton('giftvoucher/session');
        $giftproductsession = $session->getGiftProductEdit();

        if ($back || !$giftproductsession)
            return $this;

        $type = $action->getRequest()->getParam('type');
        if (!$type) {
            $id = $action->getRequest()->getParam('id');
            $type = Mage::getModel('catalog/product')->load($id)->getTypeId();
        }
        if (!$type)
            return $this;

        $reponse = Mage::app()->getResponse();
        $url = Mage::getModel('adminhtml/url')->getUrl("giftvoucheradmin/adminhtml_giftproduct/index");
        $reponse->setRedirect($url);
        $reponse->sendResponse();
        $session->unsetData('gift_product_edit');
        return $this;
    }
/*
    public function giftcardPaymentMethod($observer) {
        $block = $observer['block'];
        if ($block instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
            $requestPath = $block->getRequest()->getRequestedRouteName()
                    . '_' . $block->getRequest()->getRequestedControllerName()
                    . '_' . $block->getRequest()->getRequestedActionName();
            if ($requestPath == 'onestepcheckout_index_index'
                    || $requestPath == 'checkout_onepage_index'
            ) {
                return;
            }
            $transport = $observer['transport'];
            $html = $block->getLayout()->createBlock('giftvoucher/payment_form')->renderView();
            $html .= $transport->getHtml();
            $html .= '<script type="text/javascript">onLoadGiftvoucherForm();</script>';
            $transport->setHtml($html);
        }
    }
    */
     public function giftcardPaymentMethod($observer) {
        $block = $observer['block'];
        if ($block instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
            $requestPath = $block->getRequest()->getRequestedRouteName()
                    . '_' . $block->getRequest()->getRequestedControllerName()
                    . '_' . $block->getRequest()->getRequestedActionName();
            if ($requestPath == 'onestepcheckout_index_index'
                    || $requestPath == 'checkout_onepage_index'
            ) {
                return;
            }
            $transport = $observer['transport'];
            $html_addgiftcard = $block->getLayout()->createBlock('giftvoucher/payment_form')->renderView();
            $html = $transport->getHtml();
            $html .= '<script type="text/javascript">checkOutLoadGiftCard('.Mage::helper('core')->jsonEncode(array('html'=>$html_addgiftcard)).');onLoadGiftvoucherForm();</script>';
            $transport->setHtml($html);
        }
    }
     

    /**
     * clear admin checkout session
     * 
     * @param type $observer
     */
    public function clearAdminCheckoutSession($observer) {
        Mage::getSingleton('checkout/session')
                ->setUseGiftCard(null)
                ->setGiftCodes(null)
                ->setBaseAmountUsed(null)
                ->setBaseGiftVoucherDiscount(null)
                ->setGiftVoucherDiscount(null)
                ->setCodesBaseDiscount(null)
                ->setCodesDiscount(null)
                ->setGiftMaxUseAmount(null)
                ->setUseGiftCardCredit(null)
                ->setMaxCreditUsed(null)
                ->setBaseUseGiftCreditAmount(null)
                ->setUseGiftCreditAmount(null);
    }

    public function updateShippedGiftCard($observer) {
        $shipmentItem = $observer->getEvent()->getShipmentItem();
        $orderItemId = $shipmentItem->getOrderItemId();

        $giftVouchers = Mage::getResourceModel('giftvoucher/giftvoucher_collection')->addItemFilter($orderItemId);
        foreach ($giftVouchers as $giftCard) {
            if ($giftCard->getShippedToCustomer()
                    || !Mage::getStoreConfig('giftvoucher/general/auto_shipping', $giftCard->getStoreId())
            ) {
                return;
            }
            try {
                $giftCard->setShippedToCustomer(1)
                        ->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function customerSaveAfter($observer) {
        // if (!Mage::helper('giftvoucher')->getGeneralConfig('enablecredit'))
        //     return $this;
        $customer = $observer->getEvent()->getCustomer();
        if (!$customer->getId())
            return $this;
        $balance = Mage::app()->getRequest()->getParam('change_balance');
        if (!$balance)
            return $this;

        $credit = Mage::getModel('giftvoucher/credit')->getCreditByCustomerId($customer->getId());

        if (!$credit->getCurrency()) {
            $currency = Mage::app()->getStore()->getDefaultCurrencyCode();
            $credit->setCurrency($currency);
            $credit->setCustomerId($customer->getId());
        }
        $credit->setBalance($credit->getBalance() + $balance);

        $credithistory = Mage::getModel('giftvoucher/credithistory')
                ->setCustomerId($customer->getId())
                ->setAction('Adminupdate')
                ->setCurrencyBalance($credit->getBalance())
                ->setBalanceChange($balance)
                ->setCurrency($credit->getCurrency())
                ->setCreatedDate(now());
        try {
            $credit->save();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__($e->getMessage()));
        }
        try {
            $credithistory->save();
        } catch (Mage_Core_Exception $e) {
            $credit->setBalance($credit->getBalance() - $balance)->save();
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__($e->getMessage()));
        }
        return $this;
    }

    // Gift Card Product Condition
    public function conditionsAction($observer) {
        $product = Mage::registry('current_product');
        $model = Mage::getSingleton('giftvoucher/product');
        if (!$model->getId() && $product->getId()) {
            $model->loadByProduct($product);
        }
        $model->getConditions()->setJsFormObject('giftvoucher_conditions_fieldset');
        Mage::app()->getLayout()->getBlock('head')->setCanLoadRulesJs(true);
    }

    public function productSaveAfter($observer) {
        $product = $observer->getEvent()->getProduct();
        if ($product->getTypeId() != 'giftvoucher' || !$product->getId()) {
            return $this;
        }
        $model = Mage::getSingleton('giftvoucher/product');
        if ($model->getIsSavedConditions()) {
            return $this;
        }
        $model->setIsSavedConditions(true);
        if (!$model->getId()) {
            $model->loadByProduct($product);
        }
        $data = Mage::app()->getRequest()->getPost();
        if (isset($data['rule'])) {
            $rules = $data['rule'];
            if (isset($rules['conditions'])) {
                $data['conditions'] = $rules['conditions'];
            }
            unset($data['rule']);
        }
        $model->loadPost($data);
        $model->setProductId($product->getId());
        try {
            $model->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function predispatchCheckout($observer) {
        $cart = Mage::getSingleton('checkout/session');
        $items = $cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            $code = 'recipient_ship';
            $option = $item->getOptionByCode($code);
            if($option)
            $data = $option->getData();
            if ($data['value']) {
                $session = Mage::getSingleton('core/session');
                $session->addNotice(Mage::helper('giftvoucher')->__('You need to add your friend\'s address as the shipping address. We will send this gift card to that address.'));
                return $this;
            }
        }
        return $this;
    }

}

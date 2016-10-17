<?php

class Magestore_Giftvoucher_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {

        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect("customer/account/login");
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function checkAction() {

        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }

        $this->loadLayout();
        $max = Mage::helper('giftvoucher')->getGeneralConfig('maximum');

        if ($code = $this->getRequest()->getParam('code')) {
            $this->getLayout()->getBlock('head')->setTitle(Mage::helper('giftvoucher')->getHiddenCode($code));

            $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
            $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
            if (!$giftVoucher->getId()) {
                $codes[] = $code;
                $codes = array_unique($codes);
                Mage::getSingleton('giftvoucher/session')->setCodes($codes);
            }

            if (!Mage::helper('giftvoucher')->isAvailableToAddCode()) {
                Mage::getSingleton('giftvoucher/session')->addError(Mage::helper('giftvoucher')->__('The maximum number of times to enter gift card code is %d!', $max));
                $this->_initLayoutMessages('giftvoucher/session');
                $this->renderLayout();
                return;
            }

            if (!$giftVoucher->getId()) {
                $errorMessage = Mage::helper('giftvoucher')->__('Invalid card code. ');
                if ($max)
                    $errorMessage .= Mage::helper('giftvoucher')->__('You have %d times for checking gift card code.', $max - count($codes));
                Mage::getSingleton('giftvoucher/session')->addError($errorMessage);
            }
        }else {
            $this->getLayout()->getBlock('head')->setTitle(Mage::helper('giftvoucher')->__('Check Gift Card Balance'));
            if (!Mage::helper('giftvoucher')->isAvailableToAddCode())
                Mage::getSingleton('giftvoucher/session')->addError(Mage::helper('giftvoucher')->__('The maximum number of times to enter gift card code is %d!', $max));
        }

        $this->_initLayoutMessages('giftvoucher/session');
        $this->renderLayout();
    }

    public function removeAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect("customer/account/login");
            return;
        }
        $customer_voucher_id = $this->getRequest()->getParam('id');
        $voucher = Mage::getModel('giftvoucher/customervoucher')->load($customer_voucher_id);
        if ($voucher->getCustomerId() == Mage::getSingleton('customer/session')->getCustomer()->getId())
            try {
                $voucher->delete();
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('giftvoucher')->__('Gift card was successfully removed'));
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }
        $this->_redirect("giftvoucher/index/index");
    }

    public function addredeemAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect("customer/account/login");
            return;
        }
        $this->loadLayout();
        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('giftvoucher/index/index');
        }
        $this->renderLayout();
    }

    public function addlistAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_current' => true)));
            $this->_redirect("customer/account/login");
            return;
        }
        $code = $this->getRequest()->getParam('giftvouchercode');

        $max = Mage::helper('giftvoucher')->getGeneralConfig('maximum');

        if ($code) {
            $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
            $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
            if (!Mage::helper('giftvoucher')->isAvailableToAddCode()) {
                Mage::getSingleton('core/session')->addError(Mage::helper('giftvoucher')->__('The maximum number of times to enter gift card code is %d!', $max));
                $this->_redirect("giftvoucher/index/index");
                return;
            }
            if (!$giftVoucher->getId()) {
                $codes[] = $code;
                $codes = array_unique($codes);
                Mage::getSingleton('giftvoucher/session')->setCodes($codes);
                $errorMessage = Mage::helper('giftvoucher')->__('Gift card "%s" is invalid.', $code);
                if ($max)
                    $errorMessage .= Mage::helper('giftvoucher')->__('You have %d times for enter gift card code.', $max - count($codes));
                Mage::getSingleton('core/session')->addError($errorMessage);
                $this->_redirect("giftvoucher/index/addredeem");
                return;
            } else {
                if (!Mage::helper('giftvoucher')->canUseCode($giftVoucher)) {
                    Mage::getSingleton('core/session')->addError($this->__('Exceed the number of users allowed'));
                    return $this->_redirect("giftvoucher/index/index");
                }
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $collection = Mage::getModel('giftvoucher/customervoucher')->getCollection();
                $collection->addFieldToFilter('customer_id', $customer->getId())
                    ->addFieldToFilter('voucher_id', $giftVoucher->getId());
                if ($collection->getSize()) {
                    Mage::getSingleton('core/session')->addError(Mage::helper('giftvoucher')->__('This Gift card already exists in your list.'));
                    $this->_redirect("giftvoucher/index/addredeem");
                    return;
                } elseif ($giftVoucher->getStatus() != 1 && $giftVoucher->getStatus() != 2 && $giftVoucher->getStatus() != 4) {
                    Mage::getSingleton('core/session')->addError(Mage::helper('giftvoucher')->__('Gift card "%s" is not avaliable', $code));
                    $this->_redirect("giftvoucher/index/addredeem");
                    return;
                } else {
                    $model = Mage::getModel('giftvoucher/customervoucher')
                        ->setCustomerId($customer->getId())
                        ->setVoucherId($giftVoucher->getId())
                        ->setAddedDate(now());
                    try {
                        $model->save();
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper('giftvoucher')->__('Gift card was successfully added'));
                        $this->_redirect("giftvoucher/index/index");
                        return;
                    } catch (Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        $this->_redirect("giftvoucher/index/addredeem");
                        return;
                    }
                }
            }
        }

        $this->_redirect("giftvoucher/index/index");
    }

    public function redeemAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if(!Mage::helper('giftvoucher')->getGeneralConfig('enablecredit')){
            $this->_redirect("giftvoucher/index/index");
            return;
        }
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect("customer/account/login");
            return;
        }
        $code = $this->getRequest()->getParam('giftvouchercode');

        $max = Mage::helper('giftvoucher')->getGeneralConfig('maximum');

        if ($code) {
            $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
            $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
            if (!Mage::helper('giftvoucher')->isAvailableToAddCode()) {
                Mage::getSingleton('core/session')->addError(Mage::helper('giftvoucher')->__('The maximum number of times to enter gift card code is %d!', $max));
                $this->_redirect("giftvoucher/index/index");
                return;
            }
            if (!$giftVoucher->getId()) {
                $codes[] = $code;
                $codes = array_unique($codes);
                Mage::getSingleton('giftvoucher/session')->setCodes($codes);
                $errorMessage = Mage::helper('giftvoucher')->__('Gift card "%s" is invalid.', $code);
                if ($max)
                    $errorMessage .= Mage::helper('giftvoucher')->__('You have %d times for enter gift card code.', $max - count($codes));
                Mage::getSingleton('core/session')->addError($errorMessage);
                $this->_redirect("giftvoucher/index/addredeem");
                return;
            } else {
                if (!Mage::helper('giftvoucher')->canUseCode($giftVoucher)) {
                    Mage::getSingleton('core/session')->addError($this->__('Exceed the number of users allowed'));
                    return $this->_redirect("giftvoucher/index/index");
                }
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if ( $giftVoucher->getBalance() == 0) {
                    Mage::getSingleton('core/session')->addError(Mage::helper('giftvoucher')->__('%s - Balance of gift card is 0', $code));
                    $this->_redirect("giftvoucher/index/addredeem");
                    return;
                }
                if ($giftVoucher->getStatus() != 2 && $giftVoucher->getStatus() != 4 ) {
                    Mage::getSingleton('core/session')->addError(Mage::helper('giftvoucher')->__('Gift card "%s" is not avaliable', $code));
                    $this->_redirect("giftvoucher/index/addredeem");
                    return;
                } else {
                    $balance = $giftVoucher->getBalance();

                    $credit = Mage::getModel('giftvoucher/credit')->getCreditAccountLogin();
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
                        ->setAction('Redeem')
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
                        'balance'   => 0.0,
                        'currency' => $giftVoucher->getCurrency(),
                        'status' => $giftVoucher->getStatus(),
                        'order_amount' => '',
                        'comments' => Mage::helper('giftvoucher')->__('Redeem to the credit balance'),
                        'extra_content' => Mage::helper('giftvoucher')->__('Redeemed by %s', $customer->getName()),
                        'customer_id'   => $customer->getId(),
                        'customer_email'=> $customer->getEmail(),
                    ));

                    try {
                        $giftVoucher->setBalance(0)->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_USED)->save();
                    } catch (Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        $this->_redirect("giftvoucher/index/addredeem");
                        return;
                    }

                    try {
                        $credit->save();
                    } catch (Exception $e) {
                        $giftVoucher->setBalance($balance)->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE)->save();
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        $this->_redirect("giftvoucher/index/addredeem");
                        return;
                    }
                    try {
                        $history->save();
                        $credithistory->save();
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper('giftvoucher')->__('Gift card "%s" was successfully redeemed', $code));
                        $this->_redirect("giftvoucher/index/index");
                        return;
                    } catch (Exception $e) {
                        $giftVoucher->setBalance($balance)->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE)->save();
                        $credit->setBalance($credit->getBalance() - $amount)->save();
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        $this->_redirect("giftvoucher/index/addredeem");
                        return;
                    }
                }
            }
        }

        $this->_redirect("giftvoucher/index/index");
    }

    public function getGiftVoucher($giftvouchercode) {
        if ($giftvouchercode) {
            $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
            $codes[] = $giftvouchercode;
            $codes = array_unique($codes);
            if ($max = Mage::helper('giftvoucher')->getGeneralConfig('maximum'))
                if (count($codes) > $max)
                    return null;

            Mage::getSingleton('giftvoucher/session')->setCodes($codes);
            $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($giftvouchercode);
            if ($giftVoucher->getId())
                return $giftVoucher;
        }
        return Mage::getModel('giftvoucher/giftvoucher');
    }

    /**
     * View gift code detail
     */
    public function viewAction() {
        $linked = Mage::getModel('giftvoucher/customervoucher')->load($this->getRequest()->getParam('id'));
        if ($linked->getCustomerId() != Mage::getSingleton('customer/session')->getCustomerId()) {
            return $this->_redirect('*/*/index');
        }
        $this->loadLayout();
        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('giftvoucher/index/index');
        }
        $this->renderLayout();
    }

    /**
     * Print Gift Code
     */
    public function printAction() {
        $linked = Mage::getModel('giftvoucher/customervoucher')->load($this->getRequest()->getParam('id'));
        if ($linked->getCustomerId() != Mage::getSingleton('customer/session')->getCustomerId()) {
            return $this->_redirect('*/*/index');
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Email gift card to friend
     */
    public function emailAction() {
        $linked = Mage::getModel('giftvoucher/customervoucher')->load($this->getRequest()->getParam('id'));
        if ($linked->getCustomerId() != Mage::getSingleton('customer/session')->getCustomerId()) {
            return $this->_redirect('*/*/index');
        }
        $this->loadLayout();
        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('giftvoucher/index/index');
        }
        $this->renderLayout();
    }

    /**
     * send email to friend
     */
    public function sendEmailAction() {
        if ($data = $this->getRequest()->getPost()) {
            $id = $data['giftcard_id'];
            $giftCard = Mage::getModel('giftvoucher/giftvoucher')->load($id);
            $giftCard->addData($data);
            
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (!$customer ||
                ($giftCard->getCustomerId() != $customer->getId()
                    && $giftCard->getCustomerEmail() != $customer->getEmail()
            )) {
                Mage::getSingleton('core/session')->addError($this->__('Cannot send the gift card email.'));
                return $this->_redirect('*/*/');
            }
            
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            if ($giftCard->sendEmailToRecipient()) {
                Mage::getSingleton('core/session')->addSuccess($this->__('Gift card email has been sent successfully.'));
            } else {
                Mage::getSingleton('core/session')->addError($this->__('Cannot send Gift card email to your friend!'));
            }
            $translate->setTranslateInline(true);
        }
        $this->_redirect('*/*/');
    }

    /**
     * view balance history
     */
    public function historyAction() {
        $this->loadLayout();
        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('giftvoucher/index/index');
        }
        $this->renderLayout();
    }

}
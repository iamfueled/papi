<?php
class Magestore_Giftvoucher_CheckoutController extends Mage_Core_Controller_Front_Action
{
    public function removegiftAction(){
    	$session = Mage::getSingleton('checkout/session');
    	$code = trim($this->getRequest()->getParam('code'));
    	$codes = $session->getGiftCodes();
    	
    	$success = false;
    	if ($code && $codes){
    		$codesArray = explode(',',$codes);
    		foreach ($codesArray as $key => $value)
    			if ($value == $code){
    				unset($codesArray[$key]);
    				$success = true;
                    $giftMaxUseAmount = unserialize($session->getGiftMaxUseAmount());
                    if (is_array($giftMaxUseAmount) && array_key_exists($code, $giftMaxUseAmount)) {
                        unset($giftMaxUseAmount[$code]);
                        $session->setGiftMaxUseAmount(serialize($giftMaxUseAmount));
                    }
    				break;
   				}
    	}
    	
    	if ($success){
    		$codes = implode(',',$codesArray);
    		$session->setGiftCodes($codes);
    		$session->addSuccess($this->__('Gift card "%s" has been removed successfully!',Mage::helper('giftvoucher')->getHiddenCode($code)));
    	}else{
    		$session->addError($this->__('Gift card "%s" not found!', $code));
   		}
    	$this->_redirect('checkout/cart');
    }
    
    public function giftcardPostAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $session = Mage::getSingleton('checkout/session');
            if ($request->getParam('giftvoucher_credit') && Mage::helper('giftvoucher')->getGeneralConfig('enablecredit')) {
                $session->setUseGiftCardCredit(1);
                $session->setMaxCreditUsed(floatval($request->getParam('credit_amount')));
            } else {
                $session->setUseGiftCardCredit(0);
                $session->setMaxCreditUsed(null);
            }
            if ($request->getParam('giftvoucher')) {
                $session->setUseGiftCard(1);
                $giftcodesAmount = $request->getParam('giftcodes');
                if (count($giftcodesAmount)) {
                    $giftMaxUseAmount = unserialize($session->getGiftMaxUseAmount());
                    if (!is_array($giftMaxUseAmount)) {
                        $giftMaxUseAmount = array();
                    }
                    $giftMaxUseAmount = array_merge($giftMaxUseAmount, $giftcodesAmount);
                    $session->setGiftMaxUseAmount(serialize($giftMaxUseAmount));
                }
                $addcodes = array();
                if ($request->getParam('existed_giftvoucher_code')) {
                    $addcodes[] = trim($request->getParam('existed_giftvoucher_code'));
                }
                if ($request->getParam('giftvoucher_code')) {
                    $addcodes[] = trim($request->getParam('giftvoucher_code'));
                }
                if (count($addcodes)) {
                    if (Mage::helper('giftvoucher')->isAvailableToAddCode()) {
                        foreach ($addcodes as $code) {
                            $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
                            $quote = Mage::getSingleton('checkout/session')->getQuote();
                            if (!$giftVoucher->getId()) {
                                $codes = Mage::getSingleton('giftvoucher/session')->getCodes();
                                $codes[] = $code;
                                Mage::getSingleton('giftvoucher/session')->setCodes(array_unique($codes));
                                $session->addError($this->__('Gift card "%s" is invalid.', $code));
                                $max = Mage::helper('giftvoucher')->getGeneralConfig('maximum');
                                if($max-count($codes))
                                $session->addError($this->__('You have %d times remain to enter gift card code.',$max-count($codes)));
                            } else if ($giftVoucher->getBaseBalance() > 0
                                && $giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
                                && $giftVoucher->validate($quote->setQuote($quote))
                            ) {
                                if (Mage::helper('giftvoucher')->canUseCode($giftVoucher)) {
                                    $giftVoucher->addToSession($session);
                                    if ($giftVoucher->getCustomerId() == Mage::getSingleton('customer/session')->getCustomerId()
                                        && $giftVoucher->getRecipientName() && $giftVoucher->getRecipientEmail()
                                        && $giftVoucher->getCustomerId()
                                    ) {
                                        $session->addNotice($this->__('Gift card "%s" has been purchased to send to your friend.', Mage::helper('giftvoucher')->getHiddenCode($code)));
                                    }
                                    $session->addSuccess($this->__('Gift card "%s" has been applied successfully.', Mage::helper('giftvoucher')->getHiddenCode($code)));
                                } else {
                                    $session->addError($this->__('This Gift card limits the number of users', Mage::helper('giftvoucher')->getHiddenCode($code)));
                                }
                            } else {
                                $session->addError($this->__('Gift card "%s" is not avaliable', $code));
                            }
                        }
                    } else {
                        $session->addError($this->__('The maximum number of times to enter gift card code is %d!', Mage::helper('giftvoucher')->getGeneralConfig('maximum')));
                    }
                } else {
                    $session->addSuccess($this->__('Your Gift Card used to checkout has been updated successfully'));
                }
            } elseif ($session->getUseGiftCard()) {
                $session->setUseGiftCard(null);
                $session->addSuccess($this->__('Your gift card information has been removed successfully'));
            }
        }
        $this->_redirect('checkout/cart');
    }
    
    public function addgiftAction(){
        $addCodes = array();
    	if ($code = trim($this->getRequest()->getParam('code'))) {
            $addCodes[] = $code;
        }
        if ($code = trim($this->getRequest()->getParam('addcode'))) {
            $addCodes[] = $code;
        }
    	$result = array();
    	$max = Mage::helper('giftvoucher')->getGeneralConfig('maximum');
    	$codes = Mage::getSingleton('giftvoucher/session')->getCodes();
    	if (!count($addCodes)){
    		$errorMessage = Mage::helper('giftvoucher')->__('Invalid gift card code. ');
    		if ($max)
    			$errorMessage .= Mage::helper('giftvoucher')->__('You have %d times remain to enter gift card code.',$max-count($codes));
    		$result['error'] = $errorMessage;
    		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    		return;
    	}
    	if (!Mage::helper('giftvoucher')->isAvailableToAddCode()){
    		$giftVoucherBlock = $this->getLayout()->createBlock('giftvoucher/payment_form');
			$result['html']	= $giftVoucherBlock->toHtml();
    		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
			return;
    	}
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();
        foreach ($addCodes as $code) {
            $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
            if (!$giftVoucher->getId()){
                $codes[] = $code;
                $codes = array_unique($codes);
                Mage::getSingleton('giftvoucher/session')->setCodes($codes);
            }
            if ($giftVoucher->getId() && $giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
                && $giftVoucher->getBaseBalance() > 0 && $giftVoucher->validate($quote->setQuote($quote))
            ){
                if (Mage::helper('giftvoucher')->canUseCode($giftVoucher)) {
                    $giftVoucher->addToSession($session);
                    $updatepayment = ($quote->getGrandTotal() < 0.001);
                    $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
                    if ($updatepayment xor ($quote->getGrandTotal() < 0.001)) {
                        $result['updatepayment'] = 1;
                        break;
                    } else {
                        // $result['success'] = Mage::helper('giftvoucher')->__('Gift Voucher "%s" was applied to your order.',Mage::helper('giftvoucher')->getHiddenCode($giftVoucher->getGiftCode()));
                        if ($giftVoucher->getCustomerId() == Mage::getSingleton('customer/session')->getCustomerId()
                            && $giftVoucher->getRecipientName() && $giftVoucher->getRecipientEmail()
                            && $giftVoucher->getCustomerId()
                        ) {
                            if (!isset($result['notice'])) {
                                $result['notice'] = '';
                            } else {
                                $result['notice'] .= '<br/>';
                            }
                            $result['notice'] .= $this->__('Gift card "%s" has been purchased to send to your friend.', Mage::helper('giftvoucher')->getHiddenCode($code));
                        }
                        $result['html'] = 1;
                    }
                } else {
                    if (!isset($result['error'])) {
                        $result['error'] = '';
                    } else {
                        $result['error'] .= '<br/>';
                    }
                    $result['error'] .= $this->__('This Gift card limits the number of users', $code);
                }
            }else{
                if (isset($errorMessage)) {
                    $result['error'] = $errorMessage . '<br/>';
                } elseif (isset($result['error'])) {
                    $result['error'] .= '<br/>';
                } else {
                    $result['error'] = '';
                }
                $errorMessage = Mage::helper('giftvoucher')->__('Gift card "%s" is invalid.', $code);
                
                $maxErrorMessage = '';
                if ($max) {
                    $maxErrorMessage = Mage::helper('giftvoucher')->__('You have %d times remain to enter gift card code.',$max-count($codes));
                }
                $result['error'] .= $errorMessage . ' ' . $maxErrorMessage;
            }
        }
        if (isset($result['html']) && !isset($result['updatepayment'])) {
            $giftVoucherBlock = $this->getLayout()->createBlock('giftvoucher/payment_form');
            $result['html']	= $giftVoucherBlock->toHtml();
        }
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    public function removeAction(){
    	$session = Mage::getSingleton('checkout/session');
    	$code = trim($this->getRequest()->getParam('code'));
    	$codes = $session->getGiftCodes();
    	
    	$success = false;
    	if ($code && $codes){
    		$codesArray = explode(',',$codes);
    		foreach ($codesArray as $key => $value)
    			if ($value == $code){
    				unset($codesArray[$key]);
    				$success = true;
                    $giftMaxUseAmount = unserialize($session->getGiftMaxUseAmount());
                    if (is_array($giftMaxUseAmount) && array_key_exists($code, $giftMaxUseAmount)) {
                        unset($giftMaxUseAmount[$code]);
                        $session->setGiftMaxUseAmount(serialize($giftMaxUseAmount));
                    }
    				break;
   				}
    	}
    	
    	$result = array();
    	if ($success){
    		$codes = implode(',',$codesArray);
    		$session->setGiftCodes($codes);
            $updatepayment = ($session->getQuote()->getGrandTotal() < 0.001);
			$session->getQuote()->collectTotals()->save();
            if ($updatepayment xor ($session->getQuote()->getGrandTotal() < 0.001)) {
                $result['updatepayment'] = 1;
            } else {
                // $result['success'] = Mage::helper('giftvoucher')->__('Gift Voucher "%s" has been removed from your order.',Mage::helper('giftvoucher')->getHiddenCode($code));
                $giftVoucherBlock = $this->getLayout()->createBlock('giftvoucher/payment_form');
                $result['html']	= $giftVoucherBlock->toHtml();
            }
    	}else{
    		$result['error'] = Mage::helper('giftvoucher')->__('Gift card "%s" is not found.',$code);
   		}
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    /**
     * change use gift card to spend
     */
    public function giftcardAction(){
        $session = Mage::getSingleton('checkout/session');
        $session->setUseGiftCard($this->getRequest()->getParam('giftvoucher'));
        $result = array();
        $updatepayment = ($session->getQuote()->getGrandTotal() < 0.001);
        $session->getQuote()->collectTotals()->save();
        if ($updatepayment xor ($session->getQuote()->getGrandTotal() < 0.001)) {
            $result['updatepayment'] = 1;
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    public function updateAmountAction() {
        $session = Mage::getSingleton('checkout/session');
        $codes  = $session->getGiftCodes();
        
        $code   = $this->getRequest()->getParam('code');
        $amount = floatval($this->getRequest()->getParam('amount'));
        $result = array();
        if (in_array($code, explode(',', $codes))) {
            $giftMaxUseAmount = unserialize($session->getGiftMaxUseAmount());
            if (!is_array($giftMaxUseAmount)) {
                $giftMaxUseAmount = array();
            }
            $giftMaxUseAmount[$code] = $amount;
            $session->setGiftMaxUseAmount(serialize($giftMaxUseAmount));
            $updatepayment = ($session->getQuote()->getGrandTotal() < 0.001);
            $session->getQuote()->collectTotals()->save();
            if ($updatepayment xor ($session->getQuote()->getGrandTotal() < 0.001)) {
                $result['updatepayment'] = 1;
            } else {
                $giftVoucherBlock = $this->getLayout()->createBlock('giftvoucher/payment_form');
                $result['html']	= $giftVoucherBlock->toHtml();
                // $discounts = array_combine(explode(',', $codes), explode(',', $session->getCodesDiscount()));
                // $result['amount'] = Mage::helper('checkout')->formatPrice($discounts[$code]);
            }
        } else {
            $result['error'] = Mage::helper('giftvoucher')->__('Gift card "%s" is not added.',$code);
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    /**
     * change use gift card to spend
     */
    public function giftcardcreditAction(){
        if(!Mage::helper('giftvoucher')->getGeneralConfig('enablecredit'))
            return;
        $session = Mage::getSingleton('checkout/session');
        $session->setUseGiftCardCredit($this->getRequest()->getParam('giftcredit'));
        $result = array();
        $updatepayment = ($session->getQuote()->getGrandTotal() < 0.001);
        $session->getQuote()->collectTotals()->save();
        if ($updatepayment xor ($session->getQuote()->getGrandTotal() < 0.001)) {
            $result['updatepayment'] = 1;
        } else {
            $giftVoucherBlock = $this->getLayout()->createBlock('giftvoucher/payment_form');
            $result['html']	= $giftVoucherBlock->toHtml();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    public function creditamountAction() {
        if(!Mage::helper('giftvoucher')->getGeneralConfig('enablecredit'))
            return;
        $session = Mage::getSingleton('checkout/session');
        $result = array();
        $amount = floatval($this->getRequest()->getParam('amount'));
        if ($amount > -0.0001 && (abs($amount - $session->getMaxCreditUsed()) > 0.0001
            || !$session->getMaxCreditUsed())
        ) {
            $session->setMaxCreditUsed($amount);
            $updatepayment = ($session->getQuote()->getGrandTotal() < 0.001);
            $session->getQuote()->collectTotals()->save();
            if ($updatepayment xor ($session->getQuote()->getGrandTotal() < 0.001)) {
                $result['updatepayment'] = 1;
            } else {
                $giftVoucherBlock = $this->getLayout()->createBlock('giftvoucher/payment_form');
                $result['html']	= $giftVoucherBlock->toHtml();
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}

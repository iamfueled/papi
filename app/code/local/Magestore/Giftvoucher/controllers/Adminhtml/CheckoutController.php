<?php
class Magestore_Giftvoucher_Adminhtml_CheckoutController extends Mage_Adminhtml_Controller_Action
{
    public function removegiftAction(){
    	$session = Mage::getSingleton('checkout/session');
    	$code = trim($this->getRequest()->getParam('code'));
    	$codes = $session->getGiftCodes();
    	
    	$success = false;
    	if ($code && $codes){
    		$codesArray = explode(',',$codes);
    		foreach ($codesArray as $key => $value) {
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
    	}
    	
    	if ($success) {
    		$codes = implode(',', $codesArray);
    		$session->setGiftCodes($codes);
            Mage::getSingleton('adminhtml/session_quote')->addSuccess($this->__('Gift card "%s" has been removed successfully.', $code));
    	} else {
    		Mage::getSingleton('adminhtml/session_quote')->addError($this->__('Gift card "%s" not found!', $code));
   		}
    	$this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array()));
    }
    
    public function giftcardPostAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $session = Mage::getSingleton('checkout/session');
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            if (Mage::helper('giftvoucher')->getGeneralConfig('enablecredit', $quote->getStoreId()) && $request->getParam('giftvoucher_credit')) {
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
                    foreach ($addcodes as $code) {
                        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code);
                        $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
                        if ($giftVoucher->getBaseBalance() > 0
                            && $giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
                            && $giftVoucher->validate($quote->setQuote($quote))
                        ) {
                            $giftVoucher->addToSession($session);
                            if ($giftVoucher->getCustomerId() == Mage::getSingleton('adminhtml/session_quote')->getCustomerId()
                                && $giftVoucher->getRecipientName() && $giftVoucher->getRecipientEmail()
                                && $giftVoucher->getCustomerId()
                            ) {
                                Mage::getSingleton('adminhtml/session_quote')->addNotice($this->__('Gift card "%" has been sent to the customer\'s friend.', $code));
                            }
                            Mage::getSingleton('adminhtml/session_quote')->addSuccess($this->__('Gift card "%s" has been applied successfully.', $code));
                        } else {
                            Mage::getSingleton('adminhtml/session_quote')->addError($this->__('Gift card "%s" is not avaliable.', $code));
                        }
                    }
                } else {
                    Mage::getSingleton('adminhtml/session_quote')->addSuccess($this->__('The gift card has been updated successfully.'));
                }
            } elseif ($session->getUseGiftCard()) {
                $session->setUseGiftCard(null);
                Mage::getSingleton('adminhtml/session_quote')->addSuccess($this->__('Your Gift card has been removed successfully.'));
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array()));
    }
}

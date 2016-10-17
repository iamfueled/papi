<?php

class Magestore_Giftvoucher_Model_Total_Order_Creditmemo_Giftcardcredit extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
	public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo){
		$order = $creditmemo->getOrder();
		if ($order->getBaseUseGiftCreditAmount() && $order->getUseGiftCreditAmount()){
			$baseDiscount = $order->getBaseUseGiftCreditAmount();
			$discount = $order->getUseGiftCreditAmount();
			if ($creditmemo->getBaseGrandTotal() - $baseDiscount < 0){
				$creditmemo->setBaseUseGiftCreditAmount($creditmemo->getBaseGrandTotal());
				$creditmemo->setUseGiftCreditAmount($creditmemo->getGrandTotal());
				$creditmemo->setBaseGrandTotal(0);
				$creditmemo->setGrandTotal(0);
			}else{
				$creditmemo->setBaseUseGiftCreditAmount($baseDiscount);
				$creditmemo->setUseGiftCreditAmount($discount);
				$creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()-$baseDiscount);
				$creditmemo->setGrandTotal($creditmemo->getGrandTotal()-$discount);
			}
		}
	}
}
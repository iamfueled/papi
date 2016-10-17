<?php

class Magestore_Giftvoucher_Model_Total_Order_Invoice_Giftcardcredit extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
	public function collect(Mage_Sales_Model_Order_Invoice $invoice){
		$order = $invoice->getOrder();
		if ($order->getBaseUseGiftCreditAmount() && $order->getUseGiftCreditAmount()){
			$baseDiscount = $order->getBaseUseGiftCreditAmount();
			$discount = $order->getUseGiftCreditAmount();
			if ($invoice->getBaseGrandTotal() - $baseDiscount < 0){
				$invoice->setBaseUseGiftCreditAmount($invoice->getBaseGrandTotal());
				$invoice->setUseGiftCreditAmount($invoice->getGrandTotal());
				$invoice->setBaseGrandTotal(0);
				$invoice->setGrandTotal(0);
			}else{
				$invoice->setBaseUseGiftCreditAmount($baseDiscount);
				$invoice->setUseGiftCreditAmount($discount);
				$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()-$baseDiscount);
				$invoice->setGrandTotal($invoice->getGrandTotal()-$discount);
			}
		}
	}
}
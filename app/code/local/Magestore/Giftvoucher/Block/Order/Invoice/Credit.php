<?php
class Magestore_Giftvoucher_Block_Order_Invoice_Credit extends Mage_Core_Block_Template
{
	public function initTotals(){
		$orderTotalsBlock = $this->getParentBlock();
		$order = $orderTotalsBlock->getInvoice();
		if ($order->getUseGiftCreditAmount()){
			$orderTotalsBlock->addTotal(new Varien_Object(array(
				'code'	=> 'giftcardcredit',
				'label'	=> $this->__('Customer Credit'),
				'value'	=> -$order->getUseGiftCreditAmount(),
			)),'subtotal');
		}
	}
}
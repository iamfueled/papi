<?php
class Magestore_Giftvoucher_Block_Adminhtml_Order_Invoice_Credit extends Mage_Adminhtml_Block_Sales_Order_Totals_Item
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
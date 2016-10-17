<?php
class Magestore_Giftvoucher_Block_Adminhtml_Order_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals_Item
{
	public function initTotals(){
		$orderTotalsBlock = $this->getParentBlock();
		$order = $orderTotalsBlock->getOrder();
		if ($order->getGiftVoucherDiscount()){
			$orderTotalsBlock->addTotal(new Varien_Object(array(
				'code'	=> 'giftvoucher',
				'label'	=> $this->__('Gift Card (%s)',$order->getGiftCodes()),
				'value'	=> -$order->getGiftVoucherDiscount(),
			)),'subtotal');
		}
        if ($refund = $this->getGiftCardRefund($order)) {
            if ($order->getCustomerIsGuest() || !Mage::helper('giftvoucher')->getGeneralConfig('enablecredit', $order->getStoreId())) {
                $label = $this->__('Refund to customer gift card code used to check out');
            } else {
                $label = $this->__('Refund to customer credit balance');
            }
            $orderTotalsBlock->addTotal(new Varien_Object(array(
				'code'	=> 'giftcard_refund',
				'label'	=> $label,
				'value'	=> $refund,
                'area'  => 'footer',
			)),'subtotal');
        }
	}
    
    public function getGiftCardRefund($order) {
        $refund = 0;
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            $refund += $creditmemo->getGiftcardRefundAmount();
        }
        return $refund;
    }
}

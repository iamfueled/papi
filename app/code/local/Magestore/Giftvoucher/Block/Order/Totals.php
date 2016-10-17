<?php
class Magestore_Giftvoucher_Block_Order_Totals extends Mage_Core_Block_Template
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
                $label = $this->__('Refund to your Gift card code');
            } else {
                $label = $this->__('Refund to your credit balance');
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

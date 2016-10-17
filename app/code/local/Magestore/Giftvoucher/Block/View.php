<?php
class Magestore_Giftvoucher_Block_View extends Mage_Core_Block_Template
{
    public function getCustomerGift() {
        if (!$this->hasData('customer_gift')) {
            $this->setData('customer_gift',
                Mage::getModel('giftvoucher/customervoucher')->load(
                    $this->getRequest()->getParam('id')
                )
            );
        }
        return $this->getData('customer_gift');
    }
    
    public function getGiftVoucher() {
        if (!$this->hasData('gift_voucher')) {
            $customerGift = $this->getCustomerGift();
            $this->setData('gift_voucher',
                Mage::getModel('giftvoucher/giftvoucher')->load($customerGift->getVoucherId())
            );
        }
        return $this->getData('gift_voucher');
    }
    
    public function getCodeTxt($giftVoucher) {
    	return Mage::helper('giftvoucher')->getHiddenCode($giftVoucher->getGiftCode());
    }
    
    public function getBalanceFormat($giftVoucher) {
    	$currency = Mage::getModel('directory/currency')->load($giftVoucher->getCurrency());
    	return $currency->format($giftVoucher->getBalance());
    }
    
    public function getStatus($gifVoucher) {
    	$status = $gifVoucher->getStatus();
    	$statusArray = Mage::getSingleton('giftvoucher/status')->getOptionArray();
    	return $statusArray[$status];
    }
    
    public function checkSendFriendGiftCard($giftCard) {
        return ($giftCard->getRecipientName() && $giftCard->getRecipientEmail()
            && $giftCard->getCustomerId() == Mage::getSingleton('customer/session')->getCustomerId()
        );
    }
    
    /**
     * get shipment for gift card
     * 
     * @param Magestore_Giftvoucher_Model_Giftvoucher $giftCard
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipmentForGiftCard($giftCard) {
        $history = Mage::getResourceModel('giftvoucher/history_collection')
            ->addFieldToFilter('giftvoucher_id', $giftCard->getId())
            ->addFieldToFilter('action', Magestore_Giftvoucher_Model_Actions::ACTIONS_CREATE)
            ->getFirstItem();
        if (!$history->getOrderIncrementId() || !$history->getOrderItemId()) {
            return false;
        }
        $shipmentItem = Mage::getResourceModel('sales/order_shipment_item_collection')
            ->addFieldToFilter('order_item_id', $history->getOrderItemId())
            ->getFirstItem();
        if (!$shipmentItem || !$shipmentItem->getId()) {
            return false;
        }
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentItem->getParentId());
        if (!$shipment->getId()) {
            return false;
        }
        return $shipment;
    }
    
    /**
     * get History for Gift Card
     * 
     * @param Magestore_Giftvoucher_Model_Giftvoucher $giftCard
     * @return Magestore_Giftvoucher_Model_Mysql4_History_Collection
     */
    public function getGiftCardHistory($giftCard) {
        $collection = Mage::getResourceModel('giftvoucher/history_collection')
            ->addFieldToFilter('main_table.giftvoucher_id', $giftCard->getId())
            ->addFieldToFilter('main_table.customer_id', Mage::getSingleton('customer/session')->getCustomerId());
        $collection->getSelect()->order('main_table.created_at DESC');
        $collection->getSelect()
            ->joinLeft(array('o' => $collection->getTable('sales/order')),
                'main_table.order_increment_id = o.increment_id',
                array('order_id' => 'entity_id')
            );
        return $collection;
    }
    
    public function getActionName($history) {
        $actions = Mage::getSingleton('giftvoucher/actions')->getOptionArray();
        if (isset($actions[$history->getAction()])) {
            return $actions[$history->getAction()];
        }
        reset($actions);
        return current($actions);
    }
    
    public function getAmountFormat($giftVoucher) {
    	$currency = Mage::getModel('directory/currency')->load($giftVoucher->getCurrency());
    	return $currency->format($giftVoucher->getAmount());
    }
}

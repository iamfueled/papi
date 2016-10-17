<?php
class Magestore_Giftvoucher_Block_Cart_Giftcard extends Magestore_Giftvoucher_Block_Payment_Form
{
	public function _prepareLayout(){
        if (Mage::helper('giftvoucher')->getGeneralConfig('show_gift_card')) {
            $couponBlock = $this->getLayout()->getBlock('checkout.cart.coupon');
            $this->setData('coupon_html', $couponBlock->toHtml());
            $couponBlock->setTemplate('giftvoucher/giftcard/coupon.phtml');
        }
		return parent::_prepareLayout();
    }
}

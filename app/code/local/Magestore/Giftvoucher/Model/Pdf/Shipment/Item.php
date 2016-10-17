<?php
class Magestore_Giftvoucher_Model_Pdf_Shipment_Item extends Mage_Sales_Model_Order_Pdf_Items_Shipment_Default
{
	public function getItemOptions(){
		$result = parent::getItemOptions();
		$item = Mage::getModel('sales/order_item')->load($this->getItem()->getOrderItemId());
		
		if ($item->getProductType() != 'giftvoucher') return $result;
		
		if ($options = $item->getProductOptionByCode('info_buyRequest'))
			foreach (Mage::helper('giftvoucher')->getGiftVoucherOptions() as $code=>$label)
				if ($options[$code])
					$result[] = array(
						'label'	=> $label,
						'value'	=> $options[$code],
						'print_value'	=> $options[$code],
					);
		
		$giftVouchers = Mage::getModel('giftvoucher/giftvoucher')->getCollection()->addItemFilter($item->getId());
		if ($giftVouchers->getSize()){
			$giftVouchersCode = array();
			foreach ($giftVouchers as $giftVoucher){
				$currency = Mage::getModel('directory/currency')->load($giftVoucher->getCurrency());
				$balance = $giftVoucher->getBalance();
				if ($currency) $balance = $currency->format($balance,array(),false);
				$giftVouchersCode[] = $giftVoucher->getGiftCode().' ('.$balance.') ';
			}
			$codes = implode(' ',$giftVouchersCode);
			$result[] = array(
				'label'	=> Mage::helper('giftvoucher')->__('Gift Card Code'),
				'value'	=> $codes,
				'print_value'	=> $codes,
			);
		}
		
		return $result;
	}
}
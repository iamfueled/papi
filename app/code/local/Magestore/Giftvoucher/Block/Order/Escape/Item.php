<?php
class Magestore_Giftvoucher_Block_Order_Escape_Item extends Mage_Sales_Block_Order_Item_Renderer_Default
{
	public function getItemOptions(){
		$result = parent::getItemOptions();
		$item = $this->getOrderItem();
		
		if ($item->getProductType() != 'giftvoucher') return $result;
		
		if ($options = $item->getProductOptionByCode('info_buyRequest'))
			foreach (Mage::helper('giftvoucher')->getGiftVoucherOptions() as $code=>$label)
				if ($options[$code])
					$result[] = array(
						'label'	=> $label,
						'value'	=> $options[$code],
						'option_value'	=> $options[$code],
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
				'label'	=> $this->__('Gift Card Code'),
				'value'	=> $codes,
				'option_value'	=> $codes,
			);
		}
		
		return $result;
	}
}
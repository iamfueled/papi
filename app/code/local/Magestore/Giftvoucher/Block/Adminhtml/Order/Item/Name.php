<?php
class Magestore_Giftvoucher_Block_Adminhtml_Order_Item_Name extends Mage_Adminhtml_Block_Sales_Items_Column_Name
{
	public function getOrderOptions(){
		$result = parent::getOrderOptions();
		$item = $this->getItem();
		
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
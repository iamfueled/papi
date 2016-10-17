<?php
class Magestore_Giftvoucher_Block_Cart_Item extends Mage_Checkout_Block_Cart_Item_Renderer
{
	public function getProductOptions(){
		$options = parent::getProductOptions();
		if (Mage::helper('giftvoucher')->getInterfaceConfig('display'))
			foreach (Mage::helper('giftvoucher')->getGiftVoucherOptions() as $code=>$label)
				if ($option = $this->getItem()->getOptionByCode($code))
					$options[] = array(
						'label'	=> $label,
						'value'	=> $this->htmlEscape($option->getValue()),
					);
		return $options;
	}
}
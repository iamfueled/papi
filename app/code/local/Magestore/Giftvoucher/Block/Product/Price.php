<?php
class Magestore_Giftvoucher_Block_Product_Price extends Mage_Catalog_Block_Product_Price
{
	public function displayBothPrices(){
		return $this->helper('tax')->displayBothPrices();
	}
}
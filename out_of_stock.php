<?php
	/**
	 * @author      MagePsycho <info@magepsycho.com>
	 * @website     http://www.magepsycho.com
	 * @category    Export / Import
	 */
	$mageFilename = 'app/Mage.php';
	require_once $mageFilename;
	Mage::setIsDeveloperMode(true);
	ini_set('display_errors', 1);
	umask(0);
	Mage::app('admin');
	Mage::register('isSecureArea', 1);
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

	set_time_limit(0);
	ini_set('memory_limit','1024M');
	
	
	$stockCollection = Mage::getModel('cataloginventory/stock_item')->getCollection()
       ->addFieldToFilter('is_in_stock', 0);
       
   $productIds = array();
           
   foreach ($stockCollection as $item) {
       $productIds[] = $item->getOrigData('product_id');
   }
       
   $productCollection = Mage::getModel('catalog/product')->getCollection()
       ->addIdFilter($productIds)
       ->addAttributeToSelect('name');
                   
   $products = array();
           
   foreach ($productCollection as $product) {
       $products[] = $product->getData('name');
   }
       
   die(print_r($products));
?>
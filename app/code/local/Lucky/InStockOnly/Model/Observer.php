<?php

class Lucky_InStockOnly_Model_Observer {

  /**
   * Observes the catalog_block_product_list_collection event
   */
  public function addInStockOnlyFilter($observer){
    $observer->getEvent()->getCollection()
      ->joinField('stock_status','cataloginventory/stock_status','stock_status',
        'product_id=entity_id', array(
          'stock_status' => Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK,
          'website_id' => Mage::app()->getWebsite()->getWebsiteId(),
        ))
    ;
  }
}

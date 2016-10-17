<?php
public function massDeleteAction() {
 // get the product collection
$products = Mage::getModel('catalog/product')->getCollection();
//load all the product by id in the for loop
foreach ($products as $_product){
   $product = Mage::getModel('catalog/product')->load($_product->getId());
// getOffertext() is an attribute.
   $text = $product->getOffertext();
if(($text=='Today Only') || ($text=='Deal Of the Day')||($text=='')){
  continue;
 }
$product->setOffertext('');
$product->setSpecialFromDate('');
$product->setSpecialToDate('');
$product->setSpecialPrice('');
$product->save();
}  
}
?>
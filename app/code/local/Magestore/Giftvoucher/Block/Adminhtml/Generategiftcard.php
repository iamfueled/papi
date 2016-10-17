<?php
class Magestore_Giftvoucher_Block_Adminhtml_Generategiftcard extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_generategiftcard';
    $this->_blockGroup = 'giftvoucher';
    $this->_headerText = Mage::helper('giftvoucher')->__('Template Manager');
    $this->_addButtonLabel = Mage::helper('giftvoucher')->__('Add Template');
    parent::__construct();
  }
}
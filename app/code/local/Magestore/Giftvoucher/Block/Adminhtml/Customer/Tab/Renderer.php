<?php

class Magestore_Giftvoucher_Block_Adminhtml_Customer_Tab_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row){
		return sprintf('<a href="%s" title="%s">%s</a>',
			$this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getOrderId())),
            Mage::helper('giftvoucher')->__('View Order Detail'),
            $row->getOrderNumber()
		);
	}
}
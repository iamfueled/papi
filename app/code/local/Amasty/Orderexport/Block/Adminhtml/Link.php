<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Link extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'amorderexport';
        $this->_controller = 'adminhtml_link';
        $this->_headerText = Mage::helper('amorderexport')->__('Connections');
        parent::__construct();
        $this->_updateButton('add', 'label', Mage::helper('amorderexport')->__('Add New Connection'));
    }
}
<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Link_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId   = 'link_id';
        $this->_blockGroup = 'amorderexport';
        $this->_controller = 'adminhtml_link';

        parent::__construct();

        $this->_updateButton('save',   'label', Mage::helper('amorderexport')->__('Save Connection'));
        $this->_updateButton('delete', 'label', Mage::helper('amorderexport')->__('Delete Connection'));
    }
    
    public function getHeaderText()
    {
        if (Mage::registry('amorderexport_link')->getId()) {
            return Mage::helper('amorderexport')->__("Edit Connection For Table '%s'", $this->htmlEscape(Mage::registry('amorderexport_link')->getTableName()));
        }
        else {
            return Mage::helper('amorderexport')->__('New Connection');
        }
    }
}
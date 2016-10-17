<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Link_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('amorderexport_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('amorderexport')->__('Connection Configuration'));
    }
    
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('amorderexport')->__('Connection Configuration'),
            'title'     => Mage::helper('amorderexport')->__('Connection Configuration'),
            'content'   => $this->getLayout()->createBlock('amorderexport/adminhtml_link_edit_tab_main')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}
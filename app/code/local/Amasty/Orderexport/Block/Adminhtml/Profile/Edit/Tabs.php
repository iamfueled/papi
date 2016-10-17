<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('amorderexport_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('amorderexport')->__('Profile Configuration'));
    }
    
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('amorderexport')->__('Profile Configuration'),
            'title'     => Mage::helper('amorderexport')->__('Profile Configuration'),
            'content'   => $this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit_tab_main')->toHtml(),
        ));
        
        $this->addTab('static_section', array(
            'label'     => Mage::helper('amorderexport')->__('Static Fields'),
            'title'     => Mage::helper('amorderexport')->__('Static Fields'),
            'content'   => $this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit_tab_static')->toHtml(),
        ));
        
        if (Mage::registry('amorderexport_profile')->getId())
        {
            $this->addTab('run_section', array(
                'label'     => Mage::helper('amorderexport')->__('Run Profile'),
                'title'     => Mage::helper('amorderexport')->__('Run Profile'),
                'content'   => $this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit_tab_run')->toHtml(),
            ));
            
            $this->addTab('history_section', array(
                'label'     => Mage::helper('amorderexport')->__('Run History'),
                'title'     => Mage::helper('amorderexport')->__('Run History'),
                'content'   => $this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit_tab_history')->toHtml(),
            ));
        }
        return parent::_beforeToHtml();
    }
}
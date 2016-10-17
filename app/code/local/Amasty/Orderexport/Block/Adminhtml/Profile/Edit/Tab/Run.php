<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Tab_Run extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getProfileId()
    {
        return Mage::registry('amorderexport_profile')->getId();
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('amorderexport/run.phtml');
        return $this;
    }
    
    public function getRunButtonHtml()
    {
        $html = '';

        $html .= $this->getLayout()->createBlock('adminhtml/widget_button')->setType('button')
            ->setClass('save')->setLabel($this->__('Run Profile'))
            ->setOnClick('runProfile(true)')
            ->toHtml();

        return $html;
    }
    
    public function getTabLabel()
    {
        return Mage::helper('amorderexport')->__('Run Profile');
    }
    
    public function getTabTitle()
    {
        return Mage::helper('amorderexport')->__('Run Profile');
    }
    
    public function canShowTab()
    {
        if ($this->getProfileId())
        {
            return true;
        }
        return false;
    }
    
    public function isHidden()
    {
        return false;
    }
}

<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Tab_Static extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getProfileId()
    {
        return Mage::registry('amorderexport_profile')->getId();
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('amorderexport/static.phtml');
        return $this;
    }
    
    public function getFieldsCount()
    {
        return 10;
    }
    
    public function getStaticFields()
    {
        $collection = Mage::getModel('amorderexport/static_field')->getCollection();
        $collection->addFieldToFilter('profile_id', $this->getProfileId());
        $collection->getSelect()->order('entity_id');
        $fields     = array();
        if ($collection->getSize() > 0)
        {
            foreach ($collection as $field)
            {
                $fields[] = array(
                    'label'    => $field->getLabel(),
                    'value'    => $field->getValue(),
                    'position' => $field->getPosition(),
                );
            }
        }
        return $fields;
    }
    
    public function getTabLabel()
    {
        return Mage::helper('amorderexport')->__('Static Fields');
    }
    
    public function getTabTitle()
    {
        return Mage::helper('amorderexport')->__('Static Fields');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
}

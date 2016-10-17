<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId   = 'profile_id';
        $this->_blockGroup = 'amorderexport';
        $this->_controller = 'adminhtml_profile';

        parent::__construct();

        $this->_updateButton('save',   'label', Mage::helper('amorderexport')->__('Save Profile'));
        $this->_updateButton('delete', 'label', Mage::helper('amorderexport')->__('Delete Profile'));
    }
    
    protected function _prepareLayout()
    {
        $this->_addButton('save_and_continue', array(
            'label'     => Mage::helper('amorderexport')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit(\''.$this->_getSaveAndContinueUrl().'\')',
            'class'     => 'save'
        ), 10);
        
        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
        
        return parent::_prepareLayout();
    }
    
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'  => true,
            'back'      => 'edit',
        ));
    }
    
    public function getHeaderText()
    {
        if (Mage::registry('amorderexport_profile')->getId()) {
            return Mage::helper('amorderexport')->__("Edit Profile '%s' (ID: %s)", $this->htmlEscape(Mage::registry('amorderexport_profile')->getName()), Mage::registry('amorderexport_profile')->getId());
        }
        else {
            return Mage::helper('amorderexport')->__('New Profile');
        }
    }
}
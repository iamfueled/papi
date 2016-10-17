<?php

class Magestore_Giftvoucher_Block_Adminhtml_Giftvoucher_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'giftvoucher';
        $this->_controller = 'adminhtml_giftvoucher';
        
        $this->_updateButton('save', 'label', Mage::helper('giftvoucher')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('giftvoucher')->__('Delete'));
        
        $this->_addButton('sendemail', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Send Email'),
            'onclick'   => 'saveAndSendEmail()',
            'class'     => 'save',
        ), -100);
        
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);
        
        $this->_addButton('print', array(
            'label'     => Mage::helper('adminhtml')->__('Print'),
            'onclick'   => "setLocation('".$this->getUrl('*/*/print', array('id' => $this->getRequest()->getParam('id')))."')",
            'class'     => 'save',
        ), -100);
        
        $this->_formScripts[] = "
            function saveAndSendEmail(){
                editForm.submit('".$this->getUrl('*/*/save',array(
					'id' => $this->getRequest()->getParam('id'),
					'back' => 'edit',
					'sendemail' => 'now'
					))."');
            }
            
            function saveAndContinueEdit(){
                editForm.submit('".$this->getUrl('*/*/save',array(
					'id' => $this->getRequest()->getParam('id'),
					'back' => 'edit'
					))."');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('giftvoucher_data') && Mage::registry('giftvoucher_data')->getId() ) {
            return Mage::helper('giftvoucher')->__("Edit Gift Code '%s'", $this->htmlEscape(Mage::registry('giftvoucher_data')->getGiftCode()));
        } else {
            return Mage::helper('giftvoucher')->__('New Gift Code');
        }
    }
}
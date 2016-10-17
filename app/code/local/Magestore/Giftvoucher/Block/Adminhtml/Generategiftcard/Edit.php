<?php

class Magestore_Giftvoucher_Block_Adminhtml_Generategiftcard_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'giftvoucher';
        $this->_controller = 'adminhtml_generategiftcard';

        $this->_updateButton('save', 'label', Mage::helper('giftvoucher')->__('Save Template'));
        $this->_updateButton('delete', 'label', Mage::helper('giftvoucher')->__('Delete Template'));
        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
            ), -100);

        if ($this->getTemplateGenerate()->getIsGenerated()) {
            $this->_removeButton('save');
            $this->_removeButton('reset');
            $this->_removeButton('saveandcontinue');
            $this->_addButton('duplicate', array(
                'label' => Mage::helper('adminhtml')->__('Duplicate'),
                'onclick' => 'duplicate()',
                'class' => 'save',
                ), -100);
        } else {
            $this->_addButton('generate', array(
                'label' => Mage::helper('adminhtml')->__('Save And Generate'),
                'onclick' => 'saveAndGenerate()',
                'class' => 'save',
                ), -100);
        }
        $this->_formScripts[] = "            
            function saveAndContinueEdit(){
                editForm.submit('" . $this->getUrl('*/*/save', array(
                'id' => $this->getRequest()->getParam('id'),
                'back' => 'edit'
            )) . "');
            }
            function saveAndGenerate(){
                editForm.submit('" . $this->getUrl('*/*/generate', array(
                'id' => $this->getRequest()->getParam('id')
            )) . "');
            }
            function duplicate(){
                editForm.submit('" . $this->getUrl('*/*/duplicate', array(
                'id' => $this->getRequest()->getParam('id')
            )) . "');
            }
        ";
    }

    public function getHeaderText() {
        if (Mage::registry('template_data') && Mage::registry('template_data')->getId()) {
            return Mage::helper('giftvoucher')->__("Edit Template  '%s'", $this->htmlEscape(Mage::registry('template_data')->getTemplateName()));
        } else {
            return Mage::helper('giftvoucher')->__('New Template');
        }
    }

    public function getTemplateGenerate() {
        if (Mage::registry('template_data'))
            return Mage::registry('template_data');
        return Mage::getModel('giftvoucher/template');
    }

}
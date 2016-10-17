<?php

class Magestore_Giftvoucher_Block_Adminhtml_Generategiftcard_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        $this->setId('giftproduct_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('giftvoucher')->__('Template Information'));
    }

    protected function _beforeToHtml() {
        $this->addTab('form_section', array(
            'label' => Mage::helper('giftvoucher')->__('General Information'),
            'title' => Mage::helper('giftvoucher')->__('General Information'),
            'content' => $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_form')->toHtml(),
        ));
        $this->addTab('conditions_section', array(
            'label' => Mage::helper('giftvoucher')->__('Conditions'),
            'title' => Mage::helper('giftvoucher')->__('Conditions'),
            'content' => $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_conditions')->toHtml(),
        ));
        
        $is_generated = $this->getTemplateGenerate()->getIsGenerated();
        if ($is_generated) {
            $this->addTab('form_giftcode', array(
                'label' => Mage::helper('giftvoucher')->__('Gift Codes Information'),
                'title' => Mage::helper('giftvoucher')->__('Gift Codes Information'),
                'content' => $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_giftcodelist')->toHtml(),
            ));
        }

        return parent::_beforeToHtml();
    }
    public function getTemplateGenerate() {
        if (Mage::registry('template_data'))
            return Mage::registry('template_data');
        return Mage::getModel('giftvoucher/template');
    }

}
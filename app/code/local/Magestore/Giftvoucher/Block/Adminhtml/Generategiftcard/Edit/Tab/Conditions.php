<?php

class Magestore_Giftvoucher_Block_Adminhtml_Generategiftcard_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        if (Mage::registry('template_data')) {
            $model = Mage::registry('template_data');
        } else {
            $id = $this->getRequest()->getParam('id');
            $model = Mage::getModel('giftvoucher/template')->load($id);
        }
        $data = $model->getData();

        $model->setData('conditions', $model->getData('conditions_serialized'));

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('giftvoucher_');
        
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newConditionHtml/form/giftvoucher_conditions_fieldset'));

        $fieldset = $form->addFieldset('conditions_fieldset', array('legend' => Mage::helper('giftvoucher')->__('Use the gift card only if the following conditions are met (leave blank for all products)')))->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', array(
            'name' => 'conditions',
            'label' => Mage::helper('giftvoucher')->__('Conditions'),
            'title' => Mage::helper('giftvoucher')->__('Conditions'),
            'required' => true,
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        $form->setValues($data);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}
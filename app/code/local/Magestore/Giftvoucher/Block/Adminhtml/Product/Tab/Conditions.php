<?php

class Magestore_Giftvoucher_Block_Adminhtml_Product_Tab_Conditions
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _prepareForm() {
        $product = Mage::registry('current_product');
        $model = Mage::getSingleton('giftvoucher/product');
        if (!$model->getId() && $product->getId()) {
            $model->loadByProduct($product);
        }
        $data = $model->getData();
        $model->setData('conditions', $model->getData('conditions_serialized'));
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('giftvoucher_');
        
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newConditionHtml/form/giftvoucher_conditions_fieldset'));

        $fieldset = $form->addFieldset('conditions_fieldset', array('legend' => Mage::helper('giftvoucher')->__('Use the Gift Card only if the following conditions are met (leave blank for all products)')))->setRenderer($renderer);

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

    public function getTabLabel() {
        return Mage::helper('giftvoucher')->__('Gift Card Conditions');
    }

    public function getTabTitle() {
        return Mage::helper('giftvoucher')->__('Gift Card Conditions');
    }

    public function canShowTab() {
        if (Mage::registry('current_product')->getTypeId() == 'giftvoucher') {
            return true;
        }
        return false;
    }

    public function isHidden() {
        if (Mage::registry('current_product')->getTypeId() == 'giftvoucher') {
            return false;
        }
        return true;
    }
}

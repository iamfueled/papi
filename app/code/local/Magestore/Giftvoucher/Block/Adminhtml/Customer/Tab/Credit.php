<?php

class Magestore_Giftvoucher_Block_Adminhtml_Customer_Tab_Credit extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    protected $_customer_credit;

    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        
        $fieldset = $form->addFieldset('creditgiftcard_fieldset', array('legend' => Mage::helper('giftvoucher')->__('Customer Credit Information')));

        $fieldset->addField('credit_balance', 'note', array(
            'label' => Mage::helper('giftvoucher')->__('Balance'),
            'title' => Mage::helper('giftvoucher')->__('Balance'),
            'text' => $this->getBalanceCredit(),
        ));
        $fieldset->addField('change_balance', 'text', array(
            'label' => Mage::helper('giftvoucher')->__('Change Balance'),
            'title' => Mage::helper('giftvoucher')->__('Change Balance'),
            'name' => 'change_balance',
            'note' => Mage::helper('giftvoucher')->__('Add or subtract customer\'s balance. For ex: 99 or -99.'),
        ));
        
        $form->addFieldset('balance_history_fieldset', array('legend' => Mage::helper('giftvoucher')->__('Balance History')))->setRenderer($this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset')->setTemplate('giftvoucher/balancehistory.phtml'));
        
        
        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getCredit() {
        if (is_null($this->_customer_credit)) {
            $customerId = Mage::registry('current_customer')->getId();
            $this->_customer_credit = Mage::getModel('giftvoucher/credit')->getCreditByCustomerId($customerId);
        }
        return $this->_customer_credit;
    }

    public function getTabLabel() {
        return Mage::helper('giftvoucher')->__('Customer Credit');
    }

    public function getTabTitle() {
        return Mage::helper('giftvoucher')->__('Customer Credit');
    }

    public function canShowTab() {
        if (Mage::registry('current_customer')->getId()) {
            return true;
        }
        return false;
    }

    public function isHidden() {
        if (Mage::registry('current_customer')->getId()) {
            return false;
        }
        return true;
    }

    public function getBalanceCredit() {
        $currency = Mage::getModel('directory/currency')->load($this->getCredit()->getCurrency());
        return $currency->format($this->getCredit()->getBalance());
    }

}
<?php

class Magestore_Giftvoucher_Block_Adminhtml_Generategiftcard_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
	  $form = new Varien_Data_Form();
      $this->setForm($form);
      
      if(Mage::registry('template_data'))
			$data = Mage::registry('template_data')->getData();
      
      $fieldset = $form->addFieldset('generategiftcard_form', array('legend'=>Mage::helper('giftvoucher')->__('General Information')));
      $disabled=FALSE;
      if ($data['is_generated'])
          $disabled=TRUE;
      $fieldset->addField('template_name', 'text', array(
          'label'     => Mage::helper('giftvoucher')->__('Template Name'),
          'required'  => true,
          'name'      => 'template_name',
          'disabled'  => $disabled,
      ));
     $note=Mage::helper('giftvoucher')->__('Pattern examples:<br/><strong>[A.8] : 8 alpha chars<br/>[N.4] : 4 numerics<br/>[AN.6] : 6 alphanumeric<br/>GIFT-[A.4]-[AN.6] : GIFT-ADFA-12NF0O</strong>');
      $fieldset->addField('pattern', 'text', array(
          'label'     => Mage::helper('giftvoucher')->__('Gift code Pattern'),
          'required'  => true,
          'name'      => 'pattern',
          'value'     => Mage::helper('giftvoucher')->getGeneralConfig('pattern'),
          'note'    =>$note,
          'disabled'  => $disabled,
      ));
      
      $fieldset->addField('balance', 'text', array(
          'label'     => Mage::helper('giftvoucher')->__('Gift code value'),
          'required'  => true,
          'name'      => 'balance',
          'disabled'  => $disabled,
      ));
     
      $fieldset->addField('currency', 'select', array(
          'label'     => Mage::helper('giftvoucher')->__('Currency'),
          'required'  => false,
          'name'      => 'currency',
          'value'     => Mage::app()->getStore()->getDefaultCurrencyCode(),
          'values'    => Mage::helper('giftvoucher')->getAllowedCurrencies(),
          'disabled'  => $disabled,
      ));
     
      $fieldset->addField('expired_at', 'date', array(
          'label'     => Mage::helper('giftvoucher')->__('Expired at'),
          'required'  => false,
          'name'      => 'expired_at',
          'image'     => $this->getSkinUrl('images/grid-cal.gif'),
          'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
          'format'    => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
          'disabled'  => $disabled,
      ));
      
      $fieldset->addField('amount', 'text', array(
          'label'     => Mage::helper('giftvoucher')->__('Amount'),
          'required'  => true,
          'name'      => 'amount',
          'disabled'  => $disabled,
      ));
      
//      $fieldset->addField('day_to_send', 'date', array(
//          'label'     => Mage::helper('giftvoucher')->__('Day To Send'),
//          'required'  => false,
//          'name'      => 'day_to_send',
//          'image'     => $this->getSkinUrl('images/grid-cal.gif'),
//          'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
//          'format'    => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
//          'disabled'  => $disabled,
//      ));

      $fieldset->addField('store_id', 'select', array(
          'label'     => Mage::helper('giftvoucher')->__('Store view'),
          'name'      => 'store_id',
          'required'  => false,
          'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
          'disabled'  => $disabled,
      ));
      
      $form->setValues($data);
      return parent::_prepareForm();
  }
}
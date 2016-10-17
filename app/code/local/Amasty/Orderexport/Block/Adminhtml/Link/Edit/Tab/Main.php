<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Link_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _prepareForm()
    {
        /* @var $model Amasty_Flags_Model_Flag */
        $model = Mage::registry('amorderexport_link');

        $form = new Varien_Data_Form();

        
        /**
        * 1. FIELDSET: Profile Information
        */
        
        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('amorderexport')->__('Connection Information')));

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', array(
                'name' => 'entity_id',
            ));
        }

        $fieldset->addField('table_name', 'text', array(
            'name'      => 'table_name',
            'label'     => Mage::helper('amorderexport')->__('Table Name'),
            'title'     => Mage::helper('amorderexport')->__('Table Name'),
            'note'      => $this->__('Indicate table, data from which you would like to add to export.'),
            'required'  => true,
        ));
        
        $fieldset->addField('alias', 'text', array(
            'name'      => 'alias',
            'label'     => Mage::helper('amorderexport')->__('Alias'),
            'title'     => Mage::helper('amorderexport')->__('Alias'),
            'note'      => $this->__('If empty, table name will be used. This value will be added to the names of fields from the exported table.'),
        ));
        
        $fieldset->addField('base_key', 'text', array(
            'name'      => 'base_key',
            'label'     => Mage::helper('amorderexport')->__('Base Table Key'),
            'title'     => Mage::helper('amorderexport')->__('Base Table Key'),
            'note'      => $this->__('Indicate field from sales_flat_order table based on which the foreign table will be joined (in most cases it is entity_id field).'),
            'required'  => true,
        ));
        
        $fieldset->addField('referenced_key', 'text', array(
            'name'      => 'referenced_key',
            'label'     => Mage::helper('amorderexport')->__('Referenced Table Key'),
            'title'     => Mage::helper('amorderexport')->__('Referenced Table Key'),
            'note'      => $this->__('Field from the foreign table (the one indicated in the Table Name *), by which the table will be joined to the order table.'),
            'required'  => true,
        ));
        
        $fieldset->addField('comment', 'text', array(
            'name'      => 'comment',
            'label'     => Mage::helper('amorderexport')->__('Comment'),
            'title'     => Mage::helper('amorderexport')->__('Comment'),
        ));
        

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    public function getTabLabel()
    {
        return Mage::helper('amorderexport')->__('Connection Configuration');
    }
    
    public function getTabTitle()
    {
        return Mage::helper('amorderexport')->__('Connection Configuration');
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

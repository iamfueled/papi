<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Helper_Profile extends Mage_Core_Helper_Abstract
{
    public function getDataFormats()
    {
        $formats = array(
            array(
                'value' => 'csv',
                'label' => $this->__('CSV - Comma Separated Values'),
            ),
            array(
                'value' => 'xml',
                'label' => $this->__('MS Excel XML'),
            ),
        );
        return $formats;
    }
    
    public function getExportScope()
    {
        $formats = array(
            array(
                'value' => '1',
                'label' => $this->__('Export All Fields'),
            ),
            array(
                'value' => '0',
                'label' => $this->__('Export Specified Fields Only'),
            ),
        );
        return $formats;
    }
    
    public function getYesNo()
    {
        $yn = array(
            array(
                'value' => '1',
                'label' => $this->__('Yes'),
            ),
            array(
                'value' => '0',
                'label' => $this->__('No'),
            ),
        );
        return $yn;
    }
    
    public function getFieldsSelectHtml()
    {
        $html   = '<select name="FIELDS_SELECT_NAME" id="FIELDS_SELECT_ID" class="select" style="width: 180px;" onchange="javascript: onSelectMapping(this);">';
        $html  .= '<option value=""></option>';
        $fields = Mage::helper('amorderexport/fields')->getFieldsForSelect();
        foreach ($fields as $type => $typeFields)
        {
            foreach ($typeFields as $field)
            {
                $html .= '<option value="' . $type . '.' . $field . '">' . $type . '.' . $field . '</option>';
            }
        }
        $html   .= '</select>';
        return $html;
    }
    
    public function getNextIncrementId($lastIncrementId)
    {
        $lastId = Mage::getModel('sales/order')->load($lastIncrementId, 'increment_id')->getId();
        if (!$lastId)
        {
            return '';
        }
        $collection = Mage::getResourceModel('sales/order_collection');
        $collection->getSelect()->where('entity_id > "' . $lastId . '"');
        $collection->getSelect()->order('entity_id ASC');
        $collection->getSelect()->limit(1);
        $collection->load();
        if ($collection->getSize() > 0)
        {
            foreach ($collection as $order)
            {
                return $order->getIncrementId();
            }
        }
        return '';
    }
    
    public function getNextInvloceIncrementId($lastIncrementId)
    {
        $lastId = Mage::getModel('sales/order_invoice')->load($lastIncrementId, 'increment_id')->getId();
//        if (!$lastId)
//        {
//            return '';
//        }
        $collection = Mage::getResourceModel('sales/order_invoice_collection');
        $collection->getSelect()->where('entity_id > "' . $lastId . '"');
        $collection->getSelect()->order('entity_id ASC');
        $collection->getSelect()->limit(1);
        $collection->load();
        if ($collection->getSize() > 0)
        {
            foreach ($collection as $order)
            {
                return $order->getIncrementId();
            }
        }
        return '';
    }
}
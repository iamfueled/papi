<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Profile_Field extends Mage_Core_Model_Abstract
{
    protected $_handlers = array();
    protected $_collection = null;
    
    protected function _construct()
    {
        $this->_init('amorderexport/profile_field');
    }
    
    public function addProfileFields($profile, $fieldsDatabase, $fieldsExport, $fieldsHandlers, $fieldsOrder)
    {
        if (!$profile->getId())
        {
            return false;
        }
        $this->clearProfileFields($profile);
        if (!$fieldsDatabase || !$fieldsExport)
        {
            return false;
        }
//        $fieldsCollection = Mage::getResourceModel('amorderexport/profile_field_collection');
        foreach ($fieldsDatabase as $i => $fieldDb)
        {
            if ($fieldDb)
            {
                $data = array(
                    'profile_id'    => $profile->getId(),
                    'mapped_name'   => $fieldsExport[$i],
                    'sorting_order' => $fieldsOrder[$i],
                );
                if (isset($fieldsHandlers[$i]))
                {
                    $data['handler'] = $fieldsHandlers[$i];
                }
                list($data['field_table'], $data['field_name']) = explode('.', $fieldDb);
                $field = Mage::getModel('amorderexport/profile_field')->setData($data);
                $field->save();
//                $fieldsCollection->addItem($field);
            }
        }
//        $fieldsCollection->save();
        return true;
    }
    
    /**
    * Removing all field associations for specified profile
    * 
    * @param Amasty_Orderexport_Model_Profile $profile
    */
    public function clearProfileFields($profile)
    {
        $connection = Mage::getSingleton('core/resource') ->getConnection('core_write');
        $sql = 'DELETE FROM `' . $this->getResource()->getTable('amorderexport/profile_field') . '` WHERE `profile_id` = "' . $profile->getId() . '" ';
        $connection->query($sql);
    }
    
    public function getFields($profile)
    {
        $collection = $this->getCollection()->addFieldToFilter('profile_id', $profile->getId())->load();
        return $collection;
    }
    
    public function getFieldsForSelect($profile)
    {
        $collection = $this->getCollection()->addFieldToFilter('profile_id', $profile->getId());
        $collection->getSelect()->order('sorting_order');
        $collection->load();
        $this->_collection = $collection;
        $fields = array();
        if ($collection->getSize() > 0)
        {
            foreach ($collection as $field)
            {
                $fields[$field->getFieldTable()][$field->getMappedName()] = $field->getFieldName();
                if ($field->getHandler())
                {
                    $this->_handlers[$field->getMappedName()] = $field->getHandler();
                }
            }
        }
        return $fields;
    }
    
    public function getFieldsSorting($profile)
    {
        if (!$this->_collection)
        {
            $collection = $this->getCollection()->addFieldToFilter('profile_id', $profile->getId());
            $collection->load();
        } else
        {
            $collection = $this->_collection;
        }
        $sorting = array();
        if ($collection->getSize() > 0)
        {
            foreach ($collection as $field)
            {
                $sorting[$field->getMappedName()] = $field->getSortingOrder();
            }
        }
        return $sorting;
    }
    
    /**
    * MUST be loaded in getFieldsForSelect first.
    * 
    */
    public function getHandlers()
    {
        return $this->_handlers;
    }
}

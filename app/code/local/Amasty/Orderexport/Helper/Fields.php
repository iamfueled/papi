<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Helper_Fields extends Mage_Core_Helper_Abstract
{
    protected $_fieldModels = array(
        'amorderexport/fields_static', // static fields model should always go first in the array
        'amorderexport/fields_linked',
    );
    
    protected function _getFieldModels()
    {
        return $this->_fieldModels;
    }
    
    public function getFieldsForSelect()
    {
        $fields = array();
        foreach ($this->_getFieldModels() as $modelName)
        {
            $fields += Mage::getModel($modelName)->get();
        }
        return $fields;
    }
    
    public function getAllTables()
    {
        $tables = array();
        foreach ($this->_getFieldModels() as $modelName)
        {
            $tables += Mage::getModel($modelName)->getTables();
        }
        return $tables;
    }
}
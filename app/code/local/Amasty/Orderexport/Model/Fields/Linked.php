<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Fields_Linked extends Amasty_Orderexport_Model_Fields_Abstract
{
    protected function _getLinksCollection()
    {
        if (!Mage::registry('amorderexport_links_collection'))
        {
            $collection = Mage::getResourceModel('amorderexport/link_collection')->load();
            Mage::register('amorderexport_links_collection', $collection);
        }
        return Mage::registry('amorderexport_links_collection');
    }
    
    public function get()
    {
        $linksCollection = $this->_getLinksCollection();
        $fields          = array();
        
        if ($linksCollection->getSize() > 0) 
        {
            foreach ($linksCollection as $link)
            {
                $fields[$link->getAlias()] = $this->_getColumns($link->getAlias());
            }
        }
        
        return $fields;
    }
    
    public function getTables($table = '')
    {
        $linksCollection = $this->_getLinksCollection();
        $tables          = array();
        
        if ($linksCollection->getSize() > 0) 
        {
            foreach ($linksCollection as $link)
            {
                $tables[$link->getAlias()] = array(
                    'table' => Mage::getModel('core/resource')->getTableName($link->getTableName()),
                    'join'  => '`order`.`' . $link->getBaseKey() . '` = `' . $link->getAlias() . '`.`' . $link->getReferencedKey() . '`',
                );
            }
        }
        
        if ($table && isset($tables[$table]))
        {
            return $tables[$table];
        }
        return $tables;
    }
}

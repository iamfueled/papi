<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Mysql4_Static_Field extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('amorderexport/static_field', 'entity_id');
    }
    
    public function clearFieldsForProfile($profileId)
    {
        $query = 'DELETE FROM `' . $this->getTable('amorderexport/static_field') . '` WHERE profile_id = "' . intval($profileId) . '"';
        $this->_getConnection('core_write')->query($query);
    }
}
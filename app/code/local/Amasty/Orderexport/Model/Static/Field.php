<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Static_Field extends Mage_Core_Model_Abstract
{
    const POSITION_BEGINNING = 1;
    const POSITION_END  = 2;
    
    protected function _construct()
    {
        $this->_init('amorderexport/static_field');
    }
    
    public function saveForProfile($profileId, $fields)
    {
        if (!$profileId || !is_array($fields))
        {
            return ;
        }
        $this->getResource()->clearFieldsForProfile($profileId);
        
        $this->setProfileId($profileId);
        foreach ($fields as $field)
        {
            if ($field['label'])
            {
                $this->setId(null);
                $this->addData($field);
                $this->save();
            }
        }
    }
}
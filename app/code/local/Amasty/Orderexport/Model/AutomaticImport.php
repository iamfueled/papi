<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_AutomaticImport
{
    public function runAutomaticImport($observer)
    {
       $collection = Mage::getModel('amorderexport/profile')->getCollection();
       $collection->addFieldToFilter('run_after_order_creation', 1);
       if ($collection->getSize() > 0)
       {
           foreach ($collection as $field)
           {
               $this->runProfile($field->getId());
           }
       }
    }
    
    public function runProfile($profileId)
    {        
        if ($profileId)
        {
            $model = Mage::getModel('amorderexport/profile');
            $model->load($profileId);
            
            if ($model->getId())
            {
                try
                {
                    $model->run();
                    return;
                } catch (Exception $e)
                {
                    $this->_getSession()->addException($e, Mage::helper('amorderexport')->__('An error occurred while running the export profile: ') . $e->getMessage());
                }
            }
        }
    }
}

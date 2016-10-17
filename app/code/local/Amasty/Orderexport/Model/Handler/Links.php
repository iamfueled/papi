<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Handler_Links extends Mage_Core_Model_Abstract
{
    public function handle($data)
    {
        $data  = unserialize($data);
        $value = '';
        if (isset($data['links']))
        {
            $collection = Mage::getModel('downloadable/link')->getCollection();
            
            if (false !== strpos($collection->getSelect()->__toString(), 'main_table'))
            {
                $alias = 'main_table';
            } else 
            {
                $alias = 'e';
            }
            
            $collection->addFieldToFilter($alias . '.link_id', array('in' => $data['links']));
            $collection->addTitleToResult(Mage::app()->getStore());
            if ($collection->getSize() > 0)
            {
                foreach ($collection as $link)
                {
                    $value .= $link->getTitle() . ', ';
                }
            }
        }
        if ($value)
        {
            $value = substr($value, 0, -2);
        }
        return $value;
    }
}
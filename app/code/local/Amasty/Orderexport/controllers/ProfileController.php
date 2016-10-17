<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_ProfileController extends Mage_Core_Controller_Front_Action
{
    public function runAction()
    {
        if (!Mage::getStoreConfig('amorderexport/run_by_url/enable'))
        {
            echo $this->__('Running by direct URL is disabled.');
            exit;
        }
        
        if ($id = $this->getRequest()->getParam('id'))
        {
            $code = $this->getRequest()->getParam('sec');
            if (!$code || $code != Mage::getStoreConfig('amorderexport/run_by_url/sec_code'))
            {
                echo $this->__('Incorrect security code.');
                exit;
            }
            
            $model = Mage::getModel('amorderexport/profile');
            $model->load($id);
            
            if ($model->getId())
            {
                try
                {
                    $model->run();
                    echo $this->__('Successfully complete.');
                    exit;
                } catch (Exception $e) 
                {
                    echo $e->getMessage();
                    exit;
                }
            } else 
            {
                echo $this->__('Incorrect profile ID.');
                exit;
            }
        } else
        {
            echo $this->__('Profile ID not specified.');
            exit;
        }
    }
}
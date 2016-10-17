<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Profile_History extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('amorderexport/profile_history');
    }
    
    public function download($compression = '')
    {
        $file = Mage::getBaseDir() . DS . $this->getFilePath();
        $applicationType = substr($file, strrpos($file, '.') + 1);
        switch ($compression)
        {
            case 'zip':
                $downloadFile = $file . '.zip';
                if (!file_exists($downloadFile) || !is_readable($downloadFile))
                {
                    if (!Mage::helper('amorderexport/compress')->zip($file, $downloadFile))
                    {
                        $downloadFile = $file;
                        continue;
                    }
                }
                $applicationType = 'zip';
            break;
            
            default:
                $downloadFile = $file;
            break;
        }

        if (!is_readable($downloadFile))
        {
            throw new Exception('File is not readable');
        }
        
        $fileName = substr($downloadFile, strrpos($downloadFile, '/') + 1);
        
        header('Content-type: application/' . $applicationType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        readfile($downloadFile);
    }
}

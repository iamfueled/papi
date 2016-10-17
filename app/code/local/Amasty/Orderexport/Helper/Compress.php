<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Helper_Compress extends Mage_Core_Helper_Abstract
{
    public function zip($file, $destination)
    {
        if (class_exists('ZipArchive'))
        {
            $zip = new ZipArchive;
            if (false === $zip->open($destination, ZIPARCHIVE::CREATE))
            {
                throw new Exception('Unable to create ZIP');
            }
            $fileName = substr($file, strrpos($file, '/') + 1);
            $zip->addFile($file, $fileName);
            $zip->close();
            return true;
        }
        
        return false;
    }
}
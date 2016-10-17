<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Renderer_Filesize extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $size = $row->getFileSize();
        $size /= (1024 * 1024); // in megabytes
        return sprintf('%.3f', $size) . ' ' . $this->__('Megabytes');
    }
}

<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Renderer_Runtime extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $time = $row->getRunTime();
        return sprintf('%.3f', $time) . ' ' . $this->__('seconds');
    }
}

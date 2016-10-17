<?php

class Infortis_UltraMegamenu_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getCfg($optionString)
    {
        return Mage::getStoreConfig('ultramegamenu/' . $optionString);
    }
	
	public function getIsOnHome()
	{
		$routeName = Mage::app()->getRequest()->getRouteName();
		$id = Mage::getSingleton('cms/page')->getIdentifier();
		
		if($routeName == 'cms' && $id == 'home')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function getHomeIconSuffix()
    {
		$theme = Mage::helper('ultimo');
		$outputSuffix = '';
		
		//Get config: w = white icon, b = black icon
		if ($this->getIsOnHome()) //If current page is homepage
		{
			$colorCurrent	= $theme->getCfgDesign('nav/mobile_opener_current_color');
			if		($colorCurrent == 'w') $outputSuffix = '-w';
			elseif	($colorCurrent == 'b') $outputSuffix = '';
		}
		else
		{
			$colorDefault	= $theme->getCfgDesign('nav/mobile_opener_color');
			$colorHover		= $theme->getCfgDesign('nav/mobile_opener_hover_color');
			$colors = $colorDefault . $colorHover;
			
			if		($colors == 'bb') $outputSuffix = '';
			elseif	($colors == 'bw') $outputSuffix = '-bw';
			elseif	($colors == 'wb') $outputSuffix = '-wb';
			elseif	($colors == 'ww') $outputSuffix = '-w';
		}
		
        return $outputSuffix;
    }
}

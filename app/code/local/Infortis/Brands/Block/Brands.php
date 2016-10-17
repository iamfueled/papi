<?php

/**
 * Product brands
 */
 
class Infortis_Brands_Block_Brands extends Mage_Core_Block_Template
{	
	/**
	 * Returns current product
	 *
	 * @return 
	 */
	public function getCurrentProductObject()
	{
		return Mage::registry('current_product');
	}
	
	/**
	 * Returns ID of the brand attribute
	 *
	 * @return string
	 */
	public function getBrandAttributeId()
	{
		return Mage::helper('brands')->getCfg('general/attr_id');
	}
	
	/**
	 * Returns name (title) of the brand attribute, set in the admin panel
	 *
	 * @return string
	 */
	public function getBrandAttributeTitle()
	{
		$attributeModel = Mage::getSingleton('eav/config')
			->getAttribute('catalog_product', $this->getBrandAttributeId());
		
		return $attributeModel->getStoreLabel();
	}
	
	/**
	 * Returns all existing brands
	 *
	 * @return array
	 */
	public function getAllBrands()
	{
		$attributeModel = Mage::getSingleton('eav/config')
			->getAttribute('catalog_product', $this->getBrandAttributeId());
			
		/*
		getAllOptions ([bool $withEmpty = true], [bool $defaultValues = false])
			- bool $withEmpty: Add empty option to array
			- bool $defaultValues: Return default values
		*/
		$options = array();
		foreach ($attributeModel->getSource()->getAllOptions(false, true) as $o)
		{
			$options[] = $o['label'];
		}
		
		return $options;
	}
	
	/**
	 * Returns only brands, which are currently assigned to products
	 *
	 * @return array
	 */
	public function getAllBrandsInUse()
	{
		$attributeCode = $this->getBrandAttributeId();
		$attributeModel = Mage::getSingleton('eav/config')
			->getAttribute('catalog_product', $attributeCode);
		
		//Get product collection
		$products = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect($attributeCode)
			->addAttributeToFilter($attributeCode, array('neq' => ''))
			->addAttributeToFilter($attributeCode, array('notnull' => true));
		//Get all (attribute's) values in use
		$attributeValuesInUse = array_unique($products->getColumnValues($attributeCode));

		//Get attribute options (text labels)
		return $attributeModel->getSource()->getOptionText(
			implode(',', $attributeValuesInUse)
			);
	}
	
	/**
	 * Returns brand of the product
	 *
	 * @param Product object
	 * @return string
	 */
	public function getBrand($product)
	{
		$attr = $product->getResource()->getAttribute($this->getBrandAttributeId()); //Attr. object
		return trim($attr->getFrontend()->getValue($product)); //Attr. value
	}
	
	/**
	 * Returns URL of the brand image directory
	 *
	 * @return string
	 */
	public function getBrandImageDir()
	{
		return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'wysiwyg/infortis/brands/';
	}
	
	/**
	 * Returns URL of the brand image
	 *
	 * @param string Brand name
	 * @return string
	 */
	public function getBrandImageUrl($brand)
	{
		$manufImageExt = trim(Mage::helper('brands')->getCfg('general/image_extension'));
		
		//Create image URL with simplified (replace spaces with "_") brand name 
		return $this->getBrandImageDir() . str_replace(" ", "_", strtolower($brand)) . '.' . $manufImageExt;
	}
	
	/**
	 * Returns URL of the brand page
	 *
	 * @param string Brand name
	 * @return string
	 */
	public function getBrandPageUrl($brand)
	{
		$manufPageUrl = '';
		$helper = Mage::helper('brands');
		
		//Check, if brand logo is a link to Magento's Quick Search results
		$manufLinkToSearch = $helper->getCfg('general/link_search_enabled');
		$manufPageBasePath = trim($helper->getCfg('general/page_base_path')); //Base path of each brand's page
		
		if ($manufLinkToSearch)
		{
			$manufPageUrl = Mage::getUrl() . 'catalogsearch/result/?q=' . str_replace(" ", "+", $brand);
		}
		elseif ($manufPageBasePath != '')
		{
			//If $manufPageBasePath is '/', then '/' has to be omitted
			//Change brand name to lowercase, and replace spaces with hyphens
			$basePath = ($manufPageBasePath == '/') ? '' : $manufPageBasePath . '/';
			$manufPageUrl = Mage::getUrl() . $basePath . str_replace(" ", "-", strtolower($brand));
			
			//Append category URL suffix if needed and if it exists
			if ($helper->getCfg('general/append_category_suffix'))
				$manufPageUrl .= Mage::getStoreConfig('catalog/seo/category_url_suffix');
		}
		else
		{
			$manufPageUrl = '';
		}
		
		return $manufPageUrl;
	}
}

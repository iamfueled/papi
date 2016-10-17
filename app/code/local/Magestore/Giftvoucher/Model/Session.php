<?php

class Magestore_Giftvoucher_Model_Session extends Mage_Core_Model_Session_Abstract
{
	public function __construct(){
		$namespace = 'giftvoucher';
		$this->init($namespace);
	}
}
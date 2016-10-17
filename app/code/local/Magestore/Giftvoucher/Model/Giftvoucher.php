<?php

class Magestore_Giftvoucher_Model_Giftvoucher extends Mage_Rule_Model_Rule
{
    public function _construct(){
        parent::_construct();
        $this->_init('giftvoucher/giftvoucher');
    }
    
    public function loadByCode($code){
    	return $this->load($code,'gift_code');
    }
    
    public function load($id, $field=null){
    	parent::load($id,$field);
    	if ($this->getIsDeleted()){
    		return Mage::getModel('giftvoucher/giftvoucher');
   		}
   		if ($this->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
   			&& $this->getExpiredAt()
			&& $this->getExpiredAt() < now())
			$this->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_EXPIRED);
    	return $this;
    }
    
    public function getIsDeleted(){
    	if (!$this->hasData('is_deleted')){
    		$this->setData('is_deleted',$this->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_DELETED);
    	}
    	return $this->getData('is_deleted');
    }
    
    public function getCollection(){
    	return parent::getCollection()->getAvailable();
    }
    
    public function getBaseBalance($storeId = null){
    	if (!$this->hasData('base_balance')){
    		$baseBalance = 0;
    		if ($rate = Mage::app()->getStore($storeId)->getBaseCurrency()->getRate($this->getData('currency')))
    			$baseBalance = $this->getBalance() / $rate;
    		$this->setData('base_balance',$baseBalance);
    	}
    	return $this->getData('base_balance');
    }
    
    protected function _beforeSave(){
    	if (!$this->getId()){
    		$this->setAction(Magestore_Giftvoucher_Model_Actions::ACTIONS_CREATE);
    	}
    	if ($this->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
			&& Mage::app()->getStore()->roundPrice($this->getBalance()) == 0)
			$this->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_USED);
		if (!$this->getGiftCode())
			$this->setGiftCode(Mage::helper('giftvoucher')->getGeneralConfig('pattern'));
		if ($this->_codeIsExpression())
			$this->setGiftCode($this->_getGiftCode());
    	return parent::_beforeSave();
    }
    
    protected function _codeIsExpression(){
    	return Mage::helper('giftvoucher')->isExpression($this->getGiftCode());
    }
    
    protected function _getGiftCode(){
    	$code = Mage::helper('giftvoucher')->calcCode($this->getGiftCode());
    	$times = 10;
    	while(Mage::getModel('giftvoucher/giftvoucher')->loadByCode($code)->getId() && $times){
    		$code = Mage::helper('giftvoucher')->calcCode($this->getGiftCode());
    		$times--;
    		if ($times == 0){
    			throw new Mage_Core_Exception('Exceeded maximum retries to find available random gift card code!');
    		}
    	}
    	return $code;
    }
    
    protected function _afterSave(){
    	if ($this->getIncludeHistory() && $this->getAction()){
    		$history = Mage::getModel('giftvoucher/history')
				->setData($this->getData())
				->setData('created_at',now());
            if ($this->getAction() == Magestore_Giftvoucher_Model_Actions::ACTIONS_UPDATE
                || $this->getAction() == Magestore_Giftvoucher_Model_Actions::ACTIONS_MASS_UPDATE
            ) {
                $history->setData('customer_id', null)
                    ->setData('customer_email', null);
            }
			try{
				$history->save();
			}catch(Exception $e){}
    	}
    	return parent::_afterSave();
    }
    
    public function delete(){
    	$this->setStatus(Magestore_Giftvoucher_Model_Status::STATUS_DELETED)
    		->save();
   		return $this;
    }
	
	public function getFormatedMessage()
	{
		return str_replace("\n","<br/>",$this->getMessage());
	}
    
    public function addToSession($session=null){
    	if (is_null($session)){
    		$session = Mage::getSingleton('checkout/session');
    	}
    	if ($codes = $session->getGiftCodes()){
    		$codesArray = explode(',',$codes);
    		$codesArray[] = $this->getGiftCode();
    		$codes = implode(',',array_unique($codesArray));
    	}else{
    		$codes = $this->getGiftCode();
   		}
    	$session->setGiftCodes($codes);
    	return $this;
    }
    
    public function sendEmail(){
    	$store = Mage::app()->getStore($this->getStoreId());
    	$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);
    	$mailSent = 0;
    	
    	if ($this->getCustomerEmail()){
	    	$mailTemplate = Mage::getModel('core/email_template')
				->setDesignConfig(array(
					'area'	=> 'frontend',
					'store'	=> $store->getStoreId()
				));
            if (Mage::helper('giftvoucher')->getEmailConfig('attachment',$store->getStoreId())) {
                $pdf = Mage::getModel('giftvoucher/pdf_giftcard')->getPdf(array($this->getId()));
                $mailTemplate->getMail()->createAttachment($pdf->render(),
                    'application/pdf',
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    'giftcard_' . $this->getId() . '.pdf'
                );
            }
            $mailTemplate->sendTransactional(
					Mage::helper('giftvoucher')->getEmailConfig('self',$store->getStoreId()),
					Mage::helper('giftvoucher')->getEmailConfig('sender',$store->getStoreId()),
					$this->getCustomerEmail(),
					$this->getCustomerName(),
					array(
						'store'		=> $store,
						'sendername'	=> $this->getCustomerName(),
						'name'		=> $this->getRecipientName(),
						'code'		=> $this->getGiftCode(),
						'balance'	=> $this->getBalanceFormated(),
						'status'	=> $this->getStatusLabel(),
                        'noactive'  => ($this->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE) ? 0 : 1,
						'expiredat'	=> $this->getExpiredAt() ? Mage::getModel('core/date')->date('M d, Y',$this->getExpiredAt()) : '',
						'message'	=> $this->getFormatedMessage(),
                        'note'      => $this->getEmailNotes(),
                        'description' => $this->getDescription(),
					)
				);
			$mailSent++;
		}
		
		if ($this->getRecipientEmail()){
			$mailSent += $this->sendEmailToRecipient();
    	}
    	
    	$this->setEmailSent($mailSent);
    	$translate->setTranslateInline(true);
    	return $this;
    }
    
    /**
     * send email to friend
     * 
     * @return Magestore_Giftvoucher_Model_Giftvoucher
     */
    public function sendEmailToFriend() {
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $this->sendEmailToRecipient();
        $translate->setTranslateInline(true);
        return $this;
    }
    
    /**
     * send email to Gift Voucher Receipient
     * 
     * @return int The number of email sent
     */
    public function sendEmailToRecipient() {
        $allowStatus = explode(',', Mage::helper('giftvoucher')->getEmailConfig('only_complete', $this->getStoreId()));
        if (!is_array($allowStatus)) $allowStatus = array();
        if ($this->getRecipientEmail()
            && !$this->getData('dont_send_email_to_recipient')
            && in_array($this->getStatus(), $allowStatus)
        ) {
            $store = Mage::app()->getStore($this->getStoreId());
            $mailTemplate = Mage::getModel('core/email_template')
                ->setDesignConfig(array(
                    'area'	=> 'frontend',
                    'store'	=> $store->getStoreId()
                ));
            if (Mage::helper('giftvoucher')->getEmailConfig('attachment',$store->getStoreId())) {
                $pdf = Mage::getModel('giftvoucher/pdf_giftcard')->getPdf(array($this->getId()));
                $mailTemplate->getMail()->createAttachment($pdf->render(),
                    'application/pdf',
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    'giftcard_' . $this->getId() . '.pdf'
                );
            }
            $mailTemplate->sendTransactional(
                    Mage::helper('giftvoucher')->getEmailConfig('template',$store->getStoreId()),
                    Mage::helper('giftvoucher')->getEmailConfig('sender',$store->getStoreId()),
                    $this->getRecipientEmail(),
                    $this->getRecipientName(),
                    array(
                        'store'		=> $store,
                        'sendername'	=> $this->getCustomerName(),
                        'name'		=> $this->getRecipientName(),
                        'code'		=> $this->getGiftCode(),
                        'balance'	=> $this->getBalanceFormated(),
                        'status'	=> $this->getStatusLabel(),
                        'noactive'  => ($this->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE) ? 0 : 1,
                        'expiredat'	=> $this->getExpiredAt() ? Mage::getModel('core/date')->date('M d, Y',$this->getExpiredAt()) : '' ,
                        'message'	=> $this->getFormatedMessage(),
                        'note'      => $this->getEmailNotes(),
                        'description' => $this->getDescription(),
                        'addurl'    => Mage::getUrl('giftvoucher/index/addlist', array('giftvouchercode' => $this->getGiftCode())),
                    )
                );
            return 1;
        }
        return 0;
    }
    
    public function getPrintNotes() {
        if (!$this->hasData('print_notes')) {
            $notes = Mage::getStoreConfig('giftvoucher/print_voucher/note', $this->getStoreId());
            $notes = str_replace(array(
                    '{store_url}',
                    '{store_name}',
                    '{store_address}'
                ),
                array(
                    Mage::app()->getStore($this->getStoreId())->getBaseUrl(),
                    Mage::app()->getStore($this->getStoreId())->getFrontendName(),
                    Mage::getStoreConfig('general/store_information/address', $this->getStoreId())
                ),
                $notes);
            $this->setData('print_notes', $notes);
        }
        return $this->getData('print_notes');
    }
    
    public function getEmailNotes() {
        if (!$this->hasData('email_notes')) {
            $notes = Mage::getStoreConfig('giftvoucher/email/note', $this->getStoreId());
            $notes = str_replace(array(
                    '{store_url}',
                    '{store_name}',
                    '{store_address}'
                ),
                array(
                    Mage::app()->getStore($this->getStoreId())->getBaseUrl(),
                    Mage::app()->getStore($this->getStoreId())->getFrontendName(),
                    Mage::getStoreConfig('general/store_information/address', $this->getStoreId())
                ),
                $notes);
            $this->setData('email_notes', $notes);
        }
        return $this->getData('email_notes');
    }
    
    public function getPrintLogo() {
        $image = Mage::getStoreConfig('giftvoucher/print_voucher/logo', $this->getStoreId());
        if ($image) {
            $image = Mage::app()->getStore($this->getStoreId())->getBaseUrl('media') . 'giftvoucher/pdf/logo/' . $image;
            return $image;
        }
        return false;
    }
    
    public function getBalanceFormated(){
    	$currency = Mage::getModel('directory/currency')->load($this->getCurrency());
    	return $currency->format($this->getBalance());
    }
    
    public function getStatusLabel(){
    	$statusArray = Mage::getSingleton('giftvoucher/status')->getOptionArray();
    	return $statusArray[$this->getStatus()];
    }
    
    /**
     * get list customer that used this code
     * 
     * @return array
     */
    public function getCustomerIdsUsed() {
        $collection = Mage::getResourceModel('giftvoucher/history_collection')
            ->addFieldToFilter('main_table.giftvoucher_id', $this->getId())
            ->addFieldToFilter('main_table.action', Magestore_Giftvoucher_Model_Actions::ACTIONS_SPEND_ORDER);
        $collection->getSelect()
            ->joinLeft(array('o' => $collection->getTable('sales/order')),
                'main_table.order_increment_id = o.increment_id',
                array('order_customer_id' => 'customer_id')
            )->group('o.customer_id');
        $customerIds = array();
        foreach ($collection as $item) {
            $customerIds[] = $item->getData('order_customer_id');
        }
        return $customerIds;
    }
    
    /* Add Magento Sales Rule for Gift Card Model */
    public function getConditionsInstance() {
        return Mage::getModel('salesrule/rule_condition_combine');
    }
    
    public function getActionsInstance() {
        return Mage::getModel('salesrule/rule_condition_product_combine');
    }
    
    public function loadPost(array $rule) {
        $arr = $this->_convertFlatToRecursive($rule);
        if (isset($arr['conditions'])) {
            $this->getConditions()->setConditions(array())->loadArray($arr['conditions'][1]);
        }
        if (isset($arr['actions'])) {
            $this->getActions()->setActions(array())->loadArray($arr['actions'][1], 'actions');
        }
        return $this;
    }
    
    /**
     * Fix error when load and save with multiple Gift Card for Core Magento
     * 
     * @return Magestore_Giftvoucher_Model_Giftvoucher
     */
    protected function _afterLoad() {
        $this->setConditions(null);
        $this->setActions(null);
        return parent::_afterLoad();
    }
}

<?php

define("SHIPPINGZMAGENTO_VERSION","3.0.0.62382");

# ################################################################################
# 	
#   (c) 2010-2014 Z-Firm LLC, ALL RIGHTS RESERVED.
#   Licensed to current Stamps.com customers. 
#
#   The terms of your Stamps.com license 
#   apply to the use of this file and the contents of the  
#   Stamps_ShoppingCart_Integration_Kit__See_README_file.zip   file.
#   
#   This file is protected by U.S. Copyright. Technologies and techniques herein are
#   the proprietary methods of Z-Firm LLC. 
#  
#   For use only by customers in good standing of Stamps.com
#
#
# 	IMPORTANT
# 	=========
# 	THIS FILE IS GOVERNED BY THE STAMPS.COM LICENSE AGREEMENT
#
# 	Using or reading this file indicates your acceptance of the Stamps.com License Agreement.
#
# 	If you do not agree with these terms, this file and related files must be deleted immediately.
#
# 	Thank you for using Stamps.com!
#
################################################################################



//Function for checking Include Files
function Check_Include_File($filename)
{
	if(file_exists($filename))
	{
		return true;
	}
	else
	{
		echo "\"$filename\" is not accessible.";
		exit;
	}

}
//Check for ShippingZ integration files
if(Check_Include_File("ShippingZSettings.php"))
include("ShippingZSettings.php");
if(Check_Include_File("ShippingZClasses.php"))
include("ShippingZClasses.php");
if(Check_Include_File("ShippingZMessages.php"))
include("ShippingZMessages.php");

// TEST all the files are all the same version
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZMAGENTO_VERSION && SHIPPINGZMAGENTO_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZMagento.php [".SHIPPINGZMAGENTO_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}

if(!defined("Magento_Store_Code_To_Service"))
define("Magento_Store_Code_To_Service","-ALL-");


//Include mage model for gift messages
if(Magento_RetrieveOrderGiftMessage==1 || Magento_RetrieveProductGiftMessage==1 || Magento_Store_Code_To_Service!='-ALL-')
{
	require_once 'app/Mage.php';
	$app = Mage::app();
	if(Magento_Store_Code_To_Service!='-ALL-')
	{
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $_eachStoreId => $val)
		{
			$_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
			$_storeName = Mage::app()->getStore($_eachStoreId)->getName();
			$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
			
			if($_storeCode==Magento_Store_Code_To_Service)
			$selected_store_id=$_storeId;
		}
	}
}
else
$selected_store_id="";
############################################### Check & adjust "default_socket_timeout"#######################################
$timeout_value="";
$timeout_value=@ini_get("default_socket_timeout");
if($timeout_value!="" && $timeout_value<120)
@ini_set("default_socket_timeout",120);
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
######################################### Find out the store URL #########################################

$url="http".(((empty($_SERVER['HTTPS'])&&$_SERVER['SERVER_PORT']!=443))?"" : "s")."://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$url= str_replace("ShippingZMagentoMAGE.php","",$url);
$url= str_replace("ShippingZMagento.php","",$url);
define("WebsiteUrl",$url);

$magentoVersion = Mage::getVersion();
############################################## Class ShippingZMagento ##########################################
class ShippingZMagento extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	######################################## Function EXECUTE_CURL ######################################
	function EXECUTE_CURL($url)
	{
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		//Additional curl options Following ZF Case 24497
		//To make sure curl works for SSL Server too
		//We don't have access to other servers.Hence using following two curl options is safe for our use.
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		 
		$fp = curl_exec($ch); 
		if($fp === false)
		{
			$this->CheckAndOverrideErrorMessage('Curl error: ' . curl_error($ch));
			
		}
		
		$http_code =curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $http_code."~=~".$fp;
	
	}
	############################################## Function Check_DB_Access #################################
	//Check Database access(for magento everything will be done using API so, we don't need database access.But need to check if API credentials are set properly)
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
		global $proxy,$sessionId;
			
		if(WebsiteUrl!="" && Magento_Username!="" && Magento_Password!="")
		{
	
			$url = WebsiteUrl.'api/soap/?wsdl';
			$extra_path="";
			
			//check if the url is proper & we can access wsdl
			$curl_result=$this->EXECUTE_CURL($url);
			$curl_result_temp=explode("~=~",$curl_result);
			$fp = $curl_result_temp[1]; 
			$http_code =$curl_result_temp[0];
			
			
			$this->CheckAndOverrideErrorMessage($fp);//check for custom error 
			
			if($http_code!=200)
            {
			   if(substr(WebsiteUrl,strlen(WebsiteUrl)-1)!="/")
			   $extra_path="/index.php/";
			   else
			   $extra_path.="index.php/";
				
			   $url = WebsiteUrl.$extra_path.'api/soap/?wsdl'; 
			   //check if the url is proper & we can access wsdl
			   $curl_result=$this->EXECUTE_CURL($url);
			   $curl_result_temp=explode("~=~",$curl_result);
			   $fp = $curl_result_temp[1]; 
			   $http_code =$curl_result_temp[0];
			}
			
			if($http_code==200)
			{
				
							
				try
				{
					$proxy = new SoapClient(WebsiteUrl.$extra_path.'api/soap/?wsdl',array('exceptions' => 0,'trace' => 1,"connection_timeout" => 120));//path to magento wsdl
				}
				catch(Exception $e)
				{
				
					$this->display_msg=MAGENTO_TEMPORARY_ERROR_MSG;
					$this->SetXmlError(1,$this->display_msg, $fp . $url);
					exit;
				}
										
				try //See if API credentials are proper
				{
					$sessionId = $proxy->login(Magento_Username, Magento_Password);//create session id
		
					$this->display_msg=DB_SUCCESS_MSG;
				}
				catch(Exception $e)
				{
					//Wrong API credentials
					$this->display_msg=MAGENTO_WRONG_API_DETAILS_ERROR_MSG;
					$this->SetXmlError(1,$this->display_msg, $fp . $url);
					exit;
				
				}
			}
			else
			{
				//Wrong store url
				$this->display_msg=MAGENTO_WRONG_STORE_URL_ERROR_MSG;
				$this->SetXmlError(1,$this->display_msg, $fp . $url);
				exit;
			
			}
		}
		else
		{
			//Store URL or API credentials not set
			$this->display_msg=MAGENTO_API_NOT_SET_ERROR_MSG;
			$this->SetXmlError(1,$this->display_msg);
			exit;
		}	
				
	}
	
	############################################## Function UpdateDatefrom  #################################
	//if Day(DateFrom) = Day(DateTo) then set DateFrom to previous day
	#######################################################################################################
	function UpdateDatefrom($datefrom,$dateto)
	{
		
		$day_datefrom=substr($datefrom,0,10);
		$day_dateto=substr($dateto,0,10);
		
		$time_str_datefrom=substr($datefrom,10);
		
		if($day_datefrom==$day_dateto)
		{
			$updated_date_from=date("Y-m-d",strtotime("-1 day", strtotime($day_datefrom)));
			$updated_date_from=$updated_date_from.$time_str_datefrom;
			return $updated_date_from;
		}
		else
		{
			return $datefrom;
		}
		
	}
	############################################## Function SafeUnserialize  #################################
	//This will return false in case the passed string is not unserializeable
	#######################################################################################################
	function SafeUnserialize($serialized_string) 
	{
   		if (strpos($serialized_string, "\0") === false &&  is_string($serialized_string) ) {
			if (strpos($serialized_string, 'O:') === false) {
			  
				return @unserialize($serialized_string);
			} else if (!preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized_string)) {
			   
				return @unserialize($serialized_string);
			}
		}
		return false;
	}
	############################################## Function GetProductOptions  #################################
	//Used to get product attributes and sku for product variations
	#######################################################################################################
	function GetProductOptions($option_arr,$code='')
	{
			
			if($code=="")
			{
				//get attribute details
				$formatted_option_variation_details="";
							
				if(isset($option_arr['attributes_info']))
				{
					
					foreach($option_arr['attributes_info'] as $key=>$val)
					{
						
						if(is_array($val))
						{
							foreach($val as $key2=>$value2)
							{
								
								if($key2=="label")
								{
									$curr_label=$value2;
								}
								else if($key2=="value")
								{
									if($formatted_option_variation_details!="")
										$formatted_option_variation_details.=", ".$value2;
									else
										$formatted_option_variation_details=$value2;
								}
								
							}
						}
						
					}
					
					
					if($formatted_option_variation_details!="")
					{
						return " (".$formatted_option_variation_details.")";	
					}
					else
					{
					
						return '';
					}
					
				}
			}
			else
			{
					//get simple sku
					if(isset($option_arr[$code]))
						return "-".$option_arr[$code];
					else
						return '';
								
			}
		
	}
	############################################## Function GetProductOptionValuebyLabel  #################################
	//Used to get product attributes by label
	#######################################################################################################
	function GetProductOptionValuebyLabel($option_string,$label='')
	{
		
			$option_arr=$this->SafeUnserialize($option_string);
			
			//get attribute details
			$formatted_option_variation_details="";
						
			if(isset($option_arr['attributes_info']))
			{
				foreach($option_arr['attributes_info'] as $key=>$val)
				{
					$curr_label=0;
					
					foreach($val as $key2=>$value2)
					{
						if($key2=="label")
						{
							if($value2==$label)
							{
								$curr_label=1;
							}
						}
						else if($key2=="value" && $curr_label==1)
						{
							return $value2;
						}
						
					}
					
				}
			}
			
			
		
	}
	############################################## Function DebugApiError  #################################
	//Track Api Error
	#######################################################################################################
	function DebugApiError($Result,$Method,$line,$params)
	{
		
		global $proxy,$sessionId;
		
		if(is_soap_fault($Result)) 
		{
    		
			if($this->GetValues('show_api_error')==1)
			{
			 
				print "<br><b>SHIPPINGZCLASSES Version:</b>".SHIPPINGZCLASSES_VERSION;
				print "<br><b>SHIPPINGZSETTINGS Version:</b>".SHIPPINGZSETTINGS_VERSION;
				print "<br><b>SHIPPINGZMAGENTO Version:</b>".SHIPPINGZMAGENTO_VERSION;
				print "<br><b>SHIPPINGZMESSAGES Version:</b>".SHIPPINGZMESSAGES_VERSION;
				print("<br><b>Magento API Func Called:</b> $Method , SOAP Fault: (faultcode: {$Result->faultcode}, faultstring: {$Result->faultstring}), <b>Called at Line:</b> ".$line);
				echo "<br><b>Parameters:</b> <br>";
				print_r($params)."<br>";
				echo "<br><b>Response:</b><br> ";
				print_r($Result)."<br>";
				exit;
			}
			else
			{
				$this->CheckAndOverrideErrorMessage($Result->faultstring); //check for custom errors
				
				$this->SetXmlError("{$Result->faultcode}","Unable to communicate with the Magento API. Please review the Magento setup documentation. Check that the Magento API User Name and password are set correctly. Check that the API account is Active.","Magento API Func Called-$Method , SOAP Fault-({$Result->faultstring}),Called at Line-".$line);
				
			
			}
		}
		
	}
	############################################## Function ShowRawData  #################################
	//To check raw data returned by api
	#######################################################################################################
	function ShowRawData($Param,$Result,$is_exit=0)
	{
		
		global $proxy,$sessionId;
		
		if($this->GetValues($Param)==1)
		{
			if(count($Result)>0)
			{
				print_r($Result);
				echo "<br>=====================<br>";
			}
			if($is_exit)
			{
				exit;
			}
		}
		
	}
	############################################## Function Check_Field #################################
	//Check & Return field value if available
	#######################################################################################################
	function Check_Field($obj,$field,$arg="")
	{
		if(is_object($obj))
		{
			if($arg!="")
			{
				if($arg!=0)
				{
					if(null !==($obj->{$field}($arg)))
					{
						
						return $obj->{$field}($arg);
					}
					else
					{
						return "";
					}
				}
				else
				{
					if(null !==($obj->{$field}()))
					{
						
						return $obj->{$field}();
					}
					else
					{
						return "";
					}
				}
			}
			else
			{
				
				if($obj->{$field}!="")
				{
					
					return $obj->{$field};
				}
				else
				{
					return "";
				}
			}
			
		}
		else
		{
			return "";
		}
		
	}
	############################################## Function GetOrderCountByDate #################################
	//Get order count
	#######################################################################################################
	function GetOrderCountByDate($datefrom,$dateto)
	{
		global $proxy,$sessionId,$selected_store_id,$magentoVersion;
		
		$order_status_filter=$this->PrepareMagentoOrderStatusFilter();
		
		if(!StandardPerformanceTest)
		$datefrom=$this->UpdateDatefrom($datefrom,$dateto);
		
				
		if(Magento_Store_Code_To_Service!="-ALL-" && is_numeric($selected_store_id))
		{
				//count orders from specific store
				$orders_data = Mage::getModel('sales/order')->getCollection();
				
				if($magentoVersion>1.3)
				{
					$orders_data->addAttributeToSelect("increment_id")->getSelect()->where("(".$order_status_filter." updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."' AND store_id = $selected_store_id)");
				}
				else
				{
					$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("increment_id")->getSelect()->where("( updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."' AND store_id = $selected_store_id)");
				}
				$total_count = $orders_data->count();
		}
		else
		{
				//count orders from all stores
				$orders_data = Mage::getModel('sales/order')->getCollection();
				if($magentoVersion>1.3)
				{
					$orders_data->addAttributeToSelect("increment_id")->getSelect()->where("(".$order_status_filter." updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."' )");
				}
				else
				{
					$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("increment_id")->getSelect()->where("( updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."' )");
				}
				$total_count = $orders_data->count();
		
		
		}
				
		return $total_count;
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		
		global $proxy,$sessionId;
		
		if($ShipDate!="")
			$shipped_on=$ShipDate;
		else
			$shipped_on=date("m/d/Y");
		
		if($Carrier!="")
		{
			$SelectedCarrier=$Carrier;
			$Carrier=" via ".$Carrier;
						
		}
		else
		{
			$SelectedCarrier="ups";
		}
			
		if($Service!="")
			$ServiceString=" [".$Service."]";
		else
			$ServiceString="";
		
		if(Magento_SendsShippingEmail==1)
			$send_email_flag=true;
		else
			$send_email_flag=false;
			
			
		if(Magento_SendsShippingEmail_AddComments==1)
			$send_email_include_comments=true;
		else
			$send_email_include_comments=false;
			
			
		if(Magento_SendsBuyerEmail==1)
			$send_buyer_email_flag=true;
		else
			$send_buyer_email_flag=false;
	
		
		//prepare $comments 
		$TrackingString="";
		if($TrackingNumber!="")
		$TrackingString=", Tracking number $TrackingNumber";
		
		$comments="Shipped on $shipped_on".$Carrier.$ServiceString.$TrackingString;
		
		$magento_orders_temp=$proxy->call($sessionId, 'sales_order.info', $OrderNumber);
		$this->DebugApiError($magento_orders_temp,"sales_order.info",__LINE__ ,$OrderNumber);
		$current_order_status=$magento_orders_temp['status'];
		$related_store_id=$magento_orders_temp['store_id'];
		
		$this->ShowRawData('show_current_order_status',$current_order_status."-".$comments,1);
		
		if(MAGENTO_SHIPPED_STATUS_COMPLETE_ALL_SHIPPED_ORDERS==1)
		{
			$change_order_status="complete";
		}
		else
		{   
		    
			if(strtolower($current_order_status)=="pending")
				$change_order_status="processing";
			else if(strtolower($current_order_status)=="processing")
				$change_order_status="complete";
			else
			$change_order_status=$current_order_status;
		}
		
		if(Magento_StoreShippingInComments==1)
		{
			try
			{
				// add comment using sales_order.addComment method
				$result=$proxy->call($sessionId, 'sales_order.addComment', array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->DebugApiError($result,"sales_order.addComment",__LINE__, array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
			}
			catch( Exception $e )
			{
				//display error message
				$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
				$this->SetXmlError(1,$this->display_msg);
			}
	   }
	   else
	   {
	   		try
			{
				
				
				//get order details by id
				$magento_orders_temp=$proxy->call($sessionId, 'sales_order.info', $OrderNumber);
				$this->DebugApiError($magento_orders_temp,"sales_order.info",__LINE__,$OrderNumber);
				
		
				$exists = $proxy->call($sessionId, 'sales_order_shipment.list',array(array('order_increment_id'=>array('='=>$OrderNumber ),'store_id'=>array('='=>$related_store_id))));
				
			  
			  if(is_soap_fault($exists) )
			  {
				//call api again for magento version 1.4.1.1 
				$related_order_id=$magento_orders_temp['order_id'];
				
				$exists = $proxy->call($sessionId, 'sales_order_shipment.list', array(array('order_id'=>array('='=>$related_order_id),'store_id'=>array('='=>$related_store_id))));	
				$this->DebugApiError($exists,"sales_order_shipment.list",__LINE__,array(array('order_id'=>array('='=>$related_order_id),'store_id'=>array('='=>$related_store_id))));	
				
			  }
			
				if(isset($exists[0]['increment_id']))
				{
					$newShipmentId=$exists[0]['increment_id'];
					
				}
				else
				{
					//create new shipment
					$newShipmentId = $proxy->call($sessionId, 'sales_order_shipment.create',array($OrderNumber,array() ,$comments, $send_email_flag,$send_email_include_comments) );
					$this->DebugApiError($newShipmentId,"sales_order_shipment.create",__LINE__,array($OrderNumber,array() ,$comments, $send_email_flag,$send_email_include_comments));
				}
				
				#add tracking number
				if($Service=="")
				$Service="Shipping Tracking";
				
				
				$newTrackId = $proxy->call($sessionId, 'sales_order_shipment.addTrack', array($newShipmentId, strtolower($SelectedCarrier), $Service, $TrackingNumber));
				$this->DebugApiError($newTrackId,"sales_order_shipment.addTrack",__LINE__,array($newShipmentId, strtolower($SelectedCarrier), $Service, $TrackingNumber));
				
			
				#force status change
				$result=$proxy->call($sessionId, 'sales_order.addComment', array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->DebugApiError($result,"sales_order.addComment",__LINE__, array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success")); 

			}
			catch( Exception $e )
			{
				
				//display error message
				$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
				$this->SetXmlError(1,$e->getMessage());
			}
	   
	   }
		
	}
	############################################## Function Fetch_DB_Orders #################################
	//Fetch orders based on date range using sales_order.list method
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		global $proxy,$sessionId,$selected_store_id,$magentoVersion;
		
		$order_status_filter=$this->PrepareMagentoOrderStatusFilter();
		$pageCount=1000;
		
		if(!StandardPerformanceTest)
		$datefrom=$this->UpdateDatefrom($datefrom,$dateto);
		
		if(Magento_Store_Code_To_Service!="-ALL-" && is_numeric($selected_store_id))
		{	
			//fetch orders from specific store
			$orders_data = Mage::getModel('sales/order')->getCollection();
			
			if($magentoVersion>1.3)
			{
				$orders_data->addAttributeToSelect("*")
            ->getSelect()
            ->where("(".$order_status_filter." updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."' AND store_id = $selected_store_id)");
			}
			else
			{
			
       		$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("*")
            ->getSelect()
            ->where("( updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."' AND store_id = $selected_store_id)");
            }
              $orders_data->setPageSize($pageCount)
			   ->setCurPagE(1)
			   ->loadData();
		}
		else
		{
			//fetch all orders irrespective of store
			$orders_data = Mage::getModel('sales/order')->getCollection();
			
			if($magentoVersion>1.3)
			{
				$orders_data->addAttributeToSelect("*")
            ->getSelect()
            ->where("(".$order_status_filter." updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."')");
			
			}
			else
			{
        	$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("*")
            ->getSelect()
            ->where("( updated_at > '".$this->GetServerTimeLocalMagento($datefrom)."' AND updated_at <= '".$this->GetServerTimeLocalMagento($dateto)."')");
            }
               $orders_data->setPageSize($pageCount)
			   ->setCurPagE(1)
			   ->loadData();
		
		
		
		}
		
		
		$this->magento_orders=array();
		$counter=0;
		foreach ($orders_data as $order) 
		{
			
			$order_id=$order->getIncrementId();
			
				
			//prepare order array
			$this->magento_orders[$counter]=new stdClass();
			$this->magento_orders[$counter]->orderid=$order_id;
			
			
			if(MAGENTO_READ_INVOICES)
			{
			
					//Retrieve invoice numbers
					$invoice_str="";
					
					$invoices = $order->getInvoiceCollection();
					if(isset($invoices))
					{
														
						foreach($invoices as $invoice)
						{
							if($invoice_str!="")
							$invoice_str.=" ";
							
							$invoice_str.=$invoice->getIncrementId();
							
						}
						
						$invoice_str=substr($invoice_str,0,50); //consider upto 50 chars
						$invoice_str=trim($invoice_str);				
					}
					
			}
			
			//shipping details
			$ShippingAddress=$order->getShippingAddress();
				
			$this->magento_orders[$counter]->order_shipping["FirstName"]=$this->Check_Field($ShippingAddress,'firstname');
			$this->magento_orders[$counter]->order_shipping["LastName"]=$this->Check_Field($ShippingAddress,'lastname');
			$this->magento_orders[$counter]->order_shipping["Company"]=$this->Check_Field($ShippingAddress,'getCompany()','0');
			$this->magento_orders[$counter]->order_shipping["Address1"]=$this->Check_Field($ShippingAddress,'getStreet','1');
			$this->magento_orders[$counter]->order_shipping["Address2"]=$this->Check_Field($ShippingAddress,'getStreet','2');
			$this->magento_orders[$counter]->order_shipping["City"]=$this->Check_Field($ShippingAddress,'getCity','0');
			$this->magento_orders[$counter]->order_shipping["State"]=$this->Check_Field($ShippingAddress,'getRegionCode','0');
			$this->magento_orders[$counter]->order_shipping["PostalCode"]=$this->Check_Field($ShippingAddress,'getPostcode','0');
			$this->magento_orders[$counter]->order_shipping["Country"]=$this->Check_Field($ShippingAddress,'getCountryId','0');
			$this->magento_orders[$counter]->order_shipping["Phone"]=$this->Check_Field($ShippingAddress,'getTelephone','0');
			
			$this->magento_orders[$counter]->order_shipping["EMail"]=$this->Check_Field($order,'getCustomerEmail','0');
			
			//billing details
			$BillingAddress = $order->getBillingAddress();
			
			$this->magento_orders[$counter]->order_billing["FirstName"]=$this->Check_Field($BillingAddress,'firstname');
			$this->magento_orders[$counter]->order_billing["LastName"]=$this->Check_Field($BillingAddress,'lastname');
			$this->magento_orders[$counter]->order_billing["Company"]=$this->Check_Field($BillingAddress,'getCompany','0');
			$this->magento_orders[$counter]->order_billing["Address1"]=$this->Check_Field($BillingAddress,'getStreet','1');
			$this->magento_orders[$counter]->order_billing["Address2"]=$this->Check_Field($BillingAddress,'getStreet)','2');
			$this->magento_orders[$counter]->order_billing["City"]=$this->Check_Field($BillingAddress,'getCity','0');
			$this->magento_orders[$counter]->order_billing["State"]=$this->Check_Field($BillingAddress,'getRegionCode','0');
			$this->magento_orders[$counter]->order_billing["PostalCode"]=$this->Check_Field($BillingAddress,'getPostcode','0');
			$this->magento_orders[$counter]->order_billing["Country"]=$this->Check_Field($BillingAddress,'getCountryId','0');
			$this->magento_orders[$counter]->order_billing["Phone"]=$this->Check_Field($BillingAddress,'getTelephone','0');
			
			//order info
			$this->magento_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTCMagento($order->getCreatedAt());
			
			if(MAGENTO_READ_INVOICES)
			$this->magento_orders[$counter]->order_info["ExternalID"]=$invoice_str;
			
			$this->magento_orders[$counter]->order_info["ItemsTotal"]=number_format($order->getSubtotal(),2,'.','');
			$this->magento_orders[$counter]->order_info["Total"]=number_format($order->getGrandTotal(),2,'.','');
			if($order->getTaxAmount()!="")
			{
				$this->magento_orders[$counter]->order_info["ItemsTax"]=number_format($order->getTaxAmount(),2,'.','');
			}
			else
			{
				$this->magento_orders[$counter]->order_info["ItemsTax"]=0.00;
			}
			$this->magento_orders[$counter]->order_info["OrderNumber"]=$order_id;
			
			//Get Payment details
			$payment=$order->getPayment();
			$this->magento_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType(Mage::helper('payment')->getMethodInstance($payment->getMethod())->getTitle());
			$this->magento_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($order->getShippingAmount(),2,'.','');
			$this->magento_orders[$counter]->order_info["ShipMethod"]=$order->getShippingDescription();
			$this->magento_orders[$counter]->order_info["Comments"]="";			

			if($order->getStatus()!="pending")
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status
			if($order->getStatus()=="complete")
				$this->magento_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->magento_orders[$counter]->order_info["IsShipped"]=0;
				
			//show if cancelled
			if($order->getStatus()=="canceled")
				$this->magento_orders[$counter]->order_info["IsCancelled"]=1;
			else
				$this->magento_orders[$counter]->order_info["IsCancelled"]=0;
				
				
			 //handle closed order
			if($order->getStatus()=="closed")
			{
				$this->magento_orders[$counter]->order_info["IsCancelled"]=1;
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=0;
				$this->magento_orders[$counter]->order_info["IsShipped"]=0;
			}
			
			//Order Level Gift Message
			if(Magento_RetrieveOrderGiftMessage==1)
			{
				$message = Mage::getModel('giftmessage/message');
				$gift_message_id = $order->getGiftMessageId();
				
				if(!is_null($gift_message_id)) 
				{
						$message->load((int)$gift_message_id);
						$this->magento_orders[$counter]->order_info["Comments"]=$this->GetGiftMessageText($message);
				}
			}
			
			
			
			//Get order products
			$actual_number_of_products=0;
			
			
			$order_items=$order->getAllItems();
			
			
			foreach ($order_items as $item)
			{
				if($item->getParentItemId()=="")
				{
									
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$item->getName().$this->GetProductOptions($item->getProductOptions());
							
				
				 if (version_compare(Mage::getVersion(), '1.3.0', '>='))
				{
					$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getPrice();
				}
				else
				{
					if ($item->hasOriginalPrice())
					{
						$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getOriginalPrice();
					}
					elseif ($item->hasCustomPrice())
					{
						$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getCustomPrice();
					}
					
				}
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["ExternalID"]=$item->getSku().$item->getProductOptionByCode('simple_sku');
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]=number_format($item->getQtyOrdered(),2,'.','');;
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Total"]=number_format(($this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]*$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]),2,'.','');
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Total_Product_Weight"]=number_format(($item->getWeight()*$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]),2,'.','');
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]="";
				
				//Product Level Gift Message
				if(Magento_RetrieveProductGiftMessage==1)
				{
					$gift_message_id = $item->getGiftMessageId();
					
					if(!is_null($gift_message_id)) 
					{
							$message->load((int)$gift_message_id);
							$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]=$message->getData('message');
					}
				
				}
				
				$actual_number_of_products++;
				
				}
			}
			
			$this->magento_orders[$counter]->num_of_products=$actual_number_of_products;
			
			
			
			$counter++;
		}	
	
		
		
	}

	function GetGiftMessageText($message)
	{
           $result = "";
           if ($message->getData('sender')) $result = $result."From: ".$message->getData('sender')."\r\n";
           if ($message->getData('recipient')) $result = $result."To: ".$message->getData('recipient')."\r\n\r\n";
           $result = $result.$message->getData('message');
           return $result;
        }

	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			

			if (isset($this->magento_orders))
				return $this->magento_orders;
			else
               return array();  

			
	}
	  
	  #################################### Convert UTC time to Magento Format ################################################
	  /* Magento stores all times in UTC but not in ISO 8601 format.Hence, change "YYYY-MM-DDThh:mm:ssZ" to "YYYY-MM-DD hh:mm:ss"*/
	  #########################################################################################################################
	  function GetServerTimeLocalMagento($server_date_iso) 
	  {
			
			if(strpos($server_date_iso,"Z"))
			{
				$utc_fotmat_temp=str_replace("Z","",$server_date_iso);
				$server_date_utc=str_replace("T"," ",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
				
			}
			return $server_date_utc;
	  }	
	   #################################### Convert Magento Format to UTC################################################
	  /* Magento stores all times in UTC but not in ISO 8601 format.Hence, format date to ISO 8601 i.e "YYYY-MM-DDThh:mm:ssZ" */
	  #########################################################################################################################
	  function ConvertServerTimeToUTCMagento($server_date_utc) 
	  {
		$utc_fotmat_temp=$server_date_utc."Z";
		$server_date_iso=str_replace(" ","T",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
		return $server_date_iso;
	  }	

	#######################################################################################################
	############################################### Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareMagentoOrderStatusFilter()
	{
			global $magentoVersion;
			
			
			if($magentoVersion>1.3)
			{
					$order_status_filter="";
					
					if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
					{
						$order_status_filter=" status='pending' ";
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='processing'  ";
						}
						else
						{
							$order_status_filter.=" OR status='processing'  ";
						}
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='complete'  ";
						}
						else
						{
							$order_status_filter.=" OR status='complete'  ";
						}
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='closed'  ";
						}
						else
						{
							$order_status_filter.=" OR status='closed' ";
						}
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='canceled'  ";
						}
						else
						{
							$order_status_filter.=" OR status='canceled'  ";
						}
					
					}
					
					if($order_status_filter!="")
					$order_status_filter="( ".$order_status_filter." ) and";
					
			}
			else
			{
					$order_status_filter=array();
			
					if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
					{
						$filter_pending=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_PROCESSING);
						array_push($order_status_filter,$filter_pending);	
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
					{
						$filter_processing=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_PROCESSING);
						array_push($order_status_filter,$filter_processing);	
								
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
					{
						$filter_complete=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_COMPLETE);
						array_push($order_status_filter,$filter_complete);	
								
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
					{
						$filter_closed=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_CLOSED);
						array_push($order_status_filter,$filter_closed);	
									
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
					{
						$filter_cancelled=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_CANCELED);
						array_push($order_status_filter,$filter_cancelled);	
									
					}
			
			}
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZMagento ###################################################
	
	//create object & perform tasks based on command
	$obj_shipping_magento=new ShippingZMagento;
	$obj_shipping_magento->ExecuteCommand();	

?>
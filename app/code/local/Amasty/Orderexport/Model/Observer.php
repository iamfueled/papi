<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Observer
{
    public function modifyOrderGridAfterBlockGenerate($observer){    
        
        $permissibleActions = array('index', 'grid', 'exportCsv', 'exportExcel');
        
        
        if ( Mage::app()->getRequest()->getControllerName() != 'sales_order' || 
             !in_array(Mage::app()->getRequest()->getActionName(), $permissibleActions) ){
             
            return;
        }
        
        $block = $observer->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid){
            $profileCollection = Mage::getModel("amorderexport/profile")->getCollection();
            
            foreach($profileCollection as $profile) {
                
                $block->addExportType("amorderexport/adminhtml_profile/runGrid/id/".$profile->getId(), "Order Export Profile: " . $profile->getName());
            }
        } 
    }
    
    public function addNewActions($observer) 
    {
        if (!$this->_isSalesGrid($observer->getPage())){
            return $this;
        }        
        
        $block = $observer->getBlock();
//        //$types = array('', 'ship', 'invoice', 'capture', 'delete');
//        $types = Mage::getStoreConfig('amoaction/general/commands');
//        if (!$types)
//            return $this;
//            
//        $types = explode(',', $types); 
//        foreach ($types as $i => $type){
//            if ($type){
//                $command = Amasty_Oaction_Model_Command_Abstract::factory($type);
//                $command->addAction($block);
//            }
//            else { // separator
//                $block->addItem('amoaction_separator' . $i, array(
//                    'label'=> '---------------------',
//                    'url'  => '' 
//                ));                
//            }
//        }
//
//        if ($this->isExtensionActive('SLandsbek_SimpleOrderExport')){ 
//            $block->addItem('amoaction_separator' . $i, array(
//                'label'=> '---------------------',
//                'url'  => '' 
//            ));                
//            $block->addItem('simpleorderexport', array(
//                'label' => 'Export to .csv file',
//                'url' => Mage::app()->getStore()->getUrl('simpleorderexport/export_order/csvexport'),
//            ));
//        } 
//      
        $block->addItem('amorderexport_separator', array(
            'label'=> '---------------------',
            'url'  => '' 
        ));  
        
        
        $profileCollection = Mage::getModel("amorderexport/profile")->getCollection();
        foreach($profileCollection as $profile) {
            
            $block->addItem('amorderexport_run_'.$profile->getId(), array(
                'label' => 'Run ' . $profile->getName(),
                'url' => Mage::helper("adminhtml")->getUrl("adminhtml_orderexport/profile/runGrid", array(
                    "id" => $profile->getId()
                )),
            ));
        }

        return $this;
    }
    
    public function modifyJs($observer) 
    {
//        $page = $observer->getResult()->getPage();
//        if (!$this->_isSalesGrid($page)){
//            return $this;
//        }
//        
//        $js = $observer->getResult()->getJs();
//        $js = str_replace('varienGridMassaction', 'amoaction', $js); 
//        $observer->getResult()->setJs($js);
        
        return $this;
    }  
    
    protected function _isSalesGrid($page)
    {
	   return in_array($page, array('adminhtml_sales_order', 'sales_order', 'orderspro_order'));
    } 
}

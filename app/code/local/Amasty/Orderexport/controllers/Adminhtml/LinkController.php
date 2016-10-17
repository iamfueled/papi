<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Adminhtml_LinkController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/convert/orderexport')
            ->_addBreadcrumb(Mage::helper('amorderexport')->__('Import/Export'), Mage::helper('amorderexport')->__('Import/Export'))
            ->_addBreadcrumb(Mage::helper('amorderexport')->__('Orders Export'), Mage::helper('amorderexport')->__('Orders Export'))
        ;
        return $this;
    }
    
    public function indexAction()
    {
        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('amorderexport/adminhtml_link'))
             ->renderLayout();
    }
    
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }
    
    public function editAction()
    {
        $this->_title(Mage::helper('amorderexport')->__('Orders Export'))->_title(Mage::helper('amorderexport')->__('Edit Link'));
        
        $id    = $this->getRequest()->getParam('link_id');
        $model = Mage::getModel('amorderexport/link');
        if ($id) 
        {
            $model->load($id);
            if (!$model->getId()) 
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amorderexport')->__('This link no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        
        // Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (! empty($data)) 
        {
            $model->setData($data);
        }
        
        Mage::register('amorderexport_link', $model);
             
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('amorderexport/adminhtml_link_edit'))
            ->_addLeft($this->getLayout()->createBlock('amorderexport/adminhtml_link_edit_tabs'))
            ->renderLayout();
    }
    
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('link_id')) 
        {
            try 
            {
                $model = Mage::getModel('amorderexport/link');
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('amorderexport')->__('The link has been deleted.'));
                $this->_redirect('*/*/');
                return;
                
            } catch (Exception $e) 
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('link_id' => $id));
                return;
            }
        }
    }
    
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost())
        {
            $id = $this->getRequest()->getParam('link_id');
            
            $model = Mage::getModel('amorderexport/link');
            if ($id)
            {
                $model->load($id);
            }
            
            try
            {
                $model->setData($data);
                $model->save();
                
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('amorderexport')->__('The export link has been saved.'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                
                $this->_redirect('*/*/');
                return;
                
            } catch (Exception $e) 
            {
                $this->_getSession()->addException($e, Mage::helper('amorderexport')->__('An error occurred while saving the export link: ') . $e->getMessage());
            }
            
            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', array('link_id' => $flagId));
            return;
        }
        $this->_redirect('*/*/');
    }
}
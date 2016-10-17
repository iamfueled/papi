<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Adminhtml_ProfileController extends Mage_Adminhtml_Controller_Action
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
             ->_addContent($this->getLayout()->createBlock('amorderexport/adminhtml_profile'))
             ->renderLayout();
    }
    
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }
    
    public function editAction()
    {
        $this->_title(Mage::helper('amorderexport')->__('Orders Export'))->_title(Mage::helper('amorderexport')->__('Edit Profile'));
        
        $id    = $this->getRequest()->getParam('profile_id');
        $model = Mage::getModel('amorderexport/profile');
        if ($id) 
        {
            $model->load($id);
            if (!$model->getId()) 
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amorderexport')->__('This profile no longer exists.'));
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
        
        Mage::register('amorderexport_profile', $model);
             
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit'))
            ->_addLeft($this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit_tabs'))
            ->renderLayout();
    }
    
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('profile_id')) 
        {
            try 
            {
                $model = Mage::getModel('amorderexport/profile');
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('amorderexport')->__('The profile has been deleted.'));
                $this->_redirect('*/*/');
                return;
                
            } catch (Exception $e) 
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('profile_id' => $id));
                return;
            }
        }
    }
    
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost())
        {
            $id             = $this->getRequest()->getParam('profile_id');
            $model          = Mage::getModel('amorderexport/profile');
            $redirectBack   = $this->getRequest()->getParam('back', false);

            if ($id)
            {
                $model->load($id);
            }
            
            $fieldsDatabase = array();
            $fieldsExport   = array();
            if (isset($data['fielddb']) && isset($data['fieldex']) && isset($data['fieldorder']))
            {
                $fieldsDatabase = $data['fielddb'];
                $fieldsExport   = $data['fieldex'];
                $fieldsOrder    = $data['fieldorder'];
                $fieldsHandlers = array();
                unset($data['fielddb']);
                unset($data['fieldex']);
                unset($data['fieldorder']);
                if (isset($data['handler']))
                {
                    $fieldsHandlers = $data['handler'];
                    unset($data['handler']);
                }
            }
            
            if (isset($data['store_ids']) && $data['store_ids'])
            {
                $data['store_ids'] = implode(',', $data['store_ids']);
            }
            
            if (isset($data['filter_status']) && $data['filter_status'])
            {
                $data['filter_status'] = implode(',', $data['filter_status']);
            } else {
                $data['filter_status'] = "";
            }
            
            $staticFields = isset($data['static']) ? $data['static'] : array();
            unset($data['static']);
            
            try
            {
                $data = $this->_filterDates($data, array('filter_date_from', 'filter_date_to')); // localization
                $model->setData($data);
                if (!$model->getId())
                {
                    $model->setCreatedAt(Mage::app()->getLocale()->date());
                }
                $model->save();
                
                if ($model->getExportAllfields())
                {
                    Mage::getModel('amorderexport/profile_field')->clearProfileFields($model);
                } elseif ($fieldsDatabase && $fieldsExport)
                {
                    Mage::getModel('amorderexport/profile_field')->addProfileFields($model, $fieldsDatabase, $fieldsExport, $fieldsHandlers, $fieldsOrder);
                }
                
                /* saving static fields */
                Mage::getModel('amorderexport/static_field')->saveForProfile($model->getId(), $staticFields);
                
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('amorderexport')->__('The export profile has been saved.'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                
                if ($redirectBack)
                {
                    $this->_redirect('*/*/edit', array(
                        'profile_id'    => $model->getId(),
                        '_current'      =>true
                    ));
                    return;
                }
                
                $this->_redirect('*/*/');
                return;
                
            } catch (Exception $e) 
            {
                $this->_getSession()->addException($e, Mage::helper('amorderexport')->__('An error occurred while saving the export profile: ') . $e->getMessage());
            }
            
            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', array('profile_id' => $id));
            return;
        }
        $this->_redirect('*/*/');
    }
    
    public function runAction()
    {        
        if ($id = $this->getRequest()->getParam('id'))
        {
            $model = Mage::getModel('amorderexport/profile');
            $model->load($id);
            
            if ($model->getId())
            {
                try
                {
                    $model->run();
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('amorderexport')->__('The export process has been executed successfully.'));
                    $this->_redirect('*/*/edit', array('profile_id' => $id));
                    return;
                } catch (Exception $e) 
                {
                    $this->_getSession()->addException($e, Mage::helper('amorderexport')->__('An error occurred while running the export profile: ') . $e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/edit', array('profile_id' => $id));
    }
    
    public function runGridAction()
    {
        if ($id = $this->getRequest()->getParam('id'))
        {
            $model = Mage::getModel('amorderexport/profile');
            $model->load($id);

            if ($model->getId())
            {
                $ids = $this->getRequest()->getParam('internal_order_ids');
                $ids = !empty($ids) ? explode(",", $ids) : array();
                $history = $model->run($ids);
                $history->download('');   
            }
        }
    }
    
    
    public function downloadAction()
    {
        if ($id = $this->getRequest()->getParam('id'))
        {
            $model = Mage::getModel('amorderexport/profile_history');
            $model->load($id);
            if ($model->getId())
            {
                $compression = $this->getRequest()->getParam('zip') ? 'zip' : '';
                try
                {
                    $model->download($compression);
                    exit;
                } catch (Exception $e) 
                {
                    $this->_getSession()->addException($e, Mage::helper('amorderexport')->__('An error occurred while downloading: ') . $e->getMessage());
                }
            }
        }
        
        $this->_redirect('*/*/');
    }
    
    public function historyAction() 
    {
        $id    = $this->getRequest()->getParam('profile_id');
        $model = Mage::getModel('amorderexport/profile');
        if ($id) 
        {
            $model->load($id);
            Mage::register('amorderexport_profile', $model);
            $this->getResponse()->setBody($this->getLayout()->createBlock('amorderexport/adminhtml_profile_edit_tab_history')->toHtml());
        } else 
        {
            $this->getResponse()->setBody();
        }
    }
}

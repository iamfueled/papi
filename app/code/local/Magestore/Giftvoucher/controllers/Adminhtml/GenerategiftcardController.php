<?php

class Magestore_Giftvoucher_Adminhtml_GenerategiftcardController extends Mage_Adminhtml_Controller_Action {

    public function exportCsvAction() {
        $fileName = 'generatetemplate_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.csv';
        $content = $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_grid')->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportXmlAction() {
        $fileName = 'generatetemplate_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.xml';
        $content = $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_grid')->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportGiftCodeCsvAction() {
        $fileName = 'giftcode_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.csv';
        $content = $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_giftcodelist')->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportGiftCodeXmlAction() {
        $fileName = 'giftcode_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.xml';
        $content = $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_giftcodelist')->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportGiftCodePdfAction() {
        $fileName = 'giftcode_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf';
        $pdf = $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_giftcodelist')->getPdf();
        $this->_prepareDownloadResponse($fileName, $pdf->render(), 'application/pdf');
    }

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('giftvoucher/giftvoucher')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Generate Manager'), Mage::helper('adminhtml')->__('Template Manager'));

        return $this;
    }

    public function indexAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $this->_title($this->__('Template'))
                ->_title($this->__('Manage Template'));
        $this->_initAction()
                ->renderLayout();
    }

    public function editAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('giftvoucher/template')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }



            $this->_title($this->__('Edit Template'));
            if ($model->getId()) {
                $this->_title($model->getGiftCode());
            } else {
                $this->_title($this->__('New Template'));
            }
            $model->getConditions()->setJsFormObject('giftvoucher_conditions_fieldset');
            Mage::register('template_data', $model);
            $this->loadLayout();
            $this->_setActiveMenu('giftvoucher/giftvoucher');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Template Manager'), Mage::helper('adminhtml')->__('Template Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Template News'), Mage::helper('adminhtml')->__('Template News'));

            $this->getLayout()->getBlock('head')
                    ->setCanLoadExtJs(true)
                    ->setCanLoadRulesJs(true);

            $this->_addContent($this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit'))
                    ->_addLeft($this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Template does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction() {
        $this->_forward('edit');
    }

    public function saveAction() {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('giftvoucher/template');

            $data = $this->_filterDates($data, array('expired_at'));
            if (!$data['expired_at'])
                $data['expired_at'] = null;

            if (isset($data['rule'])) {
                $rules = $data['rule'];
                if (isset($rules['conditions']))
                    $data['conditions'] = $rules['conditions'];
                unset($data['rule']);
            }
            
            if (!Mage::helper('giftvoucher')->isExpression($data['pattern'])){
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Invalid pattern'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
            
            $model->addData($data)
                    ->setId($this->getRequest()->getParam('id'));

            try {
                $model->loadPost($data);
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('giftvoucher')->__('Template was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Unable to find Template to save'));
        $this->_redirect('*/*/');
    }

    public function generateAction() {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('giftvoucher/template');
            if ($model->getIsGenerated()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Each template only generate a time'));
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            }
            $data = $this->_filterDates($data, array('expired_at'));
            if (!$data['expired_at'])
                $data['expired_at'] = null;

            if (isset($data['rule'])) {
                $rules = $data['rule'];
                if (isset($rules['conditions']))
                    $condition = $rules['conditions'];
                $data['conditions'] = $condition;
                unset($data['rule']);
            }
            
            if (!Mage::helper('giftvoucher')->isExpression($data['pattern'])){
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Invalid pattern'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }

            $model->setData($data)
                    ->setId($this->getRequest()->getParam('id'));

            try {
                $model->loadPost($data);
                $model->setIsGenerated(1)->save();
                Mage::getSingleton('adminhtml/session')->setFormData(false);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
            try {
                $data = $model->getData();
                $data['conditions'] = $condition;
                $data['gift_code'] = $data['pattern'];
                $data['template_id'] = $model->getId();
                $data['amount'] = $data['balance'];
                $data['status'] = Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE;
                $amount = $model->getAmount();
                for ($i = 1; $i <= $amount; $i++) {
                    Mage::getModel('giftvoucher/giftvoucher')->setData($data)->loadPost($data)->setIncludeHistory(true)->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('giftvoucher')->__('The template has been successfully saved and generated.'));
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            } catch (Exception $e) {
                $model->setIsGenerated(0)->save();
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Unable to find Template to save'));
        $this->_redirect('*/*/');
    }

    public function duplicateAction() {
        if ($this->getRequest()->getParam('id')) {
            $model = Mage::getModel('giftvoucher/template');

            $data = $model->load($this->getRequest()->getParam('id'))->getData();
            $data['is_generated'] = 0;
//            $data['template_name']=$data['template_name'].' duplicate';
            unset($data['template_id']);

            $model->setData($data);

            try {
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('giftvoucher')->__('Template was successfully duplicated'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('giftvoucher')->__('Unable to find Template to duplicate'));
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('giftvoucher/template');

                $model->setId($this->getRequest()->getParam('id'))
                        ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Template was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $templateIds = $this->getRequest()->getParam('template');
        if (!is_array($templateIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Template(s)'));
        } else {
            try {
                foreach ($templateIds as $templateId) {
                    $template = Mage::getModel('giftvoucher/template')->load($templateId);
                    $template->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__(
                                'Total of %d record(s) were successfully deleted', count($templateIds)
                        )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function GiftcodelistAction() {
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('giftvoucher/adminhtml_generategiftcard_edit_tab_giftcodelist')->toHtml()
        );
    }

}
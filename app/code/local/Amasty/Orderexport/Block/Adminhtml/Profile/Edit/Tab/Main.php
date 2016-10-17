<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _prepareForm()
    {
        /* @var $model Amasty_Flags_Model_Flag */
        $model = Mage::registry('amorderexport_profile');

        $form = new Varien_Data_Form();

        
        /**
        * 1. FIELDSET: Profile Information
        */
        
        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('amorderexport')->__('Profile Information')));

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', array(
                'name' => 'entity_id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => Mage::helper('amorderexport')->__('Name'),
            'title'     => Mage::helper('amorderexport')->__('Name'),
            'required'  => true,
        ));
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_ids', 'multiselect', array(
                'name'      => 'store_ids[]',
                'label'     => Mage::helper('amorderexport')->__('Store View'),
                'title'     => Mage::helper('amorderexport')->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'note'      => Mage::helper('amorderexport')->__('Export orders from specified store views only'),
            ));
        }
        else {
            $fieldset->addField('store_ids', 'hidden', array(
                'name'      => 'store_ids[]',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
            $model->setStoreIds(Mage::app()->getStore(true)->getId());
        }
        
        
        
        /**
        * 2. FIELDSET: File Information
        */
        
        $fieldset = $form->addFieldset('file_fieldset', array('legend' => Mage::helper('amorderexport')->__('File Properties')));
        
        $fieldset->addField('filename', 'text', array(
            'name'      => 'filename',
            'label'     => Mage::helper('amorderexport')->__('File Name'),
            'title'     => Mage::helper('amorderexport')->__('File Name'),
            'note'      => Mage::helper('amorderexport')->__('Just name, with no extension. Will be used both for file saved to local folder and for one uploaded via FTP.'),
        ));
        
        $fieldset->addField('path', 'text', array(
            'name'      => 'path',
            'label'     => Mage::helper('amorderexport')->__('File Path (Local)'),
            'title'     => Mage::helper('amorderexport')->__('File Path (Local)'),
            'note'      => Mage::helper('amorderexport')->__('Absolute path, or relative to Magento install root, ex. "var/export/". Please make sure that this directory exists and is writeable.'),
        ));
        
        /**
        * 3. FIELDSET: Data Format
        */
        
        $fieldset = $form->addFieldset('run_after_order_creation_fieldset', array('legend' => Mage::helper('amorderexport')->__('Automatic Execution')));
        
        $fieldset->addField('run_after_order_creation', 'select', array(
            'name'      => 'run_after_order_creation',
            'label'     => Mage::helper('amorderexport')->__('Run automatically after each new order is placed'),
            'title'     => Mage::helper('amorderexport')->__('Run automatically after each new order is placed'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        /**
        * 4. FIELDSET: Data Format
        */
        
        $fieldset = $form->addFieldset('ftp_fieldset', array('legend' => Mage::helper('amorderexport')->__('FTP Configuration')));
        
        $fieldset->addField('ftp_use', 'select', array(
            'name'      => 'ftp_use',
            'label'     => Mage::helper('amorderexport')->__('Upload Exported File By FTP'),
            'title'     => Mage::helper('amorderexport')->__('Upload Exported File By FTP'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
            'onchange'  => 'javascript: profileCheckFtp();',
        ));
        
        $fieldset->addField('ftp_host', 'text', array(
            'name'      => 'ftp_host',
            'label'     => Mage::helper('amorderexport')->__('FTP Hostname'),
            'title'     => Mage::helper('amorderexport')->__('FTP Hostname'),
            'note'      => Mage::helper('amorderexport')->__('If you use non-standard port (not 21), please specify hostname like example.com:23, where 23 is your custom port'),
        ));
        
        $fieldset->addField('ftp_login', 'text', array(
            'name'      => 'ftp_login',
            'label'     => Mage::helper('amorderexport')->__('FTP Login'),
            'title'     => Mage::helper('amorderexport')->__('FTP Login'),
        ));
        
        $fieldset->addField('ftp_password', 'text', array(
            'name'      => 'ftp_password',
            'label'     => Mage::helper('amorderexport')->__('FTP Password'),
            'title'     => Mage::helper('amorderexport')->__('FTP Password'),
        ));
        
        $fieldset->addField('ftp_is_passive', 'select', array(
            'name'      => 'ftp_is_passive',
            'label'     => Mage::helper('amorderexport')->__('Use Passive Mode'),
            'title'     => Mage::helper('amorderexport')->__('Use Passive Mode'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        $fieldset->addField('ftp_path', 'text', array(
            'name'      => 'ftp_path',
            'label'     => Mage::helper('amorderexport')->__('File Path (FTP)'),
            'title'     => Mage::helper('amorderexport')->__('File Path (FTP)'),
        ));
        
        $fieldset->addField('ftp_delete_local', 'select', array(
            'name'      => 'ftp_delete_local',
            'label'     => Mage::helper('amorderexport')->__('Delete Local File After FTP Upload'),
            'title'     => Mage::helper('amorderexport')->__('Delete Local File After FTP Upload'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));


        /**
         * FIELDSET: E-mail Settings
         */
        $fieldset = $form->addFieldset('email_fieldset', array('legend' => Mage::helper('amorderexport')->__('E-mail Settings')));

        $fieldset->addField('email_use', 'select', array(
            'name'      => 'email_use',
            'label'     => Mage::helper('amorderexport')->__('Send Exported File to E-mail'),
            'title'     => Mage::helper('amorderexport')->__('Send Exported File to E-mail'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
            'onchange'  => 'javascript: profileCheckEmail();',
        ));

        $fieldset->addField('email_address', 'text', array(
            'name'      => 'email_address',
            'label'     => Mage::helper('amorderexport')->__('E-mail Address'),
            'title'     => Mage::helper('amorderexport')->__('E-mail Address'),
        ));

        $fieldset->addField('email_subject', 'text', array(
            'name'      => 'email_subject',
            'label'     => Mage::helper('amorderexport')->__('E-mail Message Subject'),
            'title'     => Mage::helper('amorderexport')->__('E-mail Message Subject'),
        ));

        $fieldset->addField('email_compress', 'select', array(
            'name'      => 'email_compress',
            'label'     => Mage::helper('amorderexport')->__('Compress Exported File in ZIP'),
            'title'     => Mage::helper('amorderexport')->__('Compress Exported File in ZIP'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));

        
        /**
        * 5. FIELDSET: Data Format
        */
        
        $fieldset = $form->addFieldset('data_fieldset', array('legend' => Mage::helper('amorderexport')->__('Data Format')));
        
        $fieldset->addField('format', 'select', array(
            'name'      => 'format',
            'label'     => Mage::helper('amorderexport')->__('File Format'),
            'title'     => Mage::helper('amorderexport')->__('File Format'),
            'values'    => Mage::helper('amorderexport/profile')->getDataFormats(),
            'onchange'  => 'javascript: profileCheckFormat();',
        ));
        
        $fieldset->addField('export_include_fieldnames', 'select', array(
            'name'      => 'export_include_fieldnames',
            'label'     => Mage::helper('amorderexport')->__('Field Names In The First Row'),
            'title'     => Mage::helper('amorderexport')->__('Field Names In The First Row'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        /**
        * 6. FIELDSET: CSV Configuration
        */
        
        $fieldset = $form->addFieldset('csv_fieldset', array('legend' => Mage::helper('amorderexport')->__('CSV Configuration')));
        
        $fieldset->addField('csv_delim', 'text', array(
            'name'      => 'csv_delim',
            'label'     => Mage::helper('amorderexport')->__('Delimiter'),
            'title'     => Mage::helper('amorderexport')->__('Delimiter'),
        ));
        
        $fieldset->addField('csv_enclose', 'text', array(
            'name'      => 'csv_enclose',
            'label'     => Mage::helper('amorderexport')->__('Enclose Values In'),
            'title'     => Mage::helper('amorderexport')->__('Enclose Values In'),
            'note'      => $this->__('Warning! Empty value can cause problems with CSV format.'),
        ));
        
        /**
        * FIELDSET: Filters by order number
        */

        $fieldset = $form->addFieldset('filters_order_number_fieldset', array('legend' => Mage::helper('amorderexport')->__('Order Number Filters')));

        $fieldset->addField('filter_number_from', 'text', array(
            'name'      => 'filter_number_from',
            'label'     => Mage::helper('amorderexport')->__('Starting From #'),
            'title'     => Mage::helper('amorderexport')->__('Starting From #'),
            'note'      => $this->__('Order number to start export from. Ex. 100000040. Leave empty to ignore.'),

            'after_element_html' =>
            ( $model->getLastIncrementId() ? $this->__('Last Order Exported: %s', $model->getLastIncrementId()) : '' )
                .( ($nextIncrementId = Mage::helper('amorderexport/profile')->getNextIncrementId($model->getLastIncrementId())) ? '<br />Next Order Number: ' . $nextIncrementId : '' )
        ,
        ));

        $fieldset->addField('filter_number_to', 'text', array(
            'name'      => 'filter_number_to',
            'label'     => Mage::helper('amorderexport')->__('Ending With #'),
            'title'     => Mage::helper('amorderexport')->__('Ending With #'),
            'note'      => $this->__('Order number to end export with. Leave empty to ignore.'),
        ));

        $fieldset->addField('filter_number_from_skip', 'select', array(
            'name'      => 'filter_number_from_skip',
            'label'     => Mage::helper('amorderexport')->__('Skip Starting From'),
            'title'     => Mage::helper('amorderexport')->__('Skip Starting From'),
            'note'      => $this->__('In case of "Yes" export will start from the order, next to the specified in the "Starting From #" field. Else specified order will be exported as well.'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));

        $fieldset->addField('increment_auto', 'select', array(
            'name'      => 'increment_auto',
            'label'     => Mage::helper('amorderexport')->__('Automatically Increment Starting From'),
            'title'     => Mage::helper('amorderexport')->__('Automatically Increment Starting From'),
            'note'      => Mage::helper('amorderexport')->__('Automatically fill "Starting From #" field with the last exported order number after each profile run'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));


        /**
         * FIELDSET: Filters by shipment number
         */

        $fieldset = $form->addFieldset('filters_shipment_number_fieldset', array('legend' => Mage::helper('amorderexport')->__('Shipment Number Filters')));

        $fieldset->addField('filter_shipment_from', 'text', array(
            'name'      => 'filter_shipment_from',
            'label'     => Mage::helper('amorderexport')->__('Starting From Shipment #'),
            'title'     => Mage::helper('amorderexport')->__('Starting From Shipment #'),
            'note'      => $this->__('Filter orders by shipment numbers.'),
        ));

        $fieldset->addField('filter_shipment_to', 'text', array(
            'name'      => 'filter_shipment_to',
            'label'     => Mage::helper('amorderexport')->__('Ending With Shipment #'),
            'title'     => Mage::helper('amorderexport')->__('Ending With Shipment #'),
            'note'      => $this->__('Filter orders by shipment numbers.'),
        ));

        /**
        * FIELDSET: Filters by order number
        */

        $fieldset = $form->addFieldset('filters_invoice_fieldset', array('legend' => Mage::helper('amorderexport')->__('Invoice Filters')));

        $fieldset->addField('filter_invoice_from', 'text', array(
            'name'      => 'filter_invoice_from',
            'label'     => Mage::helper('amorderexport')->__('Starting From #'),
            'title'     => Mage::helper('amorderexport')->__('Starting From #'),
            'note'      => $this->__('Invoice number to start export from. Ex. 100000040. Leave empty to ignore.'),

            'after_element_html' => 
            ( $model->getLastInvoiceIncrementId() ? $this->__('Last Invoice Exported: %s', $model->getLastInvoiceIncrementId()) : '' )
                .( ($nextInvoiceIncrementId = Mage::helper('amorderexport/profile')->getNextInvloceIncrementId($model->getLastInvoiceIncrementId())) ? '<br />Next Invoice Number: ' . $nextInvoiceIncrementId : '' )
        ,
        ));

        $fieldset->addField('filter_invoice_to', 'text', array(
            'name'      => 'filter_invoice_to',
            'label'     => Mage::helper('amorderexport')->__('Ending With #'),
            'title'     => Mage::helper('amorderexport')->__('Ending With #'),
            'note'      => $this->__('Invoice number to end export with. Leave empty to ignore.'),
        ));

        $fieldset->addField('filter_invoice_from_skip', 'select', array(
            'name'      => 'filter_invoice_from_skip',
            'label'     => Mage::helper('amorderexport')->__('Skip Starting From'),
            'title'     => Mage::helper('amorderexport')->__('Skip Starting From'),
            'note'      => $this->__('In case of "Yes" export will start from the order, next to the specified in the "Starting From #" field. Else specified order will be exported as well.'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));

        $fieldset->addField('invoice_increment_auto', 'select', array(
            'name'      => 'invoice_increment_auto',
            'label'     => Mage::helper('amorderexport')->__('Automatically Increment Starting From'),
            'title'     => Mage::helper('amorderexport')->__('Automatically Increment Starting From'),
            'note'      => Mage::helper('amorderexport')->__('Automatically fill "Starting From #" field with the last exported order number after each profile run'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));


        /**
         * FIELDSET: Other filters
         */
        
        $fieldset = $form->addFieldset('filters_other_fieldset', array('legend' => Mage::helper('amorderexport')->__('Other Export Filters')));

        $fieldset->addField('filter_sku', 'textarea', array(
            'name'      => 'filter_sku',
            'label'     => Mage::helper('amorderexport')->__('Product SKU(s)'),
            'title'     => Mage::helper('amorderexport')->__('Product SKU(s)'),
            'note'      => $this->__('Export orders which contain listed products. Split multiple SKUs by comma (,) character'),
        ));

        $fieldset->addField('filter_sku_onlylines', 'select', array(
            'name'      => 'filter_sku_onlylines',
            'label'     => Mage::helper('amorderexport')->__('Include only lines with SKU found'),
            'title'     => Mage::helper('amorderexport')->__('Include only lines with SKU found'),
            'note'      => Mage::helper('amorderexport')->__('If set to "No", all products from orders with specified SKUs will be exported'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
//        $statuses = array_merge(array('' => $this->__('- All -')), $statuses);
        
        $multiStatuses = array();
        
        foreach($statuses as $key => $status) {
            $multiStatuses[] = array(
                'value' => $key,
                'label' => $status
            );
        }
        
        $fieldset->addField('filter_status', 'multiselect', array(
            'name'      => 'filter_status',
            'label'     => Mage::helper('amorderexport')->__('Order Status'),
            'title'     => Mage::helper('amorderexport')->__('Order Status'),
            'values'    => $multiStatuses,
        ));
        
        $groups = array('' => $this->__('- All -'));
        $customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
        $found = false;
        foreach ($customerGroups as $i => $group) {
            if ($group['value'] != 0) {
                $groups[$group['value']] = $group['label'];
            }
        }
        $fieldset->addField('filter_customergroup', 'select', array(
            'name'      => 'filter_customergroup',
            'label'     => Mage::helper('amorderexport')->__('Customer Group'),
            'title'     => Mage::helper('amorderexport')->__('Customer Group'),
            'values'    => $groups,
        ));
        
        $fieldset->addField('filter_skip_zero_price', 'select', array(
            'name'      => 'filter_skip_zero_price',
            'label'     => Mage::helper('amorderexport')->__('Skip items with zero price'),
            'title'     => Mage::helper('amorderexport')->__('Skip items with zero price'),
            'note'      => Mage::helper('amorderexport')->__('Can be used to skip duplicated rows for configurable products purchases'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        $fieldset->addField('filter_date_from', 'date', array(
            'name'          => 'filter_date_from',
            'label'         => Mage::helper('amorderexport')->__('Placed From Date'),
            'title'         => Mage::helper('amorderexport')->__('Placed From Date'),
            'image'         => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format'  => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'        => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
        ));

        $fieldset->addField('filter_date_to', 'date', array(
            'name'          => 'filter_date_to',
            'label'         => Mage::helper('amorderexport')->__('Placed Until Date'),
            'title'         => Mage::helper('amorderexport')->__('Placed Until Date'),
            'image'         => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format'  => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'        => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
        ));

        /**
        * 7. FIELDSET: Field Mapping
        */
        
        $fieldset = $form->addFieldset('mapping_fieldset', array('legend' => Mage::helper('amorderexport')->__('Field Mapping')));
        
        $fieldset->addField('export_custom_options', 'select', array(
            'name'      => 'export_custom_options',
            'label'     => Mage::helper('amorderexport')->__('Export each product custom option in a separate column'),
            'title'     => Mage::helper('amorderexport')->__('Export each product custom option in a separate column'),
            'note'      => Mage::helper('amorderexport')->__('You need to select "product.product_options" in the list of fields to export custom options selected'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        $fieldset->addField('export_attributes_info', 'select', array(
            'name'      => 'export_attributes_info',
            'label'     => Mage::helper('amorderexport')->__('Export each product attribute in a separate column'),
            'title'     => Mage::helper('amorderexport')->__('Export each product attribute in a separate column'),
            'note'      => Mage::helper('amorderexport')->__('You need to select "product.product_options" in the list of fields to export attributes info'),
            'values'    => Mage::helper('amorderexport/profile')->getYesNo(),
        ));
        
        $fieldset->addField('export_allfields', 'select', array(
            'name'      => 'export_allfields',
            'label'     => Mage::helper('amorderexport')->__('Fields To Export'),
            'title'     => Mage::helper('amorderexport')->__('Fields To Export'),
            'values'    => Mage::helper('amorderexport/profile')->getExportScope(),
            'onchange'  => 'javascript: checkMapping(this);',
            
            'after_element_html'    => '<div style="padding-top: 10px; display: none;" id="add_field_mapping"><button onclick="addFieldMapping()" class="scalable add" type="button"><span>' . $this->__('Add Field Mapping') . '</span></button></div>',
        ));

        
        /**
        * 8. FIELDSET: Post data handling
        */
        
        $fieldset = $form->addFieldset('post_fieldset', array('legend' => Mage::helper('amorderexport')->__('Post-Export Data Handling')));
        
        $fieldset->addField('post_date_format', 'text', array(
            'name'      => 'post_date_format',
            'label'     => Mage::helper('amorderexport')->__('Date Format'),
            'title'     => Mage::helper('amorderexport')->__('Date Format'),
            'note'      => $this->__('Convert all dates to the specified format (according to the php <a href="http://php.net/manual/en/function.date.php" target="_blank">date()</a> function format). Leave empty for no post-processing.')
        				   . '<br />'
        				   . $this->__('For example use <strong>d/m/Y</strong> to get ') . date('d/m/Y'),
        ));
        
        $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        $statuses = array_merge(array('' => $this->__('- Do not change -')), $statuses);
        $fieldset->addField('post_status', 'select', array(
            'name'      => 'post_status',
            'label'     => Mage::helper('amorderexport')->__('Status For Processed Orders'),
            'title'     => Mage::helper('amorderexport')->__('Status For Processed Orders'),
            'values'    => $statuses,
            'note'      => $this->__('Exported orders will get specified status'),
        ));
        

        $form->setValues($model->getData());
        if (!$model->getId()) 
        {
            $form->addValues(array(
                'filename'      => 'exported_orders',
                'path'          => 'var/export/',
                'csv_delim'     => ',',
                'csv_enclose'   => '"',
                'ftp_use'       => '0',
                'run_after_order_creation' => '0',
            ));
        }
        
        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    public function getTabLabel()
    {
        return Mage::helper('amorderexport')->__('Profile Configuration');
    }
    
    public function getTabTitle()
    {
        return Mage::helper('amorderexport')->__('Profile Configuration');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
    
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        
        // adding handler options
        $handlers = array(
            'product.product_options'   => array(
                ''      => $this->__(''),
                'links' => $this->__('Downloadable Links'),
            ),
        );
                
        $js =   '<script type="text/javascript" src="' . Mage::getBaseUrl('js') . 'amasty/amorderexport/admin.js"></script>'
              . '<script type="text/javascript">
                     OrderExport.csvConfigLabel = "' . $this->__('CSV Configuration') . '";
                     OrderExport.dbString = "' . $this->__('Field in DB: ') . '";
                     OrderExport.exString = "' . $this->__('Field in Export: ') . '";
                     OrderExport.hlString = "' . $this->__('Handler: ') . '";
                     OrderExport.orString = "' . $this->__('Order: ') . '";
                     OrderExport.fieldSel = \'' . Mage::helper('amorderexport/profile')->getFieldsSelectHtml() . '\';
                     OrderExport.handlerOptions = ' . Zend_Json::encode($handlers) . ';
                     Event.observe(window, "load", initMappingOnLoad);
                ' ;
                
        // adding mapping values
        $model  = Mage::registry('amorderexport_profile');
        $fields = $model->getFields();
        /* @var $fields Amasty_Orderexport_Model_Mysql4_Profile_Field_Collection */
        if ($fields->getSize() > 0)
        {
            foreach ($fields as $field)
            {
                $js .= 'Event.observe(window, "load", function() {
                    addFieldMapping();
                    
                    onSelectMapping($("fielddb_" + ( - 1 + OrderExport.fieldCnt)), "' . $field->getHandler() . '");
                    $("fielddb_" + ( - 1 + OrderExport.fieldCnt)).value = "' . $field->getFieldTable() . '.' . $field->getFieldName() . '";
                    $("fieldex_" + ( - 1 + OrderExport.fieldCnt)).value = "' . $field->getMappedName() . '";
                    $("fieldorder_" + ( - 1 + OrderExport.fieldCnt)).value = "' . $field->getSortingOrder() . '";
                });';
            }
        }
                
        $js .=  '</script>';
        $html = $js . $html;
        return $html;
    }
}

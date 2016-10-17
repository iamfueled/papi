<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Profile extends Mage_Core_Model_Abstract
{
    protected $_handlers = array();
    
    protected $_staticFields = array();
    
    protected function _construct()
    {
        $this->_init('amorderexport/profile');
    }
    
    public function getFields()
    {
        $fieldModel = Mage::getModel('amorderexport/profile_field');
        $fields = $fieldModel->getFields($this);
        return $fields;
    }
    
    public function getStaticFields()
    {
        if (!$this->_staticFields)
        {
            $collection = Mage::getModel('amorderexport/static_field')->getCollection();
            $collection->addFieldToFilter('profile_id', $this->getId());
            $collection->getSelect()->order('entity_id');
            if ($collection->getSize() > 0)
            {
                foreach ($collection as $field)
                {
                    $this->_staticFields[] = array(
                        'label'    => $field->getLabel(),
                        'value'    => $field->getValue(),
                        'position' => $field->getPosition(),
                    );
                }
            }
        }
        return $this->_staticFields;
    }
    
    public function delete()
    {
        Mage::getModel('amorderexport/profile_field')->clearProfileFields($this);
        return parent::delete();
    }
    
    protected function _afterLoad()
    {
        if (intval($this->getFilterDateFrom()) == 0)
        {
            $this->setFilterDateFrom('');
        }
        if (intval($this->getFilterDateTo()) == 0)
        {
            $this->setFilterDateTo('');
        }
        return parent::_afterLoad();
    }
    
    protected function _beforeSave()
    {
        if ($this->getFilterDateFrom())
        {
            $this->setFilterDateFrom(date('Y-m-d', strtotime($this->getFilterDateFrom())));
        }
        if ($this->getFilterDateTo())
        {
            $this->setFilterDateTo(date('Y-m-d', strtotime($this->getFilterDateTo())));
        }
        return parent::_beforeSave();
    }
    
    protected function _initExportCustomOptions(&$productOptions, &$ret){
        if ($this->getExportCustomOptions()){
            if ($productOptions && isset($productOptions['options']) && $productOptions['options'])
            {
                $optionsIds = array();
                foreach ($productOptions['options'] as $option)
                {
                    if (!in_array($option['option_id'], $optionsIds))
                    {
                        $optionsIds[] = $option['option_id'];
                    }
                }
                
                $optionsCollection = Mage::getModel('catalog/product_option')->getCollection();
                $optionsCollection->addIdsToFilter($optionsIds)->addTitleToResult(Mage::app()->getStore()->getId())->load();
                foreach ($optionsCollection as $optionTitle)
                {
                    $ret["options"][$optionTitle->getOptionId()] = $optionTitle->getDefaultTitle();
                }
            }
        }
    }
    
    protected function _initExportAttributesInfo(&$productOptions, &$ret){
        if ($this->getExportAttributesInfo()){
            if ($productOptions && isset($productOptions['attributes_info']) && $productOptions['attributes_info'])
            {
                foreach ($productOptions['attributes_info'] as $option)
                {
                    if (!in_array($option['label'], $ret["attributes"]))
                    {
                        $ret["attributes"][] = $option['label'];
                    }
                }
            }
        }
    }
    
    protected function _getProductOptionsColumns($query){
        $ret = array(
            "options" => array(),
            "attributes" => array(),
            "custom_option_field" => null
        );
        $connection   = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $customOptionsQuery = $query;
        if (preg_match('/`product`.`product_options` AS `(.*?)`/', $customOptionsQuery, $match))
        {
            $ret["custom_option_field"]  = $match[1];
            $customOptionsQuery = preg_replace('/SELECT (.*?) FROM/', 'SELECT `product`.`product_options` FROM', $customOptionsQuery);
            
            $optionsResult      = $connection->raw_query($customOptionsQuery);
            while ($optionsRow = $optionsResult->fetch(PDO::FETCH_ASSOC)){
                if (isset($optionsRow['product_options']) && $optionsRow['product_options'])
                {
                    $productOptions = unserialize($optionsRow['product_options']);
                    $this->_initExportCustomOptions($productOptions, $ret);
                    $this->_initExportAttributesInfo($productOptions, $ret);
                    
                }
                
            }
        }
        return $ret;
    }
    
    protected function _initCustomOptionsRow($productOptions, $productOptionsColumns, &$rowFinal){
        
        if (isset($productOptionsColumns['options'])){
            foreach($productOptionsColumns['options'] as $optionId => $optionLabel){
                
                if (isset($productOptions['options'])) {
                    foreach($productOptions['options'] as $optionInfo){
                        if ($optionInfo['option_id'] == $optionId){
                            
                            $rowFinal[$optionLabel] = isset($optionInfo['value']) ? $optionInfo['value'] : $optionInfo['option_value'];
                            break;
                        }
                    }
                }
                
                if (!isset($rowFinal[$optionLabel]))
                    $rowFinal[$optionLabel] = "";
            }
        }
    }
    
    protected function _initAttributesRow($productOptions, $productOptionsColumns, &$rowFinal){
        
        
        if (isset($productOptionsColumns['attributes'])){
            
            
            foreach($productOptionsColumns['attributes'] as $attribute){
                
                if (isset($productOptions['attributes_info'])){
                    foreach($productOptions['attributes_info'] as $attributeInfo){
                        if ($attributeInfo['label'] == $attribute){
                            $rowFinal[$attribute] = $attributeInfo['value'];
                            break;
                        } 
                    }
                }
                
                if (!isset($rowFinal[$attribute])){
                    $rowFinal[$attribute] = "";
                }
            }   
        }
    }
    


    public function run($ids = array())
    {
        ignore_user_abort(true);
        set_time_limit(0);
        
        $startTime    = microtime(true);
        $connection   = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query        = $this->_getQueryExport($ids);
        $processedIds = array();
        
        
        $exportProductOptions = strpos($query, 'product_options') !== false;
        $splitByColumns = $exportProductOptions && ($this->getExportCustomOptions() || $this->getExportAttributesInfo());
        $productOptionsColumns = null;
        
        if ($splitByColumns){
            $productOptionsColumns = $this->_getProductOptionsColumns($query);
        }
        
        
        // if we need to change order status, we also need to get state
        if ($this->getPostStatus())
        {
            $collection = Mage::getResourceModel('sales/order_status_collection');
            $collection->joinStates();
            foreach ($collection as $status)
            {
                if ($this->getPostStatus() == $status->getStatus())
                {
                    $this->setPostState($status->getState());
                }
            }
            
            if (Mage::helper('core')->isModuleEnabled('Amasty_Orderstatus')) {
                
                $collection = Mage::getResourceModel('amorderstatus/status_collection');

                $collection->getSelect()->joinLeft(
                    array('state_table' => Mage::getSingleton('core/resource')->getTableName('sales/order_status_state')),
                    'main_table.status=state_table.status',
                    array('state', 'is_default')
                );

                foreach ($collection as $status)
                {
                    if ($this->getPostStatus() == $status->getParentState().'_'.$status->getAlias())
                    {
                        $this->setPostState($status->getParentState());
                    }
                }
            }
        }

        $result     = $connection->raw_query($query);
        $rows       = 0;
        
        $filePath   = $this->getPath() . $this->getFilename() . $this->_getFileExtension();
        $exportFile = Mage::getBaseDir() . DS . $filePath;
        $file       = fopen($exportFile, 'w+');
        
        $this->_initFile($file);
        
        $lastId = 0;
        $lastInvoiceId = 0;
        while ($row = $result->fetch(PDO::FETCH_ASSOC))
        {
            // merging with custom options columns, if any
            if ($splitByColumns)
            {
                $rowFinal = array();
                foreach ($row as $column => $value)
                {
                    if ($column == $productOptionsColumns["custom_option_field"])
                    {
                        $productOptions = unserialize($value);
                        $this->_initCustomOptionsRow($productOptions, $productOptionsColumns, $rowFinal);
                        $this->_initAttributesRow($productOptions, $productOptionsColumns, $rowFinal);
                    }
                    else
                    {
                        $rowFinal[$column] = $value;
                    }
                }
                
                $row = $rowFinal;
            }
            // finish merging with custom options
            
            if (isset($row['id_track']) && $row['id_track'] > $lastId)
            {
                $lastId = $row['id_track'];
            }
            
            if (isset($row['id_invoice_track']) && $row['id_invoice_track'] > $lastInvoiceId)
            {
                $lastInvoiceId = $row['id_invoice_track'];
            }
                        
            if (isset($row['id_track']))
            {
                $processedIds[] = $row['id_track'];
                unset($row['id_track']);
            }
            
            
            
            if (array_key_exists('id_invoice_track', $row))
            {
                unset($row['id_invoice_track']);
            }
                        
            if ($this->getStaticFields())
            {
                foreach ($this->getStaticFields() as $field)
                {
                    $value = $this->_initStaticValue($field['value']);
                    
                    if (Amasty_Orderexport_Model_Static_Field::POSITION_BEGINNING == $field['position'])
                    {
                        $row = array_reverse($row, true);
                        $row[$field['label']] = $value;
                        $row = array_reverse($row, true);
                    }
                    if (Amasty_Orderexport_Model_Static_Field::POSITION_END == $field['position'])
                    {
                        $row[$field['label']] = $value;
                    }
                }
            }

            if (0 == $rows && $this->getExportIncludeFieldnames())
            {
                $this->_writeHead($file, $row);
            }
            $this->_writeRow($file, $row);
            
            $rows++;
        }
        
        $this->_finalizeFile($file);
        
        fclose($file);
        
        // changing statuses for processed orders
        if ($this->getPostStatus() && $this->getPostState() && !empty($processedIds))
        {
            foreach($processedIds as $entityId){
                
                $order = Mage::getModel('sales/order')->load($entityId);
                $order->setData('state', $this->getPostState());
                $order->setStatus($this->getPostStatus());
                $history = $order->addStatusHistoryComment('Status has been changed:', false);
                $history->setIsCustomerNotified(false);
                $order->save();

            }
//            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
//            $sql   = ' UPDATE ' . Mage::getModel('core/resource')->getTableName('sales/order') . 
//                     ' SET state = "' . $this->getPostState() .'", status = "' . $this->getPostStatus() . '" WHERE entity_id IN (' . implode(',', $processedIds) . ') ';
//            $write->raw_query($sql);
//            $sql   = ' UPDATE ' . Mage::getModel('core/resource')->getTableName('sales/order_grid') . 
//                     ' SET status = "' . $this->getPostStatus() . '" WHERE entity_id IN (' . implode(',', $processedIds) . ') ';
//            $write->raw_query($sql);
        }
        
        // will store this value
        $lastIncrementId = Mage::getModel('sales/order')->load($lastId)->getIncrementId();
        $lastInvoiceIncrementId = Mage::getModel('sales/order_invoice')->load($lastInvoiceId)->getIncrementId();
        
        // copying exported file with current date in the filename
        $dateFilePath = $this->getPath() . $this->getFilename() . '' . date(Mage::getStoreConfig('amorderexport/export/file_date_format'), Mage::app()->getLocale()->date()->get()) . $this->_getFileExtension();
        copy($exportFile, Mage::getBaseDir() . DS . $dateFilePath);
        
        $runTime = microtime(true) - $startTime;
        
        // saving run history
        $history = Mage::getModel('amorderexport/profile_history');
        $historyData = array(
            'profile_id'        => $this->getId(),
            'run_at'            => date('Y-m-d H:i:s', Mage::app()->getLocale()->date()->get()),
            'last_increment_id' => $lastIncrementId,
            'last_invoice_increment_id' => $lastInvoiceIncrementId,
            'file_path'         => $dateFilePath,
            'file_size'         => filesize($exportFile),
            'run_time'          => $runTime,
            'run_records'       => $rows,
        );
        $history->addData($historyData)->save();
        
        // will show it on the profile page
        $this->setLastIncrementId($lastIncrementId);
        $this->setLastInvoiceIncrementId($lastInvoiceIncrementId);
        
        if ($this->getIncrementAuto())
        {
            $this->setFilterNumberFrom($lastIncrementId);
        }
        
        if ($this->getInvoiceIncrementAuto()){
            $this->setFilterInvoiceFrom($lastInvoiceIncrementId);
        }
        
        $this->save();
        
        // uploading to FTP is selected
        if ($this->getFtpUse())
        {
            $arrHost = explode(':', $this->getFtpHost());
            $ftpHost = $arrHost[0];
            $ftpPort = isset($arrHost[1]) ? $arrHost[1] : false;
            if (!$ftpPort) { $ftpPort = 21; }
            $ftp      = ftp_connect($ftpHost, $ftpPort, 10);
            if ($ftp)
            {
                $ftpLogin = ftp_login($ftp, $this->getFtpLogin(), $this->getFtpPassword());
                if ($ftpLogin)
                {
                    if ($this->getFtpIsPassive())
                    {
                        ftp_pasv($ftp, true);
                    }
                    $remotePath = $this->getFtpPath();
                    if ('/' != substr($remotePath, -1, 1) && '\\' != substr($remotePath, -1, 1))
                    {
                        $remotePath .= '/';
                    }
                    $remoteFileName = substr($exportFile, strrpos($exportFile, '/') + 1);
                    $remotePath .= $remoteFileName;
                    $upload = @ftp_put($ftp, $remotePath, $exportFile, FTP_ASCII);
                    if (!$upload)
                    {
                        throw new Exception('Error uploading file to the FTP server.');
                    }
                    ftp_close($ftp);
                    

                } else 
                {
                    throw new Exception('Error logging in to the FTP server.');
                }
            } else 
            {
                throw new Exception('Error connecting to the FTP server.');
            }
        }

        if ($this->getEmailUse())
        {
            if ($this->getEmailAddress())
            {
                $this->setEmailSubject($this->getEmailSubject() . '_' .$lastIncrementId);
                        
                $attachmentFile = $exportFile;
                if ($this->getEmailCompress())
                {
                    $attachmentFile = $exportFile . '.zip';
                    Mage::helper('amorderexport/compress')->zip($exportFile, $attachmentFile);
                }
                Mage::helper('amorderexport/email')->sendExported($this, $attachmentFile);
            }
        }

        if ($this->getFtpUse())
        {
            // deleting local file is specified
            if ($this->getFtpDeleteLocal())
            {
                if (file_exists($exportFile) && is_writable($exportFile))
                {
                    @unlink($exportFile);
                }
            }
        }
        
        return $history;
    }
    
    protected function _initStaticValue($value){
        
        $repl = array(
            "%now%" => date("Y-m-d H:i:s")
        );
        
        return strtr($value, $repl);
    }


    protected function _initFile($file)
    {
        switch ($this->getFormat())
        {
            case 'xml':
                $xmlHead = '<?xml version="1.0" encoding="UTF-8"?>
                            <?mso-application progid="Excel.Sheet"?>
                            <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet">
                                  <OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"></OfficeDocumentSettings>
                                  <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"></ExcelWorkbook>
                                  <Worksheet ss:Name="Sheet 1">
                                        <Table>';
                fwrite($file, $xmlHead, mb_strlen($xmlHead));
            break;
            case 'csv':
//                fwrite($file, "\xEF\xBB\xBF");
//                fwrite($file, pack("CCC", 0xef, 0xbb, 0xbf)); // for UTF-8 compatibility (special chars support)
            break;
        }
    }
    
    protected function _finalizeFile($file)
    {
        switch ($this->getFormat())
        {
            case 'xml':
                $xmlFooter = '              </Table>
                                      </Worksheet>
                                </Workbook>';
                fwrite($file, $xmlFooter, mb_strlen($xmlFooter));
            break;
        }
    }
    
    protected function _writeHead($file, $row)
    {
        $this->_writeRow($file, array_keys($row));
    }
    
    protected function _writeRow($file, $row)
    {
        $this->_processHandlers($row);

        switch ($this->getFormat())
        {
            case 'csv':
                $newline = "\r\n";
                $firstout = false;
                foreach ($row as $ind =>  $field)
                    {
//                    if ($firstout)
//                    {
//                        fwrite($file, $this->getCsvDelim(), mb_strlen($this->getCsvDelim()));
//                    }
                    $field = $this->_postProcessField($field);
                    $field = str_replace("\n", ' ', $field);
                    $field = str_replace("\r", ' ', $field);
                    $row[$ind] = $field;
                    
//                    $field = $this->getCsvEnclose() . addslashes($field) . $this->getCsvEnclose();
//                    if (function_exists('iconv'))
//                    {
//                        $field = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $field);
//                    }
//                    
//                    
//                    
//                    fwrite($file, $field, mb_strlen($field));
                    $firstout = true;
                }
                
                fputcsv($file, $row, $this->getCsvDelim(), $this->getCsvEnclose());
                
//                fwrite($file, $newline, mb_strlen($newline));
            break;
            
            case 'xml':
                fwrite($file, '<Row>');
                foreach ($row as $field)
                {
                    $field = $this->_postProcessField($field);
                    $field = str_replace("\n", ' ', $field);
                    $field = str_replace("\r", ' ', $field);
                    $dataType = is_numeric($field) ? 'Number' : 'String';
                    
                    $xmlline = '<Cell><Data ss:Type="' . $dataType . '">' . (is_numeric($field) ? trim($field) : "<![CDATA[". $field . "]]>") . '</Data></Cell>';
                    
                    fwrite($file, $xmlline, mb_strlen($xmlline));
                }
                fwrite($file, '</Row>');
            break;
        }
    }
    
    protected function _processHandlers(&$row)
    {
        if (is_array($this->_handlers) && !empty($this->_handlers))
        {
            foreach ($this->_handlers as $field => $handler)
            {
                if (isset($row[$field]))
                {
                    $row[$field] = Mage::getModel('amorderexport/handler_' . $handler)->handle($row[$field]);
                }
            }
        }
    }
    
    protected function _getQueryExport($ids = array())
    {
        $usedWhere = false;
        $sql       = '';
        
        /**
        * Getting tables and fields for select
        */
        
        $tables  = Mage::helper('amorderexport/fields')->getAllTables();
        $sorting = array();
        
        if ($this->getExportAllfields())
        {
            // building query to export all fields that are available
            $fields = Mage::helper('amorderexport/fields')->getFieldsForSelect();
        } else 
        {
            $fieldModel = Mage::getModel('amorderexport/profile_field');
            $fields = $fieldModel->getFieldsForSelect($this);
            $this->_handlers = $fieldModel->getHandlers();
            $sorting = $fieldModel->getFieldsSorting($this);
        }
        
        $fields['order']['id_track'] = 'entity_id';
        $fields['invoice']['id_invoice_track'] = 'entity_id';

        if (empty($fields))
        {
            throw new Exception('No fields specified.');
        }

        /**
        * Building select query
        */
        
        $usedTables = array();

        $fieldsToSelect = array();
        
        $sql .= 'SELECT ' ;
        
        foreach ($fields as $table => $tableFields)
        {
            if (array_key_exists($table, $tables))
            {
                $usedTables[] = $table;
                foreach ($tableFields as $alias => $field)
                {
                    if (is_numeric($alias))
                    {
                        $alias = $table . '_' . $field;
                    }
                    
                    $fieldsToSelect[$alias] = array(
                    								    'table'    => $table,
                    								    'field'    => $field,
                    								    'alias'    => $alias,
                                                    );                
                }
            }
        }

        $fieldsToSelectSorted = array();
        if (is_array($sorting) && !empty($sorting))
        {
            foreach ($sorting as $alias => $order)
            {
                $fieldsToSelectSorted[] = $fieldsToSelect[$alias];
            }
            foreach ($fieldsToSelect as $alias => $field)
            {
                if (!in_array($alias, array_keys($sorting)))
                {
                    $fieldsToSelectSorted[] = $field;
                }
            }
        } else 
        {
            $fieldsToSelectSorted = $fieldsToSelect;
        }
        unset($fieldsToSelect);

        foreach ($fieldsToSelectSorted as $field)
        {
            $sql .= '`' . $field['table'] . '`.`' . $field['field'] . '` AS `' . $field['alias'] . '`, ';
        }
        unset($fieldsToSelectSorted);
        
        if ($this->getFilterShipmentFrom() || $this->getFilterShipmentTo())
            $usedTables [] = "shipment";
        if ($this->getFilterInvoiceFrom() || $this->getFilterInvoiceTo())
            $usedTables [] = "invoice";
        $usedTables = array_unique($usedTables);

        // removing comma at the end
        $sql = substr($sql, 0, -2);
        
        foreach ($tables as $table => $tableData)
        {
            if (in_array($table, $usedTables))
            {
                if (!isset($tableData['join']))
                {
                    // the first table to select from will have no join
                    $sql .= ' FROM `' . $tableData['table'] . '` AS `' . $table . '` ';
                } else 
                {
                    // all other tables will be joined
                    $sql .= ' LEFT JOIN `' . $tableData['table'] . '` AS `' . $table . '` ON (' . $tableData['join'] . ') ';
                }
            }
        }
        
        if (count($ids) > 0){
            $sql .= ' WHERE `order`.`entity_id` IN (' . implode(",", $ids) . ') ';
            return $sql;
        }
        
        /**
        * Adding filters ===========================================================================================
        */
        
        if ($this->getFilterNumberFrom())
        {
            // increment_id is stored, but we will take entity_id for this increment_id and start searching from it
            $idFrom = Mage::getModel('sales/order')->load($this->getFilterNumberFrom(), 'increment_id')->getId();
            if (!$idFrom)
            {
                throw new Exception('Order number to start from not found.');
            }
            if ($idFrom)
            {
                if ($this->getFilterNumberFromSkip())
                {
                    $sql .= ' WHERE `order`.`entity_id` > "' . $idFrom . '" ';
                } else 
                {
                    $sql .= ' WHERE `order`.`entity_id` >= "' . $idFrom . '" ';
                }
                $usedWhere = true;
            }
        }
        if ($this->getFilterNumberTo())
        {
            $idTo = Mage::getModel('sales/order')->load($this->getFilterNumberTo(), 'increment_id')->getId();
            if (!$idTo)
            {
                throw new Exception('Order number to end with not found.');
            }
            if ($idTo)
            {
                $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . ' `order`.`entity_id` <= "' . $idTo . '" ';
                $usedWhere = true;
            }
        }

        /**
         * Adding shipment number filtering
         */
        if ($this->getFilterShipmentFrom())
        {
            $shipmentIdFrom = Mage::getModel('sales/order_shipment')->loadByIncrementId($this->getFilterShipmentFrom())->getId();
            if (!$shipmentIdFrom)
            {
                throw new Exception('Shipment number ' . $this->getFilterShipmentFrom() . ' not found.');
            }
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . ' `shipment`.`entity_id` >= "' . $shipmentIdFrom . '" ';
            $usedWhere = true;
        }

        if ($this->getFilterShipmentTo())
        {
            $shipmentIdTo = Mage::getModel('sales/order_shipment')->loadByIncrementId($this->getFilterShipmentTo())->getId();
            if (!$shipmentIdTo)
            {
                throw new Exception('Shipment number ' . $this->getFilterShipmentTo() . ' not found.');
            }
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . ' `shipment`.`entity_id` <= "' . $shipmentIdTo . '" ';
            $usedWhere = true;
        }

        /**
         * Adding invoice number filtering
         */
        
        if ($this->getFilterInvoiceFrom()){
            $idFrom = Mage::getModel('sales/order_invoice')->load($this->getFilterInvoiceFrom(), 'increment_id')->getId();
            if (!$idFrom)
            {
                throw new Exception('Invoice number to start from not found.');
            }
            if ($idFrom)
            {
                if ($this->getFilterInvoiceFromSkip())
                {
                    $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . ' `invoice`.`entity_id` > "' . $idFrom . '" ';
                } else 
                {
                    $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . ' `invoice`.`entity_id` >= "' . $idFrom . '" ';
                }
                $usedWhere = true;
            }
        }
        
        if ($this->getFilterInvoiceTo()){
            $invoiceIdTo = Mage::getModel('sales/order_invoice')->loadByIncrementId($this->getFilterInvoiceTo())->getId();
            if (!$invoiceIdTo)
            {
                throw new Exception('Invoice number ' . $this->getFilterInvoiceTo() . ' not found.');
            }
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . ' `invoice`.`entity_id` <= "' . $invoiceIdTo . '" ';
            $usedWhere = true;
        }

        if ($this->getFilterStatus())
        {
            $filterStatus = explode(",", $this->getFilterStatus());
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`status` IN ("' . implode('","', $filterStatus) . '") ' ;
            
            $usedWhere = true;
        }
        if ($this->getFilterDateFrom())
        {
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`created_at` >= "' . $this->getFilterDateFrom() . '" ' ;
            $usedWhere = true;
        }
        if ($this->getFilterDateTo())
        {
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`created_at` <= "' . $this->getFilterDateTo() . '" ' ;
            $usedWhere = true;
        }
        $storeIds = explode(',', $this->getStoreIds());
        if (is_array($storeIds) && !empty($storeIds) && !in_array(0, $storeIds))
        {
            // adding store filter
            if (1 == count($storeIds))
            {
                $storeIds = current($storeIds);
                $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`store_id` = "' . $storeIds . '" ' ;
                $usedWhere = true;
            } else 
            {
                $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`store_id` IN (' . implode(',', $storeIds) . ') ' ;
                $usedWhere = true;
            }
        }
        if ($this->getFilterCustomergroup())
        {
            $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`customer_group_id` = "' . intval($this->getFilterCustomergroup()) . '" ' ;
            $usedWhere = true;
        }
        
        // addinig filter_skip_zero_price
        if ($this->getFilterSkipZeroPrice())
        {
            if (in_array('product', $usedTables))
            {
                $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`product`.`price` != 0 ' ;
                $usedWhere = true;
            }
        }
        if ($this->getFilterSku())
        {
            $skuList = explode(',', $this->getFilterSku());
            if (is_array($skuList) && !empty($skuList))
            {
                // we should only apply filter by SKU to products if we have products table joined
                if ($this->getFilterSkuOnlylines() && isset($fields['product']))
                {
                    $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`product`.`sku` IN ( ';
                    foreach ($skuList as $sku)
                    {
                        $sql .= '"' . trim($sku) . '", ';
                    }
                    $sql = substr($sql, 0, -2);
                    $sql .= ' ) ' ;
                    $usedWhere = true;
                } else
                {
                    $subSelect = 'SELECT DISTINCT order_id FROM `' . Mage::getModel('core/resource')->getTableName('sales/order_item') . '` WHERE sku IN (';
                    foreach ($skuList as $sku)
                    {
                        $subSelect .= '"' . trim($sku) . '", ';
                    }
                    $subSelect = substr($subSelect, 0, -2);
                    $subSelect .= ');';
                    $connection = Mage::getSingleton('core/resource') ->getConnection('core_read');
                    $result     = $connection->raw_query($subSelect);
                    $inOrderIds = '';
                    while ($inOrderId = $result->fetch(PDO::FETCH_COLUMN))
                    {
                        $inOrderIds .= $inOrderId . ', ';
                    }
                    if ($inOrderIds)
                    {
                        $inOrderIds = substr($inOrderIds, 0, -2);
                        $sql .= ($usedWhere ? ' AND ' : ' WHERE ' ) . '`order`.`entity_id` IN ( ' . $inOrderIds . ' ) ' ;
                        $usedWhere = true;
                    }
                }
            }
        }
        /**
        * Finished adding filters =========================================================================================== ^^^
        */

        return $sql;
    }
    
    protected function _getFileExtension()
    {
        $ext = '';
        
        switch ($this->getFormat())
        {
            case 'csv':
                $ext = '.csv';
            break;
            case 'xml':
                $ext = '.xml';
            break;
        }
        
        return $ext;
    }
    
    protected function _postProcessField($field)
    {
    	if ($this->getPostDateFormat())
    	{
    		if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})?/', $field))
    		{
    			$field = date($this->getPostDateFormat(), strtotime($field));
    		}
    	}
    	return $field;
    }
}

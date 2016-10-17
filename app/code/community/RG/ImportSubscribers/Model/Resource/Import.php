<?php
/**
 * @author Ruslan Gumennyi <ruslan.gumennyi@gmail.com>
 * @package RG_ImportSubscribers
 *
 * This simple extension allows you to import your subscriber list
 * from previously exported file as well as from scratch.
 * Currently supported file formats:
 * txt (only list of emails, one per line),
 * csv ('Email' is required field, other can be omitted)
 * Excel XML (same as csv)
 *
 * Order of the columns can be anything, but it's important to pass correct headings,
 * for example: 'Email' (not 'email'), 'Store View', 'Status', etc.
 *
 * Valid column names (any other will be ignored):
 * Email - email addresses
 *
 * Type  - can be Customer or Guest
 *     IMPORTANT: If the Type specified as Customer, but the client
 * with such e-mail doesn't exist, it will be saved as Guest
 *
 * Status - One of Subscribed, Unsubscribed, Not Activated, Unconfirmed
 *
 * Store View - name of your site's store view in which you want
 *  insert subscribers list (must be created prior to proccess,
 *  otherwise default will be used )
 *
 * If count of rows in your file more than 50-60 thousands,
 * you'd better break it for several smaller parts.
 * On my testing environment it took me 94 sec (1 min 34 sec)
 * for importing 50000 rows with registered customers.
 *
 * IMPORTANT: It doesn't care of duplicates, so you should remove them manualy
 * prior to import
 *
 */

// sets no time limit, so we can import large amounts of data
set_time_limit(0);

class RG_ImportSubscribers_Model_Resource_Import extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * DB read connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_read;

    /**
     * DB write connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_write;

    /**
     * Errors in import process
     *
     * @var array
     */
    protected $_importErrors = array();

    /**
     * Count of imported rows
     *
     * @var int
     */
    protected $_importedRows = 0;

    /**
     * Default store ID
     *
     * @var int
     */
    protected $_defaultStoreId = null;

    /**
     * Default website ID
     *
     * @var int
     */
    protected $_defaultWebsiteId = null;

    /**
     * Default customer_id
     *
     * @var int
     */
    protected $_defaultCustomerId = 0;

    /**
     * change_status_at
     *
     * @var string
     */
    protected $_changeTime;

    protected function _construct ()
    {
        $this->_init('newsletter/subscriber', 'subscriber_id');
        $this->_read  = $this->_getReadAdapter();
        $this->_write = $this->_getWriteAdapter();
        $this->_changeTime = date('Y-m-d H:i:s');
    }

    /**
     * @param Varien_Object $object
     * @return Mage_Core_Model_Resource_Abstract
     */
    public function uploadAndImport (Varien_Object $object)
    {
        if (empty($_FILES['groups']['tmp_name']['import_subscribers']['fields']['import']['value'])) {
            return $this;
        }

        $file = $_FILES['groups']['tmp_name']['import_subscribers']['fields']['import']['value'];

        $fileName = $_FILES['groups']['name']['import_subscribers']['fields']['import']['value'];
        $fileExt  = substr($fileName, strrpos($fileName, '.') + 1);

        if ($object->getScope() == 'stores') {
        $this->_defaultStoreId = (int)$object->getScopeId();
        }

        switch ($fileExt) :
            case 'txt':
                $this->_parseTxt($file);
                break;
            case 'csv':
                $this->_parseCsv($file);
                break;
            case 'xml':
                $this->_parseXml($file);
                break;
            default:
                Mage::throwException( Mage::helper('importsubscribers')->__( 'Unknown file type. Please, make sure your file is one of supported file formats' ) );
        endswitch;

        return $this;
    }

    /**
     * Parses txt file
     * @param string $file
     * @param int $store store ID import to
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _parseTxt ($file)
    {
        $info = pathinfo($file);
        $io = new Varien_Io_File();
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');

        $this->_write->beginTransaction();

        try {
            $importData = array();
            while (false !== ($email = $io->streamRead() ) ) {
            $email = trim($email);
                if (empty($email)) {
                    continue;
                }
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $importData[] = array(  'store_id' => $this->_getDefaultStoreId(),
                                            'change_status_at' => $this->_changeTime,
                                            'subscriber_email' => $email,
                                            'subscriber_status' => $this->_getSubscriberStatus('Subscribed'),
                                            'subscriber_confirm_code' => Mage::helper('core')->uniqHash());
                } else {
                    $this->_importErrors[] = $email;
                }
                if (count($importData) > 5000) {
                    $this->_saveImportData($importData);
                    $importData = array();
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();
        } catch (Mage_Core_Exception $e) {
            $this->_write->rollBack();
            $io->streamClose();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $this->_write->rollback();
            $io->streamClose();
            Mage::logException($e);
            Mage::throwException(Mage::helper('adminhtml')->__($e->getMessage()));
        }
        $this->_write->commit();

        if ($this->_importErrors) {
            $error = sprintf( 'ImportSubscribers: These records cannot be imported: "%1$s"', implode('", "', $this->_importErrors));
            Mage::log($error, 3, '', true);
        }

        return $this;
    }

    /**
     * Parses csv file
     *
     * @param string $file
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _parseCsv ($file)
    {
        $info = pathinfo($file);
        $io = new Varien_Io_File();
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');

        $headers = $io->streamReadCsv();

        if ($headers === false) {
            $io->streamClose();
            Mage::throwException(
            Mage::helper('importsubscribers')->__('You must specify valid headers in the first row'));
        }
        if (false === ($columns = $this->_prepareColumns($headers))) {
            $io->streamClose();
            Mage::throwException(
            Mage::helper('importsubscribers')->__("Invalid header: 'Email' is missed"));
        }

        $this->_write->beginTransaction();

        try {
            $rowNumber = 1;
            $importData = array();

            while (false !== ($csvLine = $io->streamReadCsv())) {
                $rowNumber ++;

                // check for empty lines
                $emptyLine = array_unique($csvLine);
                if ( (count($emptyLine) == 1) && ($emptyLine[0] == "") ) {
                continue;
                }

                $row = $this->_getImportRow($csvLine, $columns, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }
                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = array();
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();
        } catch (Mage_Core_Exception $e) {
            $this->_write->rollback();
            $io->streamClose();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $this->_write->rollback();
            $io->streamClose();
            Mage::logException($e);
            Mage::throwException(Mage::helper('adminhtml')->__($e->getMessage()));
        }
        $this->_write->commit();

        if ($this->_importErrors) {
            $error = sprintf(
            'ImportSubscribers: "%1$s"',
            implode('", "', $this->_importErrors));
            Mage::log($error, 3, '', true);
        }

        return $this;
    }

    /**
     * Parses xml file
     *
     * @param string $file
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _parseXml ($file)
    {
        $headers = array();
        $xml = new SimpleXMLElement($file, null, true);
        $xmlHeaders = $xml->Worksheet->Table[0]->Row[0]->Cell;

        foreach ($xmlHeaders as $xmlHeader) {
            $headers[] = (string)$xmlHeader->Data;
        }

        if (count($headers) == 0) {
            Mage::throwException(
            Mage::helper('importsubscribers')->__('You must specify valid headers in the first row'));
        }
        if (false === ($columns = $this->_prepareColumns($headers))) {
            Mage::throwException(
            Mage::helper('importsubscribers')->__("Invalid headers: 'Email' is missed"));
        }

        $this->_write->beginTransaction();

        try {
            $importData = array();
            for ($rowNumber = 1; $rowNumber <= $xml->Worksheet->Table->Row->count(); ++$rowNumber) {
                $xmlLine = array();
                $rows = $xml->Worksheet->Table[0]->Row[$rowNumber]->Cell;

                foreach ($rows as $item) {
                    $xmlLine[] = (string) $item->Data;
                }

                // check for empty lines
                $emptyLine = array_unique($xmlLine);
                if ( (count($emptyLine) == 1) && ($emptyLine[0] == "") ) {
                    continue;
                }

                $row = $this->_getImportRow($xmlLine, $columns, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }
                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = array();
                }
            }
            $this->_saveImportData($importData);
        } catch (Mage_Core_Exception $e) {
            $this->_write->rollback();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $this->_write->rollback();
            Mage::logException($e);
            Mage::throwException(Mage::helper('adminhtml')->__($e->getMessage()));
        }
        $this->_write->commit();

        if ($this->_importErrors) {
            $error = sprintf( 'ImportSubscribers: "%1$s"', implode('", "', $this->_importErrors));
            Mage::log($error, 3, '', true);
        }

        return $this;
    }

    /**
     * Check and prepare headers for retrieving data from rows
     *
     * @param array $headers
     * @return array
     */
    protected function _prepareColumns (array $headers)
    {
        foreach ($headers as &$header) {
            $header = strtolower($header);
        }

        $headers = array_flip($headers);

        $columns = array();

        if (isset($headers['email'])) {
            $columns['subscriber_email'] = $headers['email'];
        } else {
            return false;
        }
        if (isset($headers['type'])) {
            $columns['type'] = $headers['type'];
        }
        if (isset($headers['customer first name'])) {
            $columns['customer_firstname'] = $headers['customer first name'];
        }
        if (isset($headers['customer last name'])) {
            $columns['customer_lastname'] = $headers['customer last name'];
        }
        if (isset($headers['status'])) {
            $columns['subscriber_status'] = $headers['status'];
        }
        if (isset($headers['store view'])) {
            $columns['store_id'] = $headers['store view'];
        }
        if (isset($headers['website'])) {
            $columns['website'] = $headers['website'];
        }

        return $columns;
    }

    /**
     * Check and prepare rows data for import
     *
     * @param array $row
     * @param array $columns
     * @param int $rowNumber
     * @return array
     */
    protected function _getImportRow (array $row, array $columns, $rowNumber = 0)
    {
        $data['subscriber_email'] = trim($row[$columns['subscriber_email']]);

        if (! filter_var($data['subscriber_email'], FILTER_VALIDATE_EMAIL)) {
            $this->_importErrors[] = sprintf('Data format error in the Row %s', $rowNumber);

            return false;
        }

        $data['subscriber_status'] = isset($columns['subscriber_status'])
                                        ? $this->_getSubscriberStatus( trim($row[$columns['subscriber_status']]))
                                        : $this->_getSubscriberStatus('Subscribed');

        $data['store_id'] = isset($columns['store_id']) ? $this->_getStoreId( trim($row[$columns['store_id']]) ) : $this->_getDefaultStoreId();

        $data['subscriber_confirm_code'] = Mage::helper('core')->uniqHash();

        if ( isset($columns['type']) && $row[$columns['type']] == 'Customer') {
            if ($customerId = $this->_getCustomerByEmail( $data['subscriber_email'] ) ) {
                $data['customer_id'] = $customerId;
            } elseif ( isset($columns['customer_firstname']) && isset($columns['customer_lastname'])) {
                $customerData = array(
                    'firstname' => trim( $row[$columns['customer_firstname']] ),
                    'lastname'  => trim( $row[$columns['customer_lastname']] ),
                    'email'     => $data['subscriber_email'],
                    'password'  => Mage::helper('core')->getRandomString(6),
                    'website_id' => isset($columns['website']) ? $this->_getWebsiteId( trim($row[$columns['website']])) : $this->_getDefaultWebsiteId(),
                );

                $data['customer_id'] = $this->_addNewCustomer($customerData);
            } else {
                $data['customer_id'] = $this->_defaultCustomerId;
            }
        } else {
            $data['customer_id'] = $this->_defaultCustomerId;
        }

        $data['change_status_at'] = $this->_changeTime;

        return $data;
    }

    /**
     * Retrieve store ID based on it's name
     *
     * @param string $name
     * @return int
     */
    protected function _getStoreId ($name)
    {
        $select = $this->_read->select();
        $select->from(Mage::getResourceModel('core/store')->getMainTable(), array('store_id'))
               ->where('name = :name');
        if (false != ($storeId = (int) $this->_read->fetchOne($select, array(':name' => (string)$name ) ))) {
            return $storeId;
        } else {
            return $this->_getDefaultStoreId();
        }
    }

    /**
     * Retrieve website ID based on it's name
     *
     * @param string $name
     * @return int
     */
    protected function _getWebsiteId($name)
    {
        $select = $this->_read->select();
        $select->from(Mage::getResourceModel('core/website')->getMainTable(), array('website_id'))
               ->where('name = :name');
        if (false != ($websiteId = (int) $this->_read->fetchOne($select, array(':name' => (string)$name ) ))) {
            return $websiteId;
        } else {
            return $this->_getDefaultWebsiteId();
        }
    }

    /**
     * Retrieve default store ID from default website
     *
     * @return int
     */
    protected function _getDefaultStoreId()
    {
        if (null == $this->_defaultStoreId) {
            $this->_defaultStoreId = (int)Mage::app()->getDefaultStoreView();
        }

        return $this->_defaultStoreId;
    }

    /**
     * Retrieve default store ID from default website
     *
     * @return int
     */
    protected function _getDefaultWebsiteId()
    {
        if (null == $this->_defaultWebsiteId) {
            $this->_defaultWebsiteId = (int)Mage::app()->getDefaultStoreView()->getWebsiteId();
        }

        return $this->_defaultWebsiteId;
    }

    /**
     * Return numeric subscribers status for import
     *
     * @param string $status
     * @return int
     */
    protected function _getSubscriberStatus ($status = 'Subscribed')
    {
        switch ($status) :
            case 'Not Activated':
                $subscriberStatus = Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE;
                break;
            case 'Unsubscribed':
                $subscriberStatus = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                break;
            case 'Unconfirmed':
                $subscriberStatus = Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED;
                break;
            case 'Subscribed':
            default:
                $subscriberStatus = Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
        endswitch;

        return $subscriberStatus;
    }

    /**
     * Retrieve customer ID based on email
     *
     * @param string $email customer email
     * @return int
     */
    protected function _getCustomerByEmail ($email)
    {
        $select = $this->_read->select();
        $select->from(Mage::getResourceModel('customer/customer')->getEntityTable(), array('entity_id'))
               ->where('email = :email');
        $customerId = (int) $this->_read->fetchOne($select, array('email' => $email));

        return $customerId;
    }

    /**
     * Create new customer with minimum attributes: First Name, Last Name, Email, Password, Store
     *
     * @param array $customerData
     * @return int customerId
     */

    protected function _addNewCustomer(array $customerData)
    {
        $customer = Mage::getModel('customer/customer');

        $customer->addData($customerData);

        try {
            $customer->save();
        } catch (Exception $e) {
            Mage::throwException( Mage::helper('importsubscribers')->__( 'Cannot create new customer %s', $customerData['email'] ) );
        }

        return $customer->getId();
    }

    /**
     * Save data to DB
     * @param array $data
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _saveImportData (array $data)
    {
        if (! empty($data)) {
            $this->_write->insertArray( $this->getMainTable(),
                                        array_keys($data[0]),
                                        $data );

            $this->_importedRows += count($data);
        }

        return $this;
    }
}
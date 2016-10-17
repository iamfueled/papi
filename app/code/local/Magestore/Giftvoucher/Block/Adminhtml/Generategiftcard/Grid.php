<?php

class Magestore_Giftvoucher_Block_Adminhtml_Generategiftcard_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('templateGrid');
        $this->setDefaultSort('template_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('giftvoucher/template')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('template_id', array(
            'header' => Mage::helper('giftvoucher')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'template_id'
        ));

        $this->addColumn('template_name', array(
            'header' => Mage::helper('giftvoucher')->__('Template Name'),
            'align' => 'left',
            'index' => 'template_name'
        ));
        
        $this->addColumn('pattern', array(
            'header' => Mage::helper('giftvoucher')->__('Pattern'),
            'align' => 'left',
            'index' => 'pattern'
        ));

        $this->addColumn('balance', array(
            'header' => Mage::helper('giftvoucher')->__('Balance'),
            'align' => 'left',
            'index' => 'balance',
            'type' => 'currency',
            'currency' => 'currency'
        ));

        $this->addColumn('currency', array(
            'header' => Mage::helper('giftvoucher')->__('Currency'),
            'align' => 'left',
            'index' => 'currency',
        ));

        $this->addColumn('expired_at', array(
            'header' => Mage::helper('giftvoucher')->__('Expired at'),
            'align' => 'left',
            'index' => 'expired_at',
            'type' => 'date'
        ));

        $this->addColumn('amount', array(
            'header' => Mage::helper('giftvoucher')->__('Amount'),
            'align' => 'left',
            'index' => 'amount',
            'type'  => 'number'
        ));

//        $this->addColumn('day_to_send', array(
//            'header' => Mage::helper('giftvoucher')->__('Day To Send'),
//            'align' => 'left',
//            'index' => 'day_to_send',
//            'type' => 'date'
//        ));


        $this->addColumn('store_id', array(
            'header' => Mage::helper('giftvoucher')->__('Store view'),
            'align' => 'left',
            'index' => 'store_id',
            'type' => 'store',
            'store_all' => true,
            'store_view' => true,
            'filter_index' => 'main_table.store_id',
            'filter_condition_callback' => array($this, 'filterByGiftvoucherStoreId')
        ));

//        $this->addColumn('conditions_serialized', array(
//            'header' => Mage::helper('giftvoucher')->__('Conditions Serialized'),
//            'align' => 'left',
//            'index' => 'conditions_serialized'
//        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('giftvoucher')->__('Action'),
            'width' => '70px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('giftvoucher')->__('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'id'
                )
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('giftvoucher')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('giftvoucher')->__('XML'));
        
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdField('template_id');
        $this->getMassactionBlock()->setFormFieldName('template');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('giftvoucher')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('giftvoucher')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function filterByGiftvoucherStoreId($collection, $column) {
        $value = $column->getFilter()->getValue();
        if (isset($value) && $value) {
            $collection->addFieldToFilter("main_table.store_id", $value);
        }
    }

}

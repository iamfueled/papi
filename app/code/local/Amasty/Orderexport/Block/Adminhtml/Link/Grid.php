<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Link_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('amorderexportLinkGrid');
        $this->setDefaultSort('table_name');
        $this->setDefaultDir('ASC');
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('amorderexport/link')->getCollection();
        /* @var $collection Amasty_Orderexport_Model_Mysql4_Link_Collection */
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('table_name', array(
            'header'    => Mage::helper('amorderexport')->__('Referenced Table'),
            'index'     => 'table_name',
        ));
        
        $this->addColumn('alias', array(
            'header'    => Mage::helper('amorderexport')->__('Alias'),
            'index'     => 'alias',
        ));
        
        $this->addColumn('base_key', array(
            'header'    => Mage::helper('amorderexport')->__('Base Table Key'),
            'index'     => 'base_key',
        ));
        
        $this->addColumn('referenced_key', array(
            'header'    => Mage::helper('amorderexport')->__('Referenced Table Key'),
            'index'     => 'referenced_key',
        ));
        
        $this->addColumn('comment', array(
            'header'    => Mage::helper('amorderexport')->__('Comments'),
            'index'     => 'comment',
        ));

        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('link_id' => $row->getId()));
    }
}
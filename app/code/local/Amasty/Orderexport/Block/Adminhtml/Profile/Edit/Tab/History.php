<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Block_Adminhtml_Profile_Edit_Tab_History extends Mage_Adminhtml_Block_Widget_Grid implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('history_grid');
        $this->setDefaultSort('run_at', 'desc');
        $this->setUseAjax(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('amorderexport/profile_history_collection')
                      ->addFieldToFilter('profile_id', Mage::registry('amorderexport_profile')->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
//        $this->addColumn('action_code', array(
//            'header'    => Mage::helper('adminhtml')->__('Profile Action'),
//            'index'     => 'action_code',
//            'filter'    => 'adminhtml/system_convert_profile_edit_filter_action',
//            'renderer'  => 'adminhtml/system_convert_profile_edit_renderer_action',
//        ));

        $this->addColumn('run_at', array(
            'header'    => Mage::helper('adminhtml')->__('Run At'),
            'type'      => 'datetime',
            'index'     => 'run_at',
            'width'     => '150px',
        ));
        
        $this->addColumn('file_path', array(
            'header'    => Mage::helper('adminhtml')->__('File'),
            'index'     => 'file_path',
            'renderer'  => 'amorderexport/adminhtml_profile_edit_renderer_path',
        ));

        $this->addColumn('file_size', array(
            'header'    => Mage::helper('adminhtml')->__('File Size'),
            'index'     => 'file_size',
            'renderer'  => 'amorderexport/adminhtml_profile_edit_renderer_filesize',
        ));
        
        $this->addColumn('run_time', array(
            'header'    => Mage::helper('adminhtml')->__('Time Of Execution'),
            'index'     => 'run_time',
            'renderer'  => 'amorderexport/adminhtml_profile_edit_renderer_runtime',
        ));
        
        $this->addColumn('run_records', array(
            'header'    => Mage::helper('adminhtml')->__('Total Records Exported'),
            'index'     => 'run_records',
        ));
        
        $this->addColumn('last_increment_id', array(
            'header'    => Mage::helper('adminhtml')->__('Last Order Exported'),
            'index'     => 'last_increment_id',
        ));
        
        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Download Direct'),
                        'url'     => array(
                            'base'      =>'*/*/download',
                            'params'    =>array()
                        ),
                        'field'   => 'id'
                    ),
                    array(
                        'caption' => Mage::helper('catalog')->__('Download Compressed (.zip)'),
                        'url'     => array(
                            'base'      =>'*/*/download',
                            'params'    =>array('zip'   => true)
                        ),
                        'field'   => 'id'
                    ),
                ),
                'filter'    => false,
                'sortable'  => false,
                'width'     => '190px',
        ));

        return parent::_prepareColumns();
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/history', array('_current' => true));
    }
    
    public function getTabLabel()
    {
        return Mage::helper('amorderexport')->__('Run History');
    }
    
    public function getTabTitle()
    {
        return Mage::helper('amorderexport')->__('Run History');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
}

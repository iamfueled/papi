<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Model_Fields_Static extends Amasty_Orderexport_Model_Fields_Abstract
{
    public function get()
    {
        $fields = array(
            'order'             => $this->_getColumns('order'),
            'invoice'           => $this->_getColumns('invoice'),
            'shipment'          => $this->_getColumns('shipment'),
            'creditmemo'        => $this->_getColumns('creditmemo'),
            'product'           => $this->_getColumns('product'),
            'shipping_address'  => $this->_getColumns('shipping_address'),
            'billing_address'   => $this->_getColumns('billing_address'),
        );
        
        return $fields;
    }
    
    public function getTables($table = '')
    {
        $tables = array(
            'order' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/order'),
            ),
            'invoice' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/invoice'),
                'join'  => '`order`.`entity_id` = `invoice`.`order_id`', // base_table_field => joined_field
            ),
            'shipment' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/shipment'),
                'join'  => '`order`.`entity_id` = `shipment`.`order_id`',
            ),
            'creditmemo' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/creditmemo'),
                'join'  => '`order`.`entity_id` = `creditmemo`.`order_id`',
            ),
            'product' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/order_item'),
                'join'  => '`order`.`entity_id` = `product`.`order_id`',
            ),
            'shipping_address' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/order_address'),
                'join'  => '`order`.`shipping_address_id` = `shipping_address`.`entity_id`',
            ),
            'billing_address' => array(
                'table' => Mage::getModel('core/resource')->getTableName('sales/order_address'),
                'join'  => '`order`.`billing_address_id` = `billing_address`.`entity_id`',
            ),
        );
        if ($table && isset($tables[$table]))
        {
            return $tables[$table];
        }
        return $tables;
    }
}

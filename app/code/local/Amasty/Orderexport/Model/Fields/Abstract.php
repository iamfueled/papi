<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
abstract class Amasty_Orderexport_Model_Fields_Abstract extends Mage_Core_Model_Abstract
{
    protected function _getColumns($table)
    {
        $connection = Mage::getSingleton('core/resource') ->getConnection('core_read');
        $tableData  = $this->getTables($table);
        $sql        = 'DESCRIBE `' . $tableData['table'] . '`';
        $tableInfo = $connection->fetchAssoc($sql);
        $columns = array();
        if (is_array($tableInfo) && !empty($tableInfo))
        {
            foreach ($tableInfo as $column)
            {
                $columns[] = $column['Field'];
            }
        }
        return $columns;
    }
}

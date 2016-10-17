<?php

/**
 * resource model Rule (fix to use for Magento 1.4)
 */
class Magestore_Giftvoucher_Model_Mysql4_Rule extends Mage_CatalogRule_Model_Mysql4_Rule
{
	public function getRulesFromProduct($date, $websiteId, $customerGroupId, $productId)
    {
        $adapter = $this->_getReadAdapter();
        $dateQuoted = $adapter->quote($this->formatDate($date, false));
        $joinCondsQuoted[] = 'main_table.rule_id = rp.rule_id';
        $joinCondsQuoted[] = $adapter->quoteInto('rp.website_id = ?', $websiteId);
        $joinCondsQuoted[] = $adapter->quoteInto('rp.customer_group_id = ?', $customerGroupId);
        $joinCondsQuoted[] = $adapter->quoteInto('rp.product_id = ?', $productId);
        $fromDate = $this->getIfNullSql('main_table.from_date', $dateQuoted);
        $toDate = $this->getIfNullSql('main_table.to_date', $dateQuoted);
        $select = $adapter->select()
            ->from(array('main_table' => $this->getTable('catalogrule/rule')))
            ->joinInner(
                array('rp' => $this->getTable('catalogrule/rule_product')),
                implode(' AND ', $joinCondsQuoted),
                array())
            ->where(new Zend_Db_Expr("{$dateQuoted} BETWEEN {$fromDate} AND {$toDate}"))
            ->where('main_table.is_active = ?', 1)
            ->order('main_table.sort_order');
        return $adapter->fetchAll($select);
    }
    
    public function getIfNullSql($expression, $value = 0) {
        if ($expression instanceof Zend_Db_Expr || $expression instanceof Zend_Db_Select) {
            $expression = sprintf("IFNULL((%s), %s)", $expression, $value);
        } else {
            $expression = sprintf("IFNULL(%s, %s)", $expression, $value);
        }
        return new Zend_Db_Expr($expression);
    }
}

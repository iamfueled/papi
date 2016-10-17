<?php

class ES_Newsletterpopup_Model_System_Config_Couponlist
{
    public function toOptionArray()
    {
        $rules = Mage::getResourceModel('salesrule/rule_collection')->load();
        $list = array(
            '' => Mage::helper('adminhtml')->__('Please choose rule')
        );
        if ($rules) {
            foreach ($rules as $rule) {
                if ($rule->getCouponType()==2)
                    $list[$rule->getId()] = $rule->getName();
            }
        }
        return $list;
    }
}
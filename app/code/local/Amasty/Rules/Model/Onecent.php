<?php
/**
 * @copyright   Copyright (c) 2009-15 Amasty
 */

class Amasty_Rules_Model_Onecent
{
    /**
     * @var array
     */
    protected $_discounts = array();

    /**
     * @var float
     */
    protected $_currentCentAmount = 0;

    /**
     * @var array
     */
    protected  $_baseDiscounts = array();

    /**
     * @var float
     */
    protected $_currentBaseCentAmount = 0;


    /**
     * Get cent fix from current discount
     */
    public function getCentFix($quote,$type = ""){

        if ($type=="base"){
            $discounts = "_discounts";
            $currentCentAmount = "_currentCentAmount";
        }else{
            $discounts = "_baseDiscounts";
            $currentCentAmount = "_currentBaseCentAmount";
        }

        $realDiscount = 0;
        $currentDiscount = 0;
        if (count($this->$discounts) > 0 ) {
            foreach ($this->$discounts as $discount) {
                $realDiscount += $discount['price'] * $discount['percent'];
                $currentDiscount += $quote->getStore()->roundPrice($discount['currentDiscount']);
            }
            $currentDiscount = $quote->getStore()->roundPrice($currentDiscount);
            $this->$discounts = array();
            if ($realDiscount > $currentDiscount) {
                $this->$currentCentAmount += $realDiscount - $currentDiscount;
            }

            if ($currentDiscount > $realDiscount) {
                $this->$currentCentAmount -= $currentDiscount - $realDiscount;
            }


            if (abs($this->$currentCentAmount) > 0.005) {
                $curCent = $this->$currentCentAmount;
                $this->$currentCentAmount = 0;
                return $curCent;
            }
        }
        return 0;
    }

    public function addDiscount($itemId,$price,$percent,$currentDiscount){
        if (isset($this->_discounts[$itemId])){
            $this->_discounts[$itemId]['price'] += $price;
            $this->_discounts[$itemId]['currentDiscount'] += $currentDiscount;
        }else{
            $this->_discounts[$itemId] = array('price'=>$price ,
                                        'percent'=>$percent,
                                        'currentDiscount'=>$currentDiscount,
            );
        }
    }

    public function addBaseDiscount($itemId,$price,$percent,$currentDiscount){
        if (isset($this->_baseDiscounts[$itemId])){
            $this->_baseDiscounts[$itemId]['price'] += $price;
            $this->_baseDiscounts[$itemId]['currentDiscount'] += $currentDiscount;
        }else{
            $this->_baseDiscounts[$itemId] = array('price'=>$price ,
                                               'percent'=>$percent,
                                               'currentDiscount'=>$currentDiscount,
            );
        }
    }

}
<?php

class Magestore_Giftvoucher_Model_Total_Order_Creditmemo_Giftvoucher extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
	public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo){
		$order = $creditmemo->getOrder();
		if ($order->getPayment()->getMethod() == 'giftvoucher') {
            return $this;
        }
        if (!$order->getGiftVoucherDiscount() && !$order->getUseGiftCreditAmount()) {
            return $this;
        }
        
        if ($this->isLast($creditmemo)) {
            $baseDiscount = $order->getBaseGiftVoucherDiscount();
            $discount = $order->getGiftVoucherDiscount();
            
            $creditBaseDiscount = $order->getBaseUseGiftCreditAmount();
            $creditDiscount = $order->getUseGiftCreditAmount();
            foreach ($order->getCreditmemosCollection() as $existedCreditmemo) {
                if ($baseDiscount > 0.0001) {
                    $baseDiscount -= $existedCreditmemo->getBaseGiftVoucherDiscount();
                    $discount -= $existedCreditmemo->getGiftVoucherDiscount();
                }
                if ($creditBaseDiscount > 0.0001) {
                    $creditBaseDiscount -= $existedCreditmemo->getBaseUseGiftCreditAmount();
                    $creditDiscount -= $existedCreditmemo->getUseGiftCreditAmount();
                }
            }
            if ($baseDiscount > 0.0001) {
                if ($creditmemo->getBaseGrandTotal() - $baseDiscount < 0) {
                    $creditmemo->setBaseGiftVoucherDiscount($creditmemo->getBaseGrandTotal());
                    $creditmemo->setGiftVoucherDiscount($creditmemo->getGrandTotal());
                    $creditmemo->setBaseGrandTotal(0);
                    $creditmemo->setGrandTotal(0);
                } else {
                    $creditmemo->setBaseGiftVoucherDiscount($baseDiscount);
                    $creditmemo->setGiftVoucherDiscount($discount);
                    $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()-$baseDiscount);
                    $creditmemo->setGrandTotal($creditmemo->getGrandTotal()-$discount);
                }
                $creditmemo->setAllowZeroGrandTotal(true);
            }
            if ($creditBaseDiscount > 0.0001) {
                if ($creditmemo->getBaseGrandTotal() - $creditBaseDiscount < 0){
                    $creditmemo->setBaseUseGiftCreditAmount($creditmemo->getBaseGrandTotal());
                    $creditmemo->setUseGiftCreditAmount($creditmemo->getGrandTotal());
                    $creditmemo->setBaseGrandTotal(0);
                    $creditmemo->setGrandTotal(0);
                } else {
                    $creditmemo->setBaseUseGiftCreditAmount($creditBaseDiscount);
                    $creditmemo->setUseGiftCreditAmount($creditDiscount);
                    $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()-$creditBaseDiscount);
                    $creditmemo->setGrandTotal($creditmemo->getGrandTotal()-$creditDiscount);
                }
                $creditmemo->setAllowZeroGrandTotal(true);
            }
        } else {
            $orderTotal = $order->getGrandTotal()
                + abs(floatval($order->getGiftVoucherDiscount()))
                + abs(floatval($order->getUseGiftCreditAmount()));
            $creditmemoTotal = $creditmemo->getGrandTotal();
            $ratio = $creditmemoTotal / $orderTotal;
            if ($order->getGiftVoucherDiscount()) {
                $baseDiscount = $order->getBaseGiftVoucherDiscount() * $ratio;
                $discount = $order->getGiftVoucherDiscount() * $ratio;
                $creditmemo->setGiftCodes($order->getGiftCodes());
                if ($creditmemo->getBaseGrandTotal() - $baseDiscount < 0) {
                    $creditmemo->setBaseGiftVoucherDiscount($creditmemo->getBaseGrandTotal());
                    $creditmemo->setGiftVoucherDiscount($creditmemo->getGrandTotal());
                    $creditmemo->setBaseGrandTotal(0);
                    $creditmemo->setGrandTotal(0);
                } else {
                    $creditmemo->setBaseGiftVoucherDiscount($baseDiscount);
                    $creditmemo->setGiftVoucherDiscount($discount);
                    $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()-$baseDiscount);
                    $creditmemo->setGrandTotal($creditmemo->getGrandTotal()-$discount);
                }
                $creditmemo->setAllowZeroGrandTotal(true);
            }
            if ($order->getUseGiftCreditAmount()) {
                $baseDiscount = $order->getBaseUseGiftCreditAmount() * $ratio;
                $discount = $order->getUseGiftCreditAmount() * $ratio;
                if ($creditmemo->getBaseGrandTotal() - $baseDiscount < 0){
                    $creditmemo->setBaseUseGiftCreditAmount($creditmemo->getBaseGrandTotal());
                    $creditmemo->setUseGiftCreditAmount($creditmemo->getGrandTotal());
                    $creditmemo->setBaseGrandTotal(0);
                    $creditmemo->setGrandTotal(0);
                } else {
                    $creditmemo->setBaseUseGiftCreditAmount($baseDiscount);
                    $creditmemo->setUseGiftCreditAmount($discount);
                    $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()-$baseDiscount);
                    $creditmemo->setGrandTotal($creditmemo->getGrandTotal()-$discount);
                }
                $creditmemo->setAllowZeroGrandTotal(true);
            }
        }
	}
    
    public function isLast($creditmemo) {
        foreach ($creditmemo->getAllItems() as $item) {
            if (!$item->isLast()) {
                return false;
            }
        }
        return true;
    }
}

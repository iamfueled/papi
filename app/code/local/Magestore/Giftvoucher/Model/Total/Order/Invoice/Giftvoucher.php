<?php

class Magestore_Giftvoucher_Model_Total_Order_Invoice_Giftvoucher extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
	public function collect(Mage_Sales_Model_Order_Invoice $invoice){
		$order = $invoice->getOrder();
		if ($order->getPayment()->getMethod() == 'giftvoucher') {
            return $this;
        }
        if (!$order->getGiftVoucherDiscount() && !$order->getUseGiftCreditAmount()) {
            return $this;
        }
        
        if ($invoice->isLast()) {
            $baseDiscount = $order->getBaseGiftVoucherDiscount();
            $discount = $order->getGiftVoucherDiscount();
            
            $creditBaseDiscount = $order->getBaseUseGiftCreditAmount();
            $creditDiscount = $order->getUseGiftCreditAmount();
            foreach ($order->getInvoiceCollection() as $existedInvoice) {
                if ($baseDiscount > 0.0001) {
                    $baseDiscount -= $existedInvoice->getBaseGiftVoucherDiscount();
                    $discount -= $existedInvoice->getGiftVoucherDiscount();
                }
                if ($creditBaseDiscount > 0.0001) {
                    $creditBaseDiscount -= $existedInvoice->getBaseUseGiftCreditAmount();
                    $creditDiscount -= $existedInvoice->getUseGiftCreditAmount();
                }
            }
            if ($baseDiscount > 0.0001) {
                if ($invoice->getBaseGrandTotal() - $baseDiscount < 0) {
                    $invoice->setBaseGiftVoucherDiscount($invoice->getBaseGrandTotal());
                    $invoice->setGiftVoucherDiscount($invoice->getGrandTotal());
                    $invoice->setBaseGrandTotal(0);
                    $invoice->setGrandTotal(0);
                } else {
                    $invoice->setBaseGiftVoucherDiscount($baseDiscount);
                    $invoice->setGiftVoucherDiscount($discount);
                    $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()-$baseDiscount);
                    $invoice->setGrandTotal($invoice->getGrandTotal()-$discount);
                }
            }
            if ($creditBaseDiscount > 0.0001) {
                if ($invoice->getBaseGrandTotal() - $creditBaseDiscount < 0){
                    $invoice->setBaseUseGiftCreditAmount($invoice->getBaseGrandTotal());
                    $invoice->setUseGiftCreditAmount($invoice->getGrandTotal());
                    $invoice->setBaseGrandTotal(0);
                    $invoice->setGrandTotal(0);
                } else {
                    $invoice->setBaseUseGiftCreditAmount($creditBaseDiscount);
                    $invoice->setUseGiftCreditAmount($creditDiscount);
                    $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()-$creditBaseDiscount);
                    $invoice->setGrandTotal($invoice->getGrandTotal()-$creditDiscount);
                }
            }
        } else {
            $orderTotal = $order->getGrandTotal()
                + abs(floatval($order->getGiftVoucherDiscount()))
                + abs(floatval($order->getUseGiftCreditAmount()));
            $invoiceTotal = $invoice->getGrandTotal();
            $ratio = $invoiceTotal / $orderTotal;
            if ($order->getGiftVoucherDiscount()) {
                $baseDiscount = $order->getBaseGiftVoucherDiscount() * $ratio;
                $discount = $order->getGiftVoucherDiscount() * $ratio;
                $invoice->setGiftCodes($order->getGiftCodes());
                if ($invoice->getBaseGrandTotal() - $baseDiscount < 0) {
                    $invoice->setBaseGiftVoucherDiscount($invoice->getBaseGrandTotal());
                    $invoice->setGiftVoucherDiscount($invoice->getGrandTotal());
                    $invoice->setBaseGrandTotal(0);
                    $invoice->setGrandTotal(0);
                } else {
                    $invoice->setBaseGiftVoucherDiscount($baseDiscount);
                    $invoice->setGiftVoucherDiscount($discount);
                    $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()-$baseDiscount);
                    $invoice->setGrandTotal($invoice->getGrandTotal()-$discount);
                }
            }
            if ($order->getUseGiftCreditAmount()) {
                $baseDiscount = $order->getBaseUseGiftCreditAmount() * $ratio;
                $discount = $order->getUseGiftCreditAmount() * $ratio;
                if ($invoice->getBaseGrandTotal() - $baseDiscount < 0){
                    $invoice->setBaseUseGiftCreditAmount($invoice->getBaseGrandTotal());
                    $invoice->setUseGiftCreditAmount($invoice->getGrandTotal());
                    $invoice->setBaseGrandTotal(0);
                    $invoice->setGrandTotal(0);
                } else {
                    $invoice->setBaseUseGiftCreditAmount($baseDiscount);
                    $invoice->setUseGiftCreditAmount($discount);
                    $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()-$baseDiscount);
                    $invoice->setGrandTotal($invoice->getGrandTotal()-$discount);
                }
            }
        }
	}
}
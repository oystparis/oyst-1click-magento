<?php

class Oyst_OneClick_Model_Observer
{
    public function handleOrderToCapture(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getDataObject();

        if ($order->getStatus() == Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_TO_CAPTURE) {
            Mage::getModel('oyst_oneclick/oystPaymentManagement')->handleMagentoOrdersToCapture([$order->getId()]);
        }
    }

    public function handleOrderRefund(Varien_Event_Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getDataObject();
        $order = $creditmemo->getOrder();

        if ($creditmemo->getGrandTotal() ==  $order->getGrandTotal()) {
            Mage::getModel('oyst_oneclick/oystPaymentManagement')->handleMagentoOrdersToRefund([$order->getId()], true);
        } else {
            $order->addStatusHistoryComment(
                __('Partial Refund %1 %2 should be handled from Oyst Back Office.', $order->getGrandTotal(), $order->getOrderCurrencyCode())
            );
        }
    }
}
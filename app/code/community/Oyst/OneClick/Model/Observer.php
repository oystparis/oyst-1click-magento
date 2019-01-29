<?php

class Oyst_OneClick_Model_Observer
{
    public function handleOrderToCapture(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getDataObject();

        if ($order->getStatus() == Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_TO_CAPTURE) {
            Mage::getModel('oyst_oneclick/oystPaymentManagement')->handleMagentoOrdersToCapture([$order->getId() => $order->getGrandTotal()]);
        }
    }
}
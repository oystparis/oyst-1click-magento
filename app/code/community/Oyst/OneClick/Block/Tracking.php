<?php

class Oyst_OneClick_Block_Tracking extends Mage_Core_Block_Template
{
    public function getCheckoutOnepageSuccessTrackingParameters()
    {
        return array(
            'version' => 1,
            'type' => 'track',
            'event' => 'Confirmation Displayed',
        );
    }

    public function getCheckoutOnepageSuccessTrackingExtraParameters()
    {
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        $extraParameters = array(
            'amount' => $order->getGrandTotal(),
            'paymentMethod' => $order->getPayment()->getMethod(),
            'currency' => $order->getOrderCurrencyCode(),
            'referrer' => urlencode(Mage::helper('core/url')->getCurrentUrl()),
            'merchantId' => Mage::getStoreConfig('oyst_oneclick/general/merchant_id'),
            'orderId' => $order->getIncrementId(),
            'userEmail' => $order->getCustomerEmail(),
        );

        if ($order->getCustomerId()) {
            $extraParameters['userId'] = $order->getCustomerId();
        }

        return $extraParameters;
    }

    public function isEnabled()
    {
        return Mage::getStoreConfig('oyst_oneclick/general/enabled');
    }
}

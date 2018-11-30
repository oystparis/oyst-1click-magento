<?php

class Oyst_OneClick_Block_Tracking extends Mage_Core_Block_Template
{
    public function getCheckoutOnepageSuccessTrackingParameters()
    {
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        return array(
            'amount' => $order->getGrandTotal(),
            'paymentMethod' => $order->getPayment()->getMethod(),
            'currency' => $order->getOrderCurrencyCode(),
            'referrer' => urlencode(Mage::helper('core/url')->getCurrentUrl()),
            'merchantId' => Mage::getStoreConfig('oyst_oneclick/general/merchant_id'),
        );
    }
    
    public function isEnabled()
    {
        return Mage::getStoreConfig('oyst_oneclick/general/enabled');
    }
}

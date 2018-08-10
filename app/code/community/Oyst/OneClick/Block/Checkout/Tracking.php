<?php
/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Checkout Tracking Block
 */
class Oyst_OneClick_Block_Checkout_Tracking extends Mage_Core_Block_Abstract
{
    protected $_order;

    protected function _toHtml()
    {
        if (!$this->getOrder()->getId()
         || strpos($this->getOrder()->getPayment()->getMethod(), 'oyst') !== false) {
            return '';
        }

        return '<img src="'.$this->getTrackerBaseUrl().'?'.$this->getExtraParameters().'"/>';
    }

    protected function getOrder()
    {
        if (!isset($this->_order)) {
            $this->_order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        }

        return $this->_order;
    }

    protected function getTrackerBaseUrl()
    {
        if (Mage::getStoreConfig('oyst/oneclick/mode') == 'prod') {
            return 'https://tkr.11rupt.io/';
        } else {
            return 'https://staging-tkr.11rupt.eu/';
        }
    }

    protected function getExtraParameters()
    {
        $extraParameters = array(
            'extra_parameters[amount]='.$this->getOrder()->getGrandTotal(),
            'extra_parameters[paymentMethod]='.$this->getOrder()->getPayment()->getMethod(),
            'extra_parameters[currency]='.$this->getOrder()->getOrderCurrencyCode(),
            'extra_parameters[referrer]='.urlencode(Mage::helper('core/url')->getCurrentUrl()),
        );

        return implode('&', $extraParameters);
    }
}
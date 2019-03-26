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
        if (!$this->getOrder()->getId()) {
            return '';
        }

        return '<img src="'.$this->getTrackerBaseUrl().'?'.$this->getParameters().'&'.$this->getExtraParameters().'"/>';
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
        $isCustomEnvProd = Mage::getStoreConfig('oyst/oneclick/mode') == 'custom';

        foreach(array('staging', 'sandbox', 'test') as $env) {
            if (strpos(Mage::getStoreConfig('oyst/oneclick/api_url'), $env) !== false) {
                $isCustomEnvProd = false;
                break;
            }
        }

        if (Mage::getStoreConfig('oyst/oneclick/mode') == 'prod' || $isCustomEnvProd) {
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
            'extra_parameters[userEmail]='.urlencode($this->getOrder()->getCustomerEmail()),
            'extra_parameters[orderId]='.$this->getOrder()->getIncrementId(),
        );

        if ($this->getOrder()->getCustomerId()) {
            $extraParameters['extra_parameters[userId]'] = $this->getOrder()->getCustomerId();
        }

        return implode('&', $extraParameters);
    }

    protected function getParameters()
    {
        $parameters = array(
            'version=1',
            'type=track',
            'event=Confirmation%20Displayed'
        );

        return implode('&', $parameters);
    }
}
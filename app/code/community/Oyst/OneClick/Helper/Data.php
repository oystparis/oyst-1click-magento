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
 * Data Helper
 */
class Oyst_OneClick_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MODULE_NAME = 'Oyst_OneClick';

    const RETURN_URL = 'oyst_oneclick/checkout_cart/return/';

    const REDIRECT_URL = 'oyst_oneclick/checkout_cart/redirect/';

    const SUCCESS_URL = 'checkout/onepage/success';

    const FAILURE_URL = 'checkout/onepage/failure';

    const XML_PATH_RESTRICT_ALLOW_IPS = 'restrict_allow_ips';
    
    const STATUS_OYST_PAYMENT_ACCEPTED = 'oyst_payment_accepted';
    
    const STATUS_OYST_PAYMENT_FRAUD = 'oyst_payment_fraud';

    /**
     * Get config from Magento
     *
     * @param string $code
     * @param int $storeId
     *
     * @return mixed
     */
    public function getConfig($code, $storeId = null)
    {
        return Mage::getStoreConfig("oyst/oneclick/$code", $storeId);
    }

    /**
     * Get the module version
     *
     * @return string Extension version
     */
    public function getModuleVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->{self::MODULE_NAME}->version;
    }

    /**
     * Get the SDK version
     *
     * @return string SDK version
     */
    public function getSdkVersion()
    {
        /** @var Oyst_OneClick_Model_ApiWrapper_Client $oystModel */
        $oystModel = Mage::getModel('oyst_oneclick/apiWrapper_client');

        return $oystModel->getSdkVersion();
    }

    /**
     * Global function for log if enabled
     *
     * @param string $message
     *
     * @return null
     */
    public function log($message)
    {
        if ($this->getConfig('log_enable')) {
            Mage::log($message, null, $this->getModuleName() . '.log', true);
        }
    }

    /**
     * Return the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Set initialization status config flag and refresh config cache.
     *
     * @param string $identifier Identifier for payment method
     * @param bool $isInitialized Flag for initialization
     */
    public function setIsInitialized($identifier, $isInitialized = true)
    {
        $isInitialized = (bool)$isInitialized ? '1' : '0';
        Mage::getModel('eav/entity_setup', 'core_setup')
            ->setConfigData('oyst/' . $identifier . '/is_initialized', $isInitialized);
        Mage::app()->getCacheInstance()->cleanType('config');
        Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => 'config'));
    }

    /**
     * Test Return the module name
     *
     * @return string
     */
    public function isApiKeyValid()
    {
        try {
            /** @var Oyst_OneClick_Model_ApiWrapper_Client $api */
            $api = Mage::getModel('oyst_oneclick/apiWrapper_client');
            $api->isApiKeyValid();
        } catch (Exception $e) {
            /** @var Oyst_OneClick_Helper_Data $oystHelper */
            $oystHelper = Mage::helper('oyst_oneclick');

            $url = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/oyst_oneclick');
            Mage::getSingleton('core/session')->addWarning(
                $oystHelper->__('Click <a href="%s">here</a> to setup your API key.', $url)
            );

            return false;
        }

        return true;
    }

    /**
     * Get OneClick javascript CDN URL
     *
     * @return string
     */
    public function getOneClickJs()
    {
        if ($this->getConfig('enable')) {
            $mode = $this->getConfig('mode');
            $oneclickjs = $this->getConfig('oneclickjs_' . $mode . '_url') . '1click/script/script.min.js';

            if (Oyst_OneClick_Model_System_Config_Source_Mode::CUSTOM === $mode) {
                $oneclickjs = $this->getConfig('oneclickjs_url');
            }

            return '<script src="' . $oneclickjs . '"></script>';
        }
    }

    /**
     * Test and set default value
     *
     * @param string $var
     * @param string $value
     */
    public function defaultValue(&$var, $value)
    {
        $var = !isset($var) ? $value : $var;
    }


    /**
     * Return a human readable amount
     *
     * @param $value
     *
     * @return float
     */
    public function getHumanAmount($value)
    {
        return (float) $value / 100;
    }

    /**
     * Determine if the payment method is oyst
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return boolean
     */
    public function isPaymentMethodOyst(Mage_Sales_Model_Order $order)
    {
        /** @var Oyst_OneClick_Model_Payment_Method_Freepay $paymentMethod */
        $freepayPaymentMethod = Mage::getModel('oyst_oneclick/payment_method_freepay');

        return false !== strpos($order->getPayment()->getMethod(), $freepayPaymentMethod->getCode());
    }

    /**
     * Checks for open refund transaction
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    public function getOpenRefundTransaction($payment)
    {
        /** @var Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection $refundTransactions */
        $refundTransactions = Mage::getModel('sales/order_payment_transaction')->getCollection();
        $transaction = $refundTransactions->addPaymentIdFilter($payment->getId())
            ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND)
            ->setOrderFilter($payment->getOrder())
            ->addFieldToFilter('is_closed', 0)
            ->getFirstItem();

        return $transaction;
    }

    /**
     * Generate unique identifier.
     *
     * Identifier is built as [custom string][current datetime][random part]
     *
     * @return string
     */
    public function generateId($string = null)
    {
        $randomPart = rand(10, 99);

        list($usec, $sec) = explode(' ', microtime());
        unset($sec);

        $microtime = explode('.', $usec);
        $datetime = new DateTime();
        $datetime = $datetime->format('YmdHis');

        return $string . $datetime . $microtime[1] . $randomPart;
    }

    /**
     * Get loading page url.
     *
     * @param int $cartId
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return Mage::getBaseUrl() . self::REDIRECT_URL;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function isIpAllowed($storeId = null)
    {
        $allow = true;

        $allowedIps = $this->getConfig(self::XML_PATH_RESTRICT_ALLOW_IPS, $storeId);
        $clientIps = Mage::app()->getRequest()->getClientIp();
        if (!empty($allowedIps) && !empty($clientIps)) {
            $allowedIps = preg_split('#\s*,\s*#', $allowedIps, null, PREG_SPLIT_NO_EMPTY);
            $clientIps = preg_split('#\s*,\s*#', $clientIps, null, PREG_SPLIT_NO_EMPTY);

            $allowedIp = array_intersect($allowedIps, $clientIps);

            if (empty($allowedIp)) {
                $allow = false;
            }
        }

        return $allow;
    }
    
    public function handleQuoteErrors(Mage_Sales_Model_Quote $quote)
    {
        if ($quote->getHasError()) {
            $errorMessages = array();
            foreach ($quote->getErrors() as $error) {
                $errorMessages[] = $error->getCode();
            }
            throw new Mage_Checkout_Exception(implode('\n', $errorMessages));
        }
        
        return $this;
    }
}

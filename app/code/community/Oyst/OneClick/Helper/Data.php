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

    const LOADING_URL = 'oyst_oneclick/checkout_cart/loading/';

    const ORDER_URL = 'oyst_oneclick/checkout_cart/order';

    const QUOTE_URL = 'oyst_oneclick/checkout_cart/quote/';

    const SUCCESS_URL = 'checkout/onepage/success';

    const XML_PATH_RESTRICT_ALLOW_IPS = 'restrict_allow_ips';

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
        /** @var Oyst_OneClick_Model_Api $oystModel */
        $oystModel = Mage::getModel('oyst_oneclick/api');

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
            /** @var Oyst_OneClick_Model_Api $api */
            $api = Mage::getModel('oyst_oneclick/api');
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
     * Get metadata string.
     *
     * @return null|string
     */
    public function getTrackingMeta()
    {
        return '<script src="https://trk.10ru.pt"></script>' . PHP_EOL
            . '<meta name="oyst_tracker_info_freepay_activated" content="'
            . (int)Mage::getStoreConfigFlag('payment/oyst_freepay/active') . '">' . PHP_EOL
            . '<meta name="oyst_tracker_info_freepay_apikey" content="'
            . (int)Mage::getStoreConfigFlag('payment/oyst_abstract/api_login') . '">' . PHP_EOL
            . '<meta name="oyst_tracker_environnement_freepay" content="'
            . Mage::getStoreConfig('payment/oyst_abstract/mode') . '">' . PHP_EOL
            . '<meta name="oyst_tracker_info_oneclick_activated" content="'
            . (int)Mage::getStoreConfigFlag('oyst/oneclick/enable') . '">' . PHP_EOL
            . '<meta name="oyst_tracker_info_oneclick_apikey" content="'
            . (int)Mage::getStoreConfigFlag('oyst/oneclick/api_login') . '">' . PHP_EOL
            . '<meta name="oyst_tracker_environnement_1click" content="'
            . Mage::getStoreConfig('oyst/oneclick/mode') . '">' . PHP_EOL;
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
    public function getRedirectUrl($cartId)
    {
        return Mage::getBaseUrl() . self::LOADING_URL . 'cart_id/' . $cartId;
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
}

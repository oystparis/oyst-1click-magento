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

use Oyst\Api\OystApiClientFactory;
use Oyst\Api\OystCatalogApi;
use Oyst\Classes\OystUserAgent;
use Oyst\Api\OystPaymentApi;
use Oyst\Classes\OystPrice;

/**
 * API Model
 */
class Oyst_OneClick_Model_Api extends Mage_Core_Model_Abstract
{
    /*
     * API length
     *
     * @var int
     */
    const API_KEY_LENGTH = 64;

    /**
     * API type of call
     *
     * @var string
     */
    const TYPE_PAYMENT = OystApiClientFactory::ENTITY_PAYMENT;

    /**
     * Hard coded currency
     */
    const CURRENCY = 'EUR';

    /**
     * Validate API key
     *
     * @return bool
     *
     * @internal param string $apiKey
     */
    public function isApiKeyValid()
    {
        if (self::API_KEY_LENGTH !== strlen(Mage::getStoreConfig('oyst/oneclick/api_login'))) {
            Mage::throwException('Oyst 1-Click API key is not valid.');
        }

        return true;
    }

    /**
     * Get all conf and make the API client based on $type
     *
     * @param string $type Client type (catalog, oneclick, order, payment)
     *
     * @return object $type Client from the $type
     */
    public function getClient($type)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->isApiKeyValid();

        $apiKey = Mage::getStoreConfig('oyst/oneclick/api_login');
        $userAgent = $this->_getUserAgent();
        $env = Mage::getStoreConfig('oyst/oneclick/mode');
        $url = $this->_getCustomApiUrl('oyst/oneclick/');

        /** @var $type $oystClient */
        if (isset($env) && isset($url)) {
            $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent, $env, $url);
        } elseif (isset($env)) {
            $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent, $env);
        } else {
            $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent);
        }

        $oystClient->setNotifyUrl(Mage::getStoreConfig('oyst/oneclick/notification_url'));

        return $oystClient;
    }

    /**
     * Magento user agent
     *
     * @return string
     */
    protected function _getUserAgent()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $userAgent = new OystUserAgent('Magento', $oystHelper->getModuleVersion(), Mage::getVersion(), 'php', phpversion());

        return $userAgent;
    }

    /**
     * Get custom api url from config
     *
     * @param string $path Config path
     *
     * @return string|null
     */
    protected function _getCustomApiUrl($path)
    {
        if (Oyst_OneClick_Model_System_Config_Source_Mode::CUSTOM === Mage::getStoreConfig($path . 'mode')) {
            return Mage::getStoreConfig($path . 'api_url');
        }

        return null;
    }

    /**
     * Check the content of API response
     *
     * @param mixed $response
     */
    public function validateResult($response)
    {
        /** @var OystCatalogApi $response */
        if (200 !== $response->getLastHttpCode()) {
            /** @var Oyst_OneClick_Helper_Data $oystHelper */
            $oystHelper = Mage::helper('oyst_oneclick');

            $oystHelper->log('LastHttpCode: ' . $response->getLastHttpCode());
            $oystHelper->log('LastError: ' . $response->getLastError());
            $oystHelper->log('NotifyUrl: ' . $response->getNotifyUrl());
            $oystHelper->log('Body: ' . $response->getBody());
            $oystHelper->log('Response: ' . $response->getResponse());

            throw Mage::exception('Oyst_OneClick', $response->getLastError(), $response->getLastHttpCode());
        }
    }

    /**
     * Get the SDK version
     *
     * @return string
     */
    public function getSdkVersion()
    {
        return (string)OystApiClientFactory::getVersion();
    }

    /**
     * Validade API key
     *
     * @param string $apiKey
     *
     * @return array
     */
    public function validateApikey($apiKey)
    {
        if (strlen($apiKey) === self::API_KEY_LENGTH) {
            return true;
        }

        return false;
    }

    /**
     * API call to Oyst Payment
     *
     * @param string $type
     * @param array $dataFormated
     *
     * @return OystPaymentAPI
     */
    public function sendPayment($type, $dataFormated)
    {
        $apiKey = Mage::getStoreConfig('payment/oyst_abstract/api_login');
        $userAgent = $this->_getUserAgent();
        $env = Mage::getStoreConfig('payment/oyst_abstract/mode');
        $url = $this->_getCustomApiUrl('payment/oyst_abstract/');

        /** @var OystPaymentAPI $oystClient */
        $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent, $env, $url);

        $oystClient->$type(
            $dataFormated['amount']['value'],
            $dataFormated['amount']['currency'],
            $dataFormated['order_id'],
            $dataFormated['urls'],
            false,
            $dataFormated['user']
        );

        return $oystClient;
    }

    /**
     * API call to Oyst Payment
     *
     * @param string $type
     * @param string $paymentId
     * @param int $amount
     *
     * @return OystPaymentApi
     *
     * @internal param array $dataFormated
     */
    public function sendCancelOrRefund($type, $paymentId, $amount = null)
    {
        $apiKey = Mage::getStoreConfig('payment/oyst_abstract/api_login');
        $userAgent = $this->_getUserAgent();
        $env = Mage::getStoreConfig('payment/oyst_abstract/mode');
        $url = $this->_getCustomApiUrl('payment/oyst_abstract/');

        /** @var OystPaymentApi $oystClient */
        if (isset($env) && isset($url)) {
            $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent, $env, $url);
        } elseif (isset($env)) {
            $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent, $env);
        } else {
            $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent);
        }

        if (!is_null($amount)) {
            $amount = new OystPrice($amount, self::CURRENCY);
        }

        $oystClient->cancelOrRefund($paymentId, $amount);

        if (200 !== $oystClient->getLastHttpCode()) {
            /** @var Oyst_OneClick_Helper_Data $oystHelper */
            $oystHelper = Mage::helper('oyst_oneclick');

            $oystHelper->log($oystClient->getLastHttpCode());
            $oystHelper->log($oystClient->getLastError());
            $oystHelper->log($oystClient->getNotifyUrl());
            $oystHelper->log($oystClient->getBody());
            $oystHelper->log($oystClient->getResponse());

            Mage::throwException($oystHelper->__('Bad FreePay API HttpCode. Check oyst.log.'));
        }

        return $oystClient;
    }
}

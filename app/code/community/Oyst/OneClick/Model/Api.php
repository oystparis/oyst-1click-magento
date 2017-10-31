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
     * Validate API key
     *
     * @return bool
     *
     * @internal param string $apiKey
     */
    public function isApiKeyValid()
    {
        if (self::API_KEY_LENGTH !== strlen($this->_getConfig('api_login'))) {
            Mage::throwException('Oyst 1-Click API key is not valid.');
        }

        return true;
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/oneclick/$code");
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

        $apiKey = $this->_getConfig('api_login');
        $userAgent = $this->_getUserAgent();
        $env = $this->_getConfig('mode');
        $url = $this->_getCustomApiUrl();

        /** @var $type $oystClient */
        $oystClient = OystApiClientFactory::getClient($type, $apiKey, $userAgent, $env, $url);
        $oystClient->setNotifyUrl($this->_getConfig('notification_url'));

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
     * @return string|null
     */
    protected function _getCustomApiUrl()
    {
        if (Oyst_OneClick_Model_System_Config_Source_Mode::CUSTOM === $this->_getConfig('mode')) {
            return $this->_getConfig('api_url');
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
}

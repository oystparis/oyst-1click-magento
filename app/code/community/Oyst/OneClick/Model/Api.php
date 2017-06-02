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

/**
 * API Model
 */
class Oyst_OneClick_Model_Api
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

        return sprintf(
            'Magento v%s - %s v%s',
            Mage::getVersion(),
            $oystHelper->getModuleName(),
            $oystHelper->getModuleVersion()
        );
    }

    /**
     * Get custom api url from config
     *
     * @return string|null
     */
    protected function _getCustomApiUrl()
    {
        if (Oyst_OneClick_Model_Source_Mode::CUSTOM === $this->_getConfig('api_url')) {
            return $this->_getConfig('api_url');
        }

        return null;
    }

    /**
     * Check the content of API response
     *
     * @param mixed $result
     */
    public function validateResult($result)
    {
        /** @var OystCatalogApi $result */
        if (200 !== $result->getLastHttpCode()) {
            /** @var Oyst_OneClick_Helper_Data $oystHelper */
            $oystHelper = Mage::helper('oyst_oneclick');

            $oystHelper->log('LastHttpCode: ' . $result->getLastHttpCode());
            $oystHelper->log('LastError: ' . $result->getLastError());
            $oystHelper->log('NotifyUrl: ' . $result->getNotifyUrl());
            $oystHelper->log('Body: ' . $result->getBody());
            $oystHelper->log('Response: ' . $result->getResponse());

            throw Mage::exception('Oyst_OneClick', $result->getLastError(), $result->getLastHttpCode());
        }
    }
}

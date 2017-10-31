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
use Oyst\Api\OystOrderApi;

/**
 * Order ApiWrapper Model
 */
class Oyst_OneClick_Model_Order_ApiWrapper extends Mage_Core_Model_Abstract
{
    /** @var Oyst_OneClick_Model_Api $_oystClient */
    protected $_oystClient;

    /** @var OystOrderApi $_orderApi */
    protected $_orderApi;

    protected $_type = OystApiClientFactory::ENTITY_ORDER;

    public function __construct()
    {
        $this->_oystClient = Mage::getModel('oyst_oneclick/api');
        $this->_orderApi = $this->_oystClient->getClient($this->_type);
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
     * Retrieve order from Oyst
     *
     * @param Int $oystOrderId
     *
     * @return array
     */
    public function getOrder($oystOrderId)
    {
        try {
            $response = $this->_orderApi->getOrder($oystOrderId);
            $this->_oystClient->validateResult($this->_orderApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }

    /**
     * Update Order Status to Oyst
     *
     * @param Int $oystOrderId
     * @param string $status
     *
     * @return array
     */
    public function updateOrder($oystOrderId, $status)
    {
        try {
            $response = $this->_orderApi->updateStatus($oystOrderId, $status);
            $this->_oystClient->validateResult($this->_orderApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }
}

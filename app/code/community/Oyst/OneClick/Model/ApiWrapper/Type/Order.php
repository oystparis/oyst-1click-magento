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
use Oyst\Classes\OystPrice;

/**
 * ApiWrapper_Type_Order Model
 */
class Oyst_OneClick_Model_ApiWrapper_Type_Order extends Oyst_OneClick_Model_ApiWrapper_AbstractType
{
    /** @var OystOrderApi $_orderApi */
    protected $_orderApi;

    protected $_type = OystApiClientFactory::ENTITY_ORDER;

    public function __construct()
    {
        parent::__construct();
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
     * @param null|string
     *
     * @return array
     */
    public function updateOrder($oystOrderId, $status, $incrementId = null)
    {
        try {
            $response = $this->_orderApi->updateStatus($oystOrderId, $status, $incrementId);
            $this->_oystClient->validateResult($this->_orderApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }

    /**
     * Refund an order.
     *
     * @param string $orderId
     * @param int $price
     * @param string $currency
     *
     * @return mixed
     */
    public function refund($orderId, $price, $currency = 'EUR')
    {
        $price = new OystPrice($price, $currency);
        $response = $this->_orderApi->refunds($orderId, $price);
        $this->_oystClient->validateResult($this->_orderApi);

        return $response;
    }
}

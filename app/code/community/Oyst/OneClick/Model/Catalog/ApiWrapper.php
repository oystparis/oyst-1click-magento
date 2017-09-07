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
use Oyst\Classes\OystCarrier;
use Oyst\Classes\OneClickShipment;
use Oyst\Classes\ShipmentAmount;

/**
 * Catalog ApiWrapper Model
 */
class Oyst_OneClick_Model_Catalog_ApiWrapper extends Mage_Core_Model_Abstract
{
    /** @var Oyst_OneClick_Model_Api $_oystClient */
    protected $_oystClient;

    /** @var OystCatalogApi $_catalogApi */
    protected $_catalogApi;

    protected $_type = OystApiClientFactory::ENTITY_CATALOG;

    public function __construct()
    {
        $this->_oystClient = Mage::getModel('oyst_oneclick/api');
        $this->_catalogApi = $this->_oystClient->getClient($this->_type);
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
     * Send products
     *
     * @param $dataFormated
     *
     * @return mixed
     */
    public function postProducts($dataFormated)
    {
        try {
            $response = $this->_catalogApi->postProducts($dataFormated);
            $this->_oystClient->validateResult($this->_catalogApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }

    /**
     * Notify Oyst to export catalog of products
     *
     * @param $dataFormated
     *
     * @return mixed
     */
    public function notifyImport()
    {
        try {
            $response = $this->_catalogApi->notifyImport();
            $this->_oystClient->validateResult($this->_catalogApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }


    /**
     * Send shipments
     *
     * @return mixed
     */
    public function postShipments()
    {
        $shipmentsConfig = json_decode($this->_getConfig('shipments_config'), true);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oneClickShipments = array();
        foreach ($shipmentsConfig['shipments'] as $shipmentConfig) {
            $oneClickShipment = new OneClickShipment();

            $oystHelper->defaultValue($shipmentConfig['amount']['follower'], null);
            $oystHelper->defaultValue($shipmentConfig['amount']['leader'], null);
            $oystHelper->defaultValue($shipmentConfig['amount']['currency'], null);
            $oneClickShipment->setAmount(
                new ShipmentAmount(
                    $shipmentConfig['amount']['follower'],
                    $shipmentConfig['amount']['leader'],
                    $shipmentConfig['amount']['currency']
                )
            );

            $oystHelper->defaultValue($shipmentConfig['carrier']['id'], null);
            $oystHelper->defaultValue($shipmentConfig['carrier']['name'], null);
            $oystHelper->defaultValue($shipmentConfig['carrier']['type'], null);
            $oneClickShipment->setCarrier(
                new OystCarrier(
                    $shipmentConfig['carrier']['id'],
                    $shipmentConfig['carrier']['name'],
                    $shipmentConfig['carrier']['type']
                )
            );

            $oneClickShipment->setDelay($shipmentConfig['delay']);
            $oneClickShipment->setFreeShipping($shipmentConfig['free_shipping']);
            $oneClickShipment->setPrimary($shipmentConfig['primary']);
            $oneClickShipment->setZones($shipmentConfig['zones']);

            $oneClickShipments[] = $oneClickShipment;
        }

        try {
            $response = $this->_catalogApi->postShipments($oneClickShipments);
            $this->_oystClient->validateResult($this->_catalogApi);

            return $this->_catalogApi;
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->_catalogApi;
    }
}

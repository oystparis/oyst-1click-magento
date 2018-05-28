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

    /** @var array */
    protected $_shipmentTypes;

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
     * Get the list of shipment types (eg. home_delivery, ...)
     *
     * @return array
     */
    public function getShipmentTypes()
    {
        try {
            if (!isset($this->_shipmentTypes)) {
                $this->_shipmentTypes = $this->_catalogApi->getShipmentTypes();
                $this->_oystClient->validateResult($this->_catalogApi);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->_shipmentTypes;
    }
}

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
 * ApiWrapper_Type_Catalog Model
 */
class Oyst_OneClick_Model_ApiWrapper_Type_Catalog extends Oyst_OneClick_Model_ApiWrapper_AbstractType
{
    /** @var OystCatalogApi $_catalogApi */
    protected $_catalogApi;

    protected $_type = OystApiClientFactory::ENTITY_CATALOG;

    /** @var array */
    protected $_shipmentTypes;

    public function __construct()
    {
        parent::__construct();
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
        if (!isset($this->_shipmentTypes)) {
            $this->_shipmentTypes = $this->_catalogApi->getShipmentTypes();
            $this->_oystClient->validateResult($this->_catalogApi);
        }

        return $this->_shipmentTypes;
    }
}

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
class Oyst_OneClick_Model_Catalog_ApiWrapper extends Mage_Core_Model_Abstract
{
    protected $_type = OystApiClientFactory::ENTITY_CATALOG;

    /**
     * API send
     *
     * @param $dataFormated
     *
     * @return mixed
     */
    public function sendProduct($dataFormated)
    {
        /** @var Oyst_OneClick_Model_Api $oystClient */
        $oystClient = Mage::getModel('oyst_oneclick/api');

        /** @var OystCatalogApi $catalogApi */
        $catalogApi = $oystClient->getClient($this->_type);

        $result = $catalogApi->postProducts($dataFormated);

        try {
            $oystClient->validateResult($catalogApi);
        } catch (Exception $e) {
            return $result;
        }

        return $result;
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
        /** @var Oyst_OneClick_Model_Api $oystClient */
        $oystClient = Mage::getModel('oyst_oneclick/api');

        /** @var OystCatalogApi $catalogApi */
        $catalogApi = $oystClient->getClient($this->_type);

        $result = $catalogApi->notifyImport();

        try {
            $oystClient->validateResult($catalogApi);
        } catch (Exception $e) {
            return $result;
        }

        return $result;
    }
}

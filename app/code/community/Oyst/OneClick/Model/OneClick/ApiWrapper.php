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
use Oyst\Api\OystOneClickApi;
use Oyst\Classes\OneClickNotifications;
use Oyst\Classes\OneClickOrderParams;

/**
 * OneClick ApiWrapper Model
 */
class Oyst_OneClick_Model_OneClick_ApiWrapper extends Oyst_OneClick_Model_Api
{
    /** @var Oyst_OneClick_Model_Api $_oystClient */
    protected $_oystClient;

    /** @var OystOneClickApi $_oneClickApi */
    protected $_oneClickApi;

    protected $_type = OystApiClientFactory::ENTITY_ONECLICK;

    public function __construct()
    {
        $this->_oystClient = Mage::getModel('oyst_oneclick/api');
        $this->_oneClickApi = $this->_oystClient->getClient($this->_type);
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
     * API send
     *
     * @param $dataFormated
     *
     * @return mixed
     */
    public function authorizeOrder($dataFormated)
    {
        /** @var Oyst_OneClick_Model_Catalog $oystCatalog */
        $oystCatalog = Mage::getModel('oyst_oneclick/catalog');
        $oystProduct = $oystCatalog->getOystProduct($dataFormated['productRef'], $dataFormated['variationRef']);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->defaultValue($dataFormated['productRef'], null);
        $oystHelper->defaultValue($dataFormated['quantity'], 1);
        $oystHelper->defaultValue($dataFormated['variationRef'], null);
        $oystHelper->defaultValue($dataFormated['user'], null);
        $oystHelper->defaultValue($dataFormated['version'], 1);

        Mage::helper('oyst_oneclick')->log('$dataFormated');
        Mage::helper('oyst_oneclick')->log($dataFormated);

        $orderParams = new OneClickOrderParams();
        $orderParams->setManageQuantity($this->_getConfig('allow_quantity_change'));

        $context = array(
            'id' => (string)$this->generateId(),
            'remote_addr' => Mage::helper('core/http')->getRemoteAddr(),
            'store_id' => (string)Mage::app()->getStore()->getStoreId(),
        );

        if (!is_null($userId = Mage::getSingleton('customer/session')->getCustomerId())) {
            $context['user_id'] = $userId;
        }

        $notifications = new OneClickNotifications();
        $notifications->setShouldAskShipments(true);
        $notifications->setShouldAskStock($this->_getConfig('should_ask_stock'));
        $notifications->setUrl($this->_getConfig('notification_url'));

        try {
            $response = $this->_oneClickApi->authorizeOrder(
                $dataFormated['productRef'],
                $dataFormated['quantity'],
                $dataFormated['variationRef'],
                $dataFormated['user'],
                $dataFormated['version'],
                $oystProduct,
                $orderParams,
                $context,
                $notifications
            );
            $this->_oystClient->validateResult($this->_oneClickApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
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

        $microtime = explode('.', $usec);
        $datetime = new DateTime();
        $datetime = $datetime->format('YmdHis');

        return $string . $datetime . $microtime[1] . $randomPart;
    }
}

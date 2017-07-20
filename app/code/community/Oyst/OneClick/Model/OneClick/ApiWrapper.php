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

/**
 * OneClick ApiWrapper Model
 */
class Oyst_OneClick_Model_OneClick_ApiWrapper extends Mage_Core_Model_Abstract
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
     * API send
     *
     * @param $dataFormated
     *
     * @return mixed
     */
    public function send($dataFormated)
    {
        /** @var Oyst_OneClick_Model_Api $oystClient */
        extract($dataFormated);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->defaultValue($productRef, null);
        $oystHelper->defaultValue($quantity, 1);
        $oystHelper->defaultValue($variationRef, null);
        $oystHelper->defaultValue($user, null);
        $oystHelper->defaultValue($version, 1);

        try {
            $response = $this->_oneClickApi->authorizeOrder($productRef, $quantity, $variationRef, $user, $version);
            $this->_oystClient->validateResult($this->_oneClickApi);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }
}

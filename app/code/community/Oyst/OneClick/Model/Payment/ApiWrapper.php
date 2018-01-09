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
use Oyst\Classes\OystPrice;
use Oyst\Api\OystOneClickApi;

/**
 * Payment_ApiWrapper Model
 */
class Oyst_OneClick_Model_Payment_ApiWrapper extends Mage_Core_Model_Abstract
{
    /**
     * Hard coded currency
     */
    const CURRENCY = 'EUR';

    const TYPE = OystApiClientFactory::ENTITY_PAYMENT;

    /** @var Oyst_OneClick_Model_Api $_oystClient */
    protected $_oystClient;

    /** @var OystOneClickApi $_oneClickApi */
    protected $_oneClickApi;

    public function __construct()
    {
        $this->_oystClient = Mage::getModel('oyst_oneclick/api');
        $this->_oneClickApi = $this->_oystClient->getClient(self::TYPE);
    }

    /**
     * Make API call for retrieve Oyst url
     *
     * @param array $params
     *
     * @return string
     */
    public function getPaymentUrl($params)
    {
        $this->_oneClickApi->payment(
            $params['amount']['value'],
            $params['amount']['currency'],
            $params['order_id'],
            $params['urls'],
            false,
            $params['user']
        );
        $response = $this->_oneClickApi->getResponse();

        return $response['url'];
    }

    /**
     * @param string $lastTransId
     * @param int $amount
     * @return
     */
    public function cancelOrRefund($lastTransId, $amount = null)
    {
        if (!(null === $amount)) {
            $amount = new OystPrice($amount, self::CURRENCY);
        }

        $this->_oneClickApi->cancelOrRefund($lastTransId, $amount);

        $this->_oystClient->validateResult($this->_oneClickApi);

        return $this->_oneClickApi->getResponse();
    }
}

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
 * ApiWrapper_Type_Payment Model
 */
class Oyst_OneClick_Model_ApiWrapper_Type_Payment extends Oyst_OneClick_Model_ApiWrapper_AbstractType
{
    /**
     * Hard coded currency
     */
    const CURRENCY = 'EUR';

    const TYPE = OystApiClientFactory::ENTITY_PAYMENT;

    /** @var OystOneClickApi $_oneClickApi */
    protected $_oneClickApi;

    public function __construct()
    {
        parent::__construct();
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

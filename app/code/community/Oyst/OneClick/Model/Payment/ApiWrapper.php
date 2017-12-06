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

/**
 * Payment_ApiWrapper Model
 */
class Oyst_OneClick_Model_Payment_ApiWrapper extends Mage_Core_Model_Abstract
{
    /**
     * Make API call for retrieve Oyst url
     *
     * @param array $params
     *
     * @return string
     */
    public function getPaymentUrl($params)
    {
        /** @var Oyst_OneClick_Model_Api $paymentApi */
        $paymentApi = Mage::getModel('oyst_oneclick/api');
        $response = $paymentApi->sendPayment(Oyst_OneClick_Model_Api::TYPE_PAYMENT, $params)->getResponse();

        return $response['url'];
    }

    /**
     * @param string $lastTransId
     * @param int $amount
     * @return
     */
    public function cancelOrRefund($lastTransId, $amount = null)
    {
        /** @var Oyst_OneClick_Model_Api $paymentApi */
        $paymentApi = Mage::getModel('oyst_oneclick/api');

        $response = $paymentApi->sendCancelOrRefund(Oyst_OneClick_Model_Api::TYPE_PAYMENT, $lastTransId, $amount)->getResponse();

        return $response;
    }
}

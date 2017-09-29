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
 * Payment Controller
 */
class Oyst_OneClick_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Return the OneClick payment Url
     *
     * @return
     */
    public function urlAction()
    {
        $rawData = Mage::app()->getRequest()->getPost();

        $rawData['version'] = Mage::getStoreConfig('oyst/oneclick/order_api_version');

        /** @var Oyst_OneClick_Model_OneClick_ApiWrapper $oneclickApi */
        $oneclickApi = Mage::getModel('oyst_oneclick/oneClick_apiWrapper');

        $jsonData = $oneclickApi->send($rawData);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($jsonData));
    }
}
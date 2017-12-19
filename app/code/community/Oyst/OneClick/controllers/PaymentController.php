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
        /** @var Zend_Controller_Request_Http $rawData */
        $rawData = Mage::app()->getRequest()->getPost();

        /** @var Oyst_OneClick_Model_OneClick_ApiWrapper $oneclickApi */
        $oneclickApi = Mage::getModel('oyst_oneclick/oneClick_apiWrapper');
        $response = $oneclickApi->authorizeOrder($rawData);

        $this->getResponse()->setHttpResponseCode(200);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($response));
    }
}

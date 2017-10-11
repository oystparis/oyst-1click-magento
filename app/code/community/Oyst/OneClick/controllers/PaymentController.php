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

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->load($rawData['productRef']);

        // Test if variationRef is set for configurable
        if ($product->isConfigurable() && is_null($rawData['variationRef'])) {
            throw Mage::exception(
                'Oyst_OneClick',
                Mage::helper('oyst_onelick')->__(
                    sprintf("variationRef is null with for configurable product id %s", $rawData['productRef'])
                )
            );
        }

        $rawData['version'] = Mage::getStoreConfig('oyst/oneclick/order_api_version');

        /** @var Oyst_OneClick_Model_OneClick_ApiWrapper $oneclickApi */
        $oneclickApi = Mage::getModel('oyst_oneclick/oneClick_apiWrapper');

        $jsonData = $oneclickApi->send($rawData);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($jsonData));
    }
}

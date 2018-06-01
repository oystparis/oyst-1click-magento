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
 * Cart Controller
 */
class Oyst_OneClick_Checkout_CartController extends Mage_Core_Controller_Front_Action
{
    /* @var mixed $data */
    private $data = null;

    /**
     * Loading page action
     */
    public function redirectAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Login customer and redirect to success page
     */
    public function returnAction()
    {
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getOystRelatedQuoteId(), 'quote_id');

        if($order->getId()) {
            $websiteId = Mage::app()->getWebsite()->getId();
            $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)
                ->loadByEmail($order->getCustomerEmail());

            $session = Mage::getSingleton("customer/session");
            $session->loginById($customer->getId());
            $session->setCustomerAsLoggedIn($customer);

            $session = Mage::getSingleton('checkout/type_onepage')->getCheckout();
            $session->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId());

            $successUrl = Mage::getStoreConfig('oyst/oneclick/checkout_cart_cta_success_page');

            if (!$successUrl) {
                $successUrl = Mage::getBaseUrl() . Oyst_OneClick_Helper_Data::SUCCESS_URL;
            }

            $this->data = $successUrl;
        }

        $this->sendResponse();
    }

    /**
     * Send response to loading page.
     */
    private function sendResponse()
    {
        $this->getResponse()->setHttpResponseCode(200);
        if ('cgi-fcgi' === php_sapi_name()) {
            $this->getResponse()->setHeader('Content-type', 'application/json');
        }
        $this->getResponse()->setBody(Zend_Json::encode($this->data));
    }

    public function initOystCheckoutAction()
    {
        $params = $this->getRequest()->getParams();
        $params['quoteId'] = Mage::getSingleton('checkout/session')->getQuoteId();
        if (!$params['quoteId']) {
            Mage::getSingleton('checkout/cart')->save();
            $params['quoteId'] = Mage::getSingleton('checkout/session')->getQuoteId();
        }
        $params['add_to_cart_form'] = isset($params['add_to_cart_form']) ? Zend_Json::decode($params['add_to_cart_form']) : null;

        try {
            $oystCart = Mage::getModel('oyst_oneclick/cart');
            $this->data = $oystCart->initOystCheckout($params);
            Mage::getSingleton('checkout/session')->setOystRelatedQuoteId($params['quoteId']);
        } catch (Exception $e) {
            Mage::helper('oyst_oneclick')->log($e->__toString());
            $this->data = array('has_error' => 1, 'message' => $e->getMessage());
        }

        $this->sendResponse();
    }
}

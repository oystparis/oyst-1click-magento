<?php

class Oyst_OneClick_Api_OystCheckoutController extends Oyst_OneClick_Controller_Api_AbstractController
{
    public function getOystCheckoutFromMagentoQuoteAction()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');

        try {
            $result = Mage::getModel('oyst_oneclick/oystCheckoutManagement')->getOystCheckoutFromMagentoQuote($quoteId);

            $this->getResponse()->setBody(json_encode($result));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function syncMagentoQuoteWithOystCheckoutAction()
    {
        $oystCheckout = json_decode($this->getRequest()->getParam('oyst_checkout'), true);

        if ($oystCheckout === null) {
            // TODO
        }

        try {
            $result = Mage::getModel('oyst_oneclick/oystCheckoutManagement')->syncMagentoQuoteWithOystCheckout(null, $oystCheckout['oystCheckout']);

            $this->getResponse()->setBody(json_encode($result));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}

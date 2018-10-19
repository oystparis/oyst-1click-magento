<?php

class Oyst_OneClick_Api_OystCheckoutController extends Oyst_OneClick_Controller_Api_AbstractController
{
    public function getOystCheckoutFromMagentoQuoteAction()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');

        $result = Mage::getModel('oyst_oneclick/oystCheckoutManagement')->getOystCheckoutFromMagentoQuote($quoteId);

        $this->getResponse()->setBody(json_encode($result));
    }

    public function syncMagentoQuoteWithOystCheckoutAction()
    {
        $oystCheckout = json_decode($this->getRequest()->getParam('oyst_checkout'), true);

        if ($oystCheckout === null) {
            // TODO
        }

        $result = Mage::getModel('oyst_oneclick/oystCheckoutManagement')->syncMagentoQuoteWithOystCheckout(null, $oystCheckout['oystCheckout']);

        $this->getResponse()->setBody(json_encode($result));
    }
}


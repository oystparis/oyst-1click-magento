<?php

require_once 'Mage/Checkout/controllers/CartController.php';
class Oyst_OneClick_CheckoutController extends Mage_Checkout_CartController
{
    public function addToCartAction()
    {
        parent::addAction();

        if (!$this->getRequest()->isAjax()) {
            return $this;
        }

        $this->getCartAction();
    }

    public function getCartAction()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        Mage::getSingleton('checkout/session')->setOystOneClickQuoteId($quote->getId());

        $this->getResponse()->setHttpResponseCode(200);
        $this->getResponse()->clearHeader('Location');
        $this->getResponse()->setBody(
            json_encode(array('cart_id' => $quote->getId()))
        );
    }

    public function redirectAction()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quoteId = $checkoutSession->getOystOneClickQuoteId(true);
        $quote = Mage::getModel('sales/quote')->loadActive($quoteId);
        $order = Mage::getModel('oyst_oneclick/oystOrderManagement')->getMagentoOrderByQuoteId($quote->getId());

        $checkoutSession->setLastQuoteId($quoteId);
        $checkoutSession->setLastSuccessQuoteId($quoteId);
        $checkoutSession->setLastOrderId($order->getId());
        $checkoutSession->setLastRealOrderId($order->getIncrementId());
        $checkoutSession->setLastOrderStatus($order->getStatus());

        $this->handleDeactivateQuote($quote);
        $this->handleCustomerRedirectFromOrder($order);
        $this->handleSendNewOrderEmail($order);

        $this->_redirect('checkout/onepage/success');
        return $this;
    }

    protected function handleCustomerRedirectFromOrder($order)
    {
        try {
            if ($order->getCustomerId()) {
                if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                    Mage::getSingleton('customer/session')->loginById($order->getCustomerId());
                }
            } else {
                if (Mage::getStoreConfig(Oyst_OneClick_Helper_Constants::CONFIG_PATH_OYST_CONFIG_CREATE_CUSTOMER_ON_OYST_ORDER)) {
                    $customer = Mage::getModel('oyst_oneclick/oystCustomerManagement')
                        ->createMagentoCustomerFromOrder($order);
                    Mage::getSingleton('customer/session')->loginById($customer->getId());
                }
            }
        } catch (\Exception $e) {
            // Handle non blocking behaviour
            Mage::log($e->__toString(), null, 'error_oyst.log', true);
        }
    }

    protected function handleDeactivateQuote($quote)
    {
        $quote->setIsActive(false);
        $quote->save();

        return $this;
    }

    protected function handleSendNewOrderEmail($order)
    {
        try {
            $order->sendNewOrderEmail();
        } catch (\Exception $e) {
            // Handle non blocking behaviour
            Mage::log($e->__toString(), null, 'error_oyst.log', true);
        }

        return $this;
    }
}
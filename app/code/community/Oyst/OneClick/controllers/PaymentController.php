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
     * @deprecated since version 1.11.0
     * @return
     */
    public function urlAction()
    {
        $this->_forward('initOystCheckout', 'checkout_cart');
    }

    /**
     * Cancel the order in case of payment error or
     * when a customer cancel payment from oyst.
     *
     * @return null
     */
    public function cancelAction()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $checkoutSession = Mage::getSingleton('checkout/session');
        if (! $checkoutSession->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');

            return;
        }

        $lastQuoteId = $checkoutSession->getLastQuoteId();
        $lastOrderId = $checkoutSession->getLastOrderId();
        if ($lastOrderId) {
            Mage::getSingleton('core/session')->addError(
                $oystHelper->__("Order %s cancelled", $lastOrderId)
            );
            $orderModel = Mage::getModel('sales/order')->load($lastOrderId);

            if ($lastQuoteId && $orderModel->canCancel()) {
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                $quote->setIsActive(true)->save();

                $orderModel->cancel();
                $orderModel->setStatus('canceled');
                $orderModel->save();

                $this->_redirect('checkout/cart', array('_secure' => true));

                return;
            }
        }

        $oystHelper->log('Order Cancel Error');
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * Redirect customer to success if payment return is success
     *
     * @return null
     */
    public function returnAction()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->log('Redirecting to success page');

        $orderIncrementId = Mage::app()->getRequest()->getParam('order_increment_id');
        $order = Mage::getModel('sales/order')->load($orderIncrementId);

        $this->_redirect(
            'checkout/onepage/success',
            array(
                '_secure' => true,
                '_store' => $order->getStore()->getId(),
            )
        );
    }

    /**
     * Cancel the order in case of payment error
     *
     * @return null
     */
    public function errorAction()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $checkoutSession = Mage::getSingleton('checkout/session');
        if (! $checkoutSession->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');

            return null;
        }

        $lastQuoteId = $checkoutSession->getLastQuoteId();
        $lastOrderId = $checkoutSession->getLastOrderId();
        if ($lastOrderId) {
            Mage::getSingleton('core/session')->addError(
                $oystHelper->__("An error occured with order %s", $lastOrderId)
            );
            $orderModel = Mage::getModel('sales/order')->load($lastOrderId);

            if ($lastQuoteId && $orderModel->canCancel()) {
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                $quote->setIsActive(true)->save();

                $orderModel->cancel();
                $orderModel->setStatus('canceled');
                $orderModel->save();

                $this->_redirect('checkout/cart', array('_secure' => true));

                return null;
            }
        }

        $oystHelper->__('Payment Error');
        $this->_redirect('checkout/cart', array('_secure' => true));

        return null;
    }
}

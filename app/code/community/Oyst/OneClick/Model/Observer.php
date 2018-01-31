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
 * Observer Model
 */
class Oyst_OneClick_Model_Observer
{
    /**
     * Change order status after partial refund.
     *
     * @param Varien_Event_Observer $observer
     */
    public function setStatusAfterRefund(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();

        $status = Mage::getStoreConfig('payment/oyst_freepay/refund_partial_authorized');
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status);
    }

    /**
     * Update order status on cancel event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_OneClick_Model_Observer
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getPayment()->getOrder();

        if (!$oystHelper->isPaymentMethodOyst($order)) {
            return;
        }

        $orderStatusOnCancel = Mage::getStoreConfig('payment/oyst_freepay/payment_cancelled');

        if (Mage_Sales_Model_Order::STATE_HOLDED === $orderStatusOnCancel) {
            if ($order->canHold()) {
                $order
                    ->hold()
                    ->addStatusHistoryComment(
                        $oystHelper->__(
                            'Cancel Order asked. Waiting for FreePay notification. Payment Id: "%s".',
                            $order->getTxnId()
                        )
                    )
                    ->save();
            }
        }

        if (Mage_Sales_Model_Order::STATE_CANCELED === $orderStatusOnCancel) {
            if ($order->canCancel()) {
                $order
                    ->cancel()
                    ->addStatusHistoryComment(
                        $oystHelper->__('Success Cancel Order. Payment Id: "%s".', $order->getTxnId())
                    )
                    ->save();

                return $this;
            }
        }

        $oystHelper->log('Start send cancelOrRefund of order id : ' . $order->getId());

        /** @var Oyst_OneClick_Model_Payment_ApiWrapper $response */
        $response = Mage::getModel('oyst_oneclick/payment_apiWrapper');
        $response->cancelOrRefund($order->getPayment()->getLastTransId());

        $oystHelper->log('Waiting from cancelOrRefund notification of order id : ' . $order->getId());

        return $this;
    }

    /**
     * Invalidate config cache.
     *
     * @param Varien_Event_Observer $observer
     */
    public function invalidateCache(Varien_Event_Observer $observer)
    {
        Mage::app()->getCacheInstance()->invalidateType('config');
    }
}
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
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getConfig($section, $code)
    {
        return Mage::getStoreConfig("oyst/" . $section . "_settings/" . $code);
    }

    /**
     * Update order status event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_OneClick_Model_Observer
     */
    public function sendOrderStatusUpdate(Varien_Event_Observer $observer)
    {
        // if it's not while order import
        if (!$this->_getConfig("order", "enable") || Mage::registry('order_status_changing')) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        $state = $order->getState();
        if ($order->getOrigData('state') != $state) {
            switch ($state) {
                case Mage_Sales_Model_Order::STATE_NEW:
                case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
                case Mage_Sales_Model_Order::STATE_HOLDED:
                case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
                    $oystStatus = 'pending';
                    break;
                case Mage_Sales_Model_Order::STATE_PROCESSING:
                case Mage_Sales_Model_Order::STATE_COMPLETE:
                    $oystStatus = 'accepted';
                    break;
                case Mage_Sales_Model_Order::STATE_CANCELED:
                    $oystStatus = 'denied';
                    break;
                case Mage_Sales_Model_Order::STATE_CLOSED:
                    $oystStatus = 'refunded';
                    break;
                default:
                    $oystStatus = '';
            }
        }

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->log('Start of update of order id : ' . $order->getId());

        // sent status update
        /** @var Oyst_OneClick_Helper_Order_Data $orderData */
        $orderData = Mage::helper('oyst_oneclick/order_data');
        $orderData->updateStatus(
            array(
                'oyst_order_id' => $order->getOystOrderId(),
                'status' => $oystStatus,
            )
        );
        $oystHelper->log('End of update of order id : ' . $order->getId());

        return $this;
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
     * Update order status on refund event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_OneClick_Model_Observer
     */
    public function salesOrderPaymentRefund(Varien_Event_Observer $observer)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getPayment();

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        if (!$oystHelper->isPaymentMethodOyst($order)) {
            return;
        }

        if (!$payment->canRefund()) {
            Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
        }

        $oystHelper->log('Start send cancelOrRefund from refund of order id : ' . $order->getId());

        /** @var Oyst_OneClick_Model_Payment_ApiWrapper $response */
        $response = Mage::getModel('oyst_oneclick/payment_apiWrapper');
        $amount = $observer->getPayment()->getAmountRefunded();
        $response->cancelOrRefund($order->getPayment()->getLastTransId(), $amount);

        if ($response === false) {
            $errorCode = 'Invalid Data';
            $errorMsg = $this->_getHelper()->__('Error Processing the request');
            Mage::throwException($errorMsg);
        }

        if ($order->canHold()) {
            $order
                ->hold()
                ->addStatusHistoryComment(
                    $oystHelper->__(
                        'Refund Order asked. Waiting for FreePay notification. Payment Id: "%s".',
                        $order->getTxnId()
                    )
                )
                ->save();
        }

        $oystHelper->log('Waiting from cancelOrRefund notification of order id : ' . $order->getId());

        return $this;
    }

    public function validateApiKey(Varien_Event_Observer $observer)
    {
        $config = $observer->getEvent()->getObject();
        if ($config->getSection() != "oyst") {
            return $this;
        }

        $groups = $config->getGroups();
        $globalSettings = $groups["global_settings"];

        if ($globalSettings["fields"]["enable"]["value"] == 1 &&
            !empty($globalSettings["fields"]["api_login"]["value"])
        ) {
            $apiKey = $globalSettings["fields"]["api_login"]["value"];

            $apiResponse = Mage::getModel('oyst_oneclick/api')->validateApikeyFromApi($apiKey);
            if ($apiResponse != "true") {
                /** @var Oyst_OneClick_Helper_Data $oystHelper */
                $oystHelper = Mage::helper('oyst_oneclick');
                Mage::throwException($oystHelper->__("API key %s is not valid", $apiKey));
            }
        }

        return $this;
    }
}

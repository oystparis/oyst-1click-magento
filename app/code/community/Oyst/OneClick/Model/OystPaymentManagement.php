<?php

class Oyst_OneClick_Model_OystPaymentManagement extends Oyst_OneClick_Model_AbstractOystManagement
{
    /**
     * TODO : Check if order has already been captured
     * @param array $orderIds
     * @param bool $skipInvoiceCreation
     * @return $this
     */
    public function handleMagentoOrdersToCapture(array $orderIds, $skipInvoiceCreation = false)
    {
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $orderIds))
            ->addFieldToFilter('status', Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_TO_CAPTURE);

        if (count($orders) == 0) {
            return $this;
        }

        $oystOrderIds = [];
        foreach ($orders as $order) {
            $oystOrderIds[] = $order->getOystId();
        }

        $gatewayCallbackClient = new Oyst_OneClick_Gateway_CallbackClient();
        $gatewayResult = json_decode($gatewayCallbackClient->callGatewayCallbackApi(
            Oyst_OneClick_Helper_Constants::OYST_GATEWAY_ENDPOINT_TYPE_CAPTURE, $oystOrderIds
        ), true);

        if ($skipInvoiceCreation) {
            return $this;
        }

        foreach ($gatewayResult['orders'] as $gatewayOrder) {
            $order = $orders->getItemByColumnValue(
                'increment_id', $gatewayOrder['internal_id']
            );

            $this->handleMagentoOrderPaymentCaptured($order, $gatewayOrder);
            $order->save();
        }

        return $this;
    }

    /**
     * TODO : Full Refund is handled but allow partial refunds
     * @param array $orderIds
     * @param bool $skipCreditmemoCreation
     * @return $this
     */
    public function handleMagentoOrdersToRefund($orderIds, $skipCreditmemoCreation = false)
    {
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $orderIds));

        if (count($orders) == 0) {
            return $this;
        }

        $oystOrderIds = [];
        foreach ($orders as $order) {
            $oystOrderIds[] = $order->getOystId();
        }

        $gatewayCallbackClient = new Oyst_OneClick_Gateway_CallbackClient();
        $gatewayResult = json_decode($gatewayCallbackClient->callGatewayCallbackApi(
            Oyst_OneClick_Helper_Constants::OYST_GATEWAY_ENDPOINT_TYPE_REFUND, $oystOrderIds
        ), true);

        if ($skipCreditmemoCreation) {
            return $this;
        }

        foreach ($gatewayResult['orders'] as $gatewayOrder) {
            $order = $orders->getItemByColumnValue(
                'increment_id', $gatewayOrder['internal_id']
            );

            $service = Mage::getModel('sales/service_order', $order);
            $creditmemo = $service->prepareCreditmemo(array());
            $creditmemo->setOfflineRequested(true);
            $creditmemo->register();
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($creditmemo)
                ->addObject($creditmemo->getOrder());
            $transactionSave->save();
        }

        return $this;
    }

    public function handleMagentoOrderPaymentCaptured(Mage_Sales_Model_Order $order, array $oystOrder)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($oystOrder['payment']['last_transaction']['id']);
        $payment->setCurrencyCode($oystOrder['payment']['last_transaction']['currency']);
        $payment->setPreparedMessage(
            Mage::helper('oyst_oneclick')->__('Oyst OneClick Payment Captured : ')
        );
        $payment->setShouldCloseParentTransaction(true);
        $payment->setIsTransactionClosed(1);

        if (Mage::getStoreConfig('oyst_oneclick/general/enable_invoice_auto_generation')) {
            $payment->registerCaptureNotification($helper->getHumanAmount($this->orderResponse['order']['order_amount']['value']));
        }

        if ($order->getState() != Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING, Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_CAPTURED
            );
        }
    }
}
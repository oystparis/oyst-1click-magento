<?php

class Oyst_OneClick_Model_OystOrderManagement extends Oyst_OneClick_Model_AbstractOystManagement
{
    public function createOrderFromOystOrder(array $oystOrder)
    {
        $quote = $this->getMagentoQuoteByOystId($oystOrder['oyst_id']);

        if (!$quote->getId()) {
            throw new \Exception('Quote is not available.');
        }

        $newsletter = isset($oystOrder['user']['newsletter']) ? $oystOrder['user']['newsletter'] : false;
        Mage::helper('oyst_oneclick')->addQuoteExtraData(
            $quote, 'newsletter_optin', $newsletter
        );

        $quote->collectTotals();

        /** @var Mage_Sales_Model_Service_Quote $service */
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $service->getQuote()->setIsActive(true);

        return $this->getOystOrderFromMagentoOrder($oystOrder['oyst_id']);
    }

    public function getOystOrderFromMagentoOrder($oystId)
    {
        $order = $this->getMagentoOrderByOystId($oystId);

        if (!$order->getId()
         || $order->getPayment()->getMethod() != Mage::getModel('oyst_oneclick/payment_method_oneClick')->getCode()) {
            throw new \Exception('Order is not available.');
        }

        return Mage::getModel('oyst_oneclick/oystOrder_builder')->buildOystOrder($order);
    }

    public function syncMagentoOrderWithOystOrderStatus($oystId, array $oystOrder)
    {
        $order = $this->getMagentoOrderByOystId($oystId);

        if ($oystOrder['status']['code'] == Oyst_OneClick_Helper_Constants::OYST_API_ORDER_STATUS_CANCELED) {
            if ($order->canCancel()) {
                $order->cancel();
                $order->save();
            } else {
                // TODO
            }
        } elseif ($oystOrder['status']['code'] == Oyst_OneClick_Helper_Constants::OYST_API_ORDER_STATUS_PAYMENT_CAPTURED) {
            Mage::getModel('oyst_oneclick/oystPaymentManagement')->handleMagentoOrderPaymentCaptured($order, $oystOrder);
            $order->save();
            // TODO send invoice email
        } elseif ($oystOrder['status']['code'] == Oyst_OneClick_Helper_Constants::OYST_API_ORDER_STATUS_PAYMENT_WAITING_TO_CAPTURE) {
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING, Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_WAITING_TO_CAPTURE
            );

            $order->save();
        } else {
            throw new \Exception('Non handled status : '.$oystOrder['status']);
        }

        Mage::dispatchEvent(
            'oyst_oneclick_model_oyst_order_management_sync_magento_order_with_oyst_order_status_after',
            array('order' => $order, 'oyst_order' => $oystOrder)
        );

        return Mage::getModel('oyst_oneclick/oystOrder_builder')->buildOystOrder($order);
    }
}
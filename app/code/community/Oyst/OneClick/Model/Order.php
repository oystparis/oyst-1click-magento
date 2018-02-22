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

use Oyst\Classes\Enum\AbstractOrderState as OystOrderStatus;

/**
 * Order Model
 */
class Oyst_OneClick_Model_Order extends Mage_Core_Model_Abstract
{
    /** @var string Payment method name */
    protected $paymentMethod = null;

    /** @var string API event notification */
    private $eventNotification = null;

    /** @var string[] API order response */
    private $orderResponse = null;

    public function __construct()
    {
        $this->paymentMethod = Mage::getModel('oyst_oneclick/payment_method_oneclick')->getName();
    }

    /**
     * Order process from notification controller
     *
     * @param array $event
     * @param array $apiData
     *
     * @return string
     */
    public function processNotification($event, $apiData)
    {
        $oystOrderId = $apiData['order_id'];

        $this->eventNotification = $event;

        // Get last notification
        /** @var Oyst_OneClick_Model_Notification $lastNotification */
        $lastNotification = Mage::getModel('oyst_oneclick/notification');
        $lastNotification = $lastNotification->getLastNotification('order', $oystOrderId);

        // If last notification is not finished
        if ($lastNotification->getId() && 'finished' !== $lastNotification->getStatus()) {
            Mage::throwException(Mage::helper('oyst_oneclick')->__(
                'Last Notification with order id "%s" is not finished.',
                $oystOrderId)
            );
        }

        // Create new notification in db with status 'start'
        $notification = Mage::getModel('oyst_oneclick/notification');
        $notification->setData(
            array(
                'event' => $this->eventNotification,
                'oyst_data' => Zend_Json::encode($apiData),
                'status' => 'start',
                'created_at' => Mage::getModel('core/date')->gmtDate(),
                'executed_at' => Mage::getModel('core/date')->gmtDate(),
            )
        );
        $notification->save();

        // When notification already processed
        if (!is_null($magentoOrderId = $lastNotification->isOrderProcessed($oystOrderId))) {
            $response = Zend_Json::encode(array(
                'magento_order_id' => $magentoOrderId,
                'message' => 'notification has been already processed.',
            ));
        } else {
            // Sync Order From Api
            $result = $this->sync(array(
                'oyst_order_id' => $oystOrderId,
            ));
            $magentoOrderId = $result['magento_order_id'];

            $response = Zend_Json::encode(array(
                'magento_order_id' => $result['magento_order_id'],
            ));
        }

        // Save new status and result in db
        $notification->setStatus('finished')
            ->setMageResponse($response)
            ->setOrderId($magentoOrderId)
            ->setExecutedAt(Mage::getSingleton('core/date')->gmtDate())
            ->save();

        return $response;
    }

    /**
     * Do process of synchronisation
     *
     * @param array $params
     *
     * @return array
     */
    public function sync($params)
    {
        // Retrieve order from Api
        $oystOrderId = $params['oyst_order_id'];

        // Sync API
        /** @var Oyst_OneClick_Model_Order_ApiWrapper $orderApi */
        $orderApi = Mage::getModel('oyst_oneclick/order_apiWrapper');

        try {
            $this->orderResponse = $orderApi->getOrder($oystOrderId);
            Mage::helper('oyst_oneclick')->log($this->orderResponse);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->orderResponse['event'] = $this->eventNotification;
        $order = $this->createMagentoOrder();
        $this->orderResponse['magento_order_id'] = $order->getId();

        return $this->orderResponse;
    }

    private function createMagentoOrder()
    {
        // Register a 'lock' for not update status to Oyst
        Mage::register('order_status_changing', true);

        /** @var Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder */
        $magentoQuoteBuilder = Mage::getModel('oyst_oneclick/magento_quote', $this->orderResponse);
        $magentoQuoteBuilder->buildQuote();

        /** @var Oyst_OneClick_Model_Magento_Order $magentoOrderBuilder */
        $magentoOrderBuilder = Mage::getModel('oyst_oneclick/magento_order', $magentoQuoteBuilder->getQuote());
        $magentoOrderBuilder->buildOrder();

        $magentoOrderBuilder->getOrder()->addStatusHistoryComment(
            Mage::helper('oyst_oneclick')->__(
                '%s import order id: "%s".',
                $this->paymentMethod,
                $this->orderResponse['id']
            )
        )->save();

        // Change status of order if need to be invoice
        $this->changeStatus($magentoOrderBuilder->getOrder());

        Mage::unregister('order_status_changing');

        $this->clearCart($magentoQuoteBuilder->getQuote()->getQuoteId());

        return $magentoOrderBuilder->getOrder();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    private function changeStatus(Mage_Sales_Model_Order $order)
    {
        // Take the last status and change order status
        $currentStatus = $this->orderResponse['current_status'];

        // Update Oyst order to accepted and auto-generate invoice
        if (in_array($currentStatus, array(OystOrderStatus::PENDING))) {
            /** @var Oyst_OneClick_Model_Order_ApiWrapper $orderApiClient */
            $orderApiClient = Mage::getModel('oyst_oneclick/order_apiWrapper');

            try {
                $response = $orderApiClient->updateOrder($this->orderResponse['id'], OystOrderStatus::ACCEPTED);
                Mage::helper('oyst_oneclick')->log($response);

                $this->initTransaction($order);

                $order->addStatusHistoryComment(
                    Mage::helper('oyst_oneclick')->__('%s update order status to: "%s".',
                        $this->paymentMethod,
                        OystOrderStatus::ACCEPTED)
                )->save();

                $invIncrementIDs = array();
                if ($order->hasInvoices()) {
                    foreach ($order->getInvoiceCollection() as $inv) {
                        $invIncrementIDs[] = $inv->getIncrementId();
                    }
                }

                if ($order->getInvoiceCollection()->getSize()) {
                    $order->addStatusHistoryComment(
                        Mage::helper('oyst_oneclick')->__('%s generate invoice: "%s".',
                            $this->paymentMethod,
                            rtrim(implode(',', $invIncrementIDs), ','))
                    )->save();
                }

            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        if (in_array($currentStatus, array('denied', 'refunded'))) {
            $order->cancel();
        }

        $order->save();
    }

    /**
     * Add transaction to order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    private function initTransaction(Mage_Sales_Model_Order $order)
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');

        // Set transaction info
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($this->orderResponse['transaction']['id']);
        $payment->setCurrencyCode($this->orderResponse['transaction']['amount']['currency']);
        $payment->setPreparedMessage(Mage::helper('oyst_oneclick')->__('%s', $this->paymentMethod));
        $payment->setShouldCloseParentTransaction(true);
        $payment->setIsTransactionClosed(1);

        if (Mage::helper('oyst_oneclick')->getConfig('enable_invoice_auto_generation')) {
            $payment->registerCaptureNotification($helper->getHumanAmount($this->orderResponse['order_amount']['value']));
        }

        $order->save();
    }

    private function clearCart($quoteId)
    {
        $quotes = Mage::getModel('sales/quote')->getCollection()
            ->addFieldToFilter('is_active', array('eq' => 1))
            ->addFieldToFilter('entity_id', array('eq' => $quoteId));

        foreach ($quotes as $quote) {
            $quote->setIsActive(0);

            // @codingStandardsIgnoreLine
            $quote->save();
        }
    }
}

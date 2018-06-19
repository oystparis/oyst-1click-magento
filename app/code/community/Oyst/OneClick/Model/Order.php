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
        if ($lastNotification->getId()
            && Oyst_OneClick_Model_Notification::NOTIFICATION_STATUS_START === $lastNotification->getStatus()
        ) {
            Mage::throwException(
                Mage::helper('oyst_oneclick')->__(
                'Last Notification with order id "%s" is still processing.',
                $oystOrderId
            )
            );
        }

        // Create new notification in db with status 'start'
        $notification = Mage::getModel('oyst_oneclick/notification');
        $notification->registerNotificationStart($this->eventNotification, $apiData);

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
        $notification
            ->setMageResponse($response)
            ->setOrderId($magentoOrderId)
            ->registerNotificationFinish();

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
        /** @var Oyst_OneClick_Model_ApiWrapper_Type_Order $orderApi */
        $orderApi = Mage::getModel('oyst_oneclick/apiWrapper_type_order');

        try {
            $this->orderResponse = $orderApi->getOrder($oystOrderId);
            Mage::helper('oyst_oneclick')->log($this->orderResponse);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->orderResponse['event'] = $this->eventNotification;
        $order = $this->createMagentoOrder($oystOrderId);
        $this->orderResponse['magento_order_id'] = $order->getId();

        return $this->orderResponse;
    }

    /**
     * Create magento order.
     *
     * @param $oystOrderId
     *
     * @return Mage_Sales_Model_Order
     */
    private function createMagentoOrder($oystOrderId)
    {
        // Register a 'lock' for not update status to Oyst
        Mage::register('order_status_changing', true);

        /** @var Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder */
        $magentoQuoteBuilder = Mage::getModel('oyst_oneclick/magento_quote', $this->orderResponse);
        $magentoQuoteBuilder->syncQuoteFacade();

        Mage::dispatchEvent('oyst_oneclick_model_magento_order_create_magento_order_before',
            array('quote' => $magentoQuoteBuilder->getQuote())
        );

        /** @var Oyst_OneClick_Model_Magento_Order $magentoOrderBuilder */
        $magentoOrderBuilder = Mage::getModel('oyst_oneclick/magento_order', $magentoQuoteBuilder->getQuote());
        $magentoOrderBuilder->buildOrder();

        Mage::dispatchEvent('oyst_oneclick_model_magento_order_create_magento_order_after',
            array('quote' => $magentoQuoteBuilder->getQuote(), 'order' => $magentoOrderBuilder->getOrder())
        );

        $magentoOrderBuilder->getOrder()->addStatusHistoryComment(
            Mage::helper('oyst_oneclick')->__(
                '%s import order id: "%s".',
                $this->paymentMethod,
                $this->orderResponse['order']['id']
            )
        )->save();

        // Change status of order if need to be invoice
        $this->changeStatus($magentoOrderBuilder->getOrder());

        Mage::unregister('order_status_changing');

        $this->clearCart($magentoQuoteBuilder->getQuote()->getQuoteId(), $oystOrderId);

        $magentoOrderBuilder->getOrder()->sendNewOrderEmail();

        return $magentoOrderBuilder->getOrder();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    private function changeStatus(Mage_Sales_Model_Order $order)
    {
        // Take the last status and change order status
        $currentStatus = $this->orderResponse['order']['current_status'];

        // Update Oyst order to accepted and auto-generate invoice
        if (in_array($currentStatus, array(OystOrderStatus::PENDING))) {
            /** @var Oyst_OneClick_Model_ApiWrapper_Type_Order $orderApiClient */
            $orderApiClient = Mage::getModel('oyst_oneclick/apiWrapper_type_order');

            try {
                $response = $orderApiClient->updateOrder($this->orderResponse['order']['id'], OystOrderStatus::ACCEPTED, $order->getIncrementId());
                Mage::helper('oyst_oneclick')->log($response);

                $this->initTransaction($order);

                $order->addStatusHistoryComment(
                    Mage::helper('oyst_oneclick')->__(
                        '%s update order status to: "%s".',
                        $this->paymentMethod,
                        OystOrderStatus::ACCEPTED
                    )
                );

                $invIncrementIDs = array();
                if ($order->hasInvoices()) {
                    foreach ($order->getInvoiceCollection() as $inv) {
                        $invIncrementIDs[] = $inv->getIncrementId();
                    }
                }

                if ($order->getInvoiceCollection()->getSize()) {
                    $order->addStatusHistoryComment(
                        Mage::helper('oyst_oneclick')->__(
                            '%s generate invoice: "%s".',
                            $this->paymentMethod,
                            rtrim(implode(',', $invIncrementIDs), ',')
                        )
                    );
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        if (in_array($currentStatus, array('denied', 'refunded'))) {
            $order->cancel();
        }

        Mage::dispatchEvent('oyst_oneclick_model_magento_order_change_status_after',
            array('order' => $order)
        );

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
        $payment->setTransactionId($this->orderResponse['order']['transaction']['id']);
        $payment->setCurrencyCode($this->orderResponse['order']['transaction']['amount']['currency']);
        $payment->setPreparedMessage(Mage::helper('oyst_oneclick')->__('%s', $this->paymentMethod));
        $payment->setShouldCloseParentTransaction(true);
        $payment->setIsTransactionClosed(1);

        if (Mage::helper('oyst_oneclick')->getConfig('enable_invoice_auto_generation')) {
            $payment->registerCaptureNotification($helper->getHumanAmount($this->orderResponse['order']['order_amount']['value']));
        }

        Mage::dispatchEvent('oyst_oneclick_model_magento_order_init_transaction_after',
            array('payment' => $payment, 'order' => $order)
        );
    }

    /**
     * Clear cart.
     *
     * @param $quoteId
     * @param $oystOrderId
     */
    private function clearCart($quoteId, $oystOrderId)
    {
        $quotes = Mage::getModel('sales/quote')->getCollection()
            ->addFieldToFilter('is_active', array('eq' => 1))
            ->addFieldToFilter('entity_id', array('eq' => $quoteId));

        $resourceHelper = Mage::getResourceModel('oyst_oneclick/helper');
        $resourceHelper->inactivateQuotesByIds($quotes->getAllIds());
    }
}

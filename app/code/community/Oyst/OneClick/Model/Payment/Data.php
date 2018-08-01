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

use Oyst\Classes\OystPrice;

/**
 * Payment_Data Helper
 */
class Oyst_OneClick_Model_Payment_Data extends Mage_Core_Model_Abstract
{
    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return string
     */
    protected function _getConfig($code, $paymentMethodCode = null, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = Mage::app()->getStore()->getStoreId();
        }

        if (empty($paymentMethodCode)) {
            return trim(Mage::getStoreConfig("payment/oyst_abstract/$code", $storeId));
        }

        return trim(Mage::getStoreConfig("payment/$paymentMethodCode/$code", $storeId));
    }

    /**
     * Sync payment information from notification
     *
     * @param array $event
     * @param array $data
     *
     * @return string
     */
    public function processNotification($event, $data)
    {
        /** @var Oyst_OneClick_Model_Notification $lastNotification */
        $lastNotification = Mage::getModel('oyst_oneclick/notification');
        // Get last notification
        $lastNotification = $lastNotification->getLastNotification('payment', $data['payment_id']);

        // If last notification is not finished
        if ($lastNotification->getId()
            && Oyst_OneClick_Model_Notification::NOTIFICATION_STATUS_FINISHED != $lastNotification->getStatus()
        ) {
            Mage::throwException($this->__('Last Notification payment id %s is not finished', $data['payment_id']));
        }

        // Create new notification in db with status 'start'
        $notification = Mage::getModel('oyst_oneclick/notification');
        $notification->registerNotificationStart($event, $data);

        // Get order_increment_id
        if (empty($data['order_increment_id'])) {
            Mage::throwException($this->__('order_increment_id not found'));
        }

        $orderIncrementId = $data['order_increment_id'];
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');

        // Get payment_id
        if (empty($data['payment_id'])) {
            Mage::throwException($this->__("payment_id not found for payment id %s", $data['payment_id']));
        }

        $paymentId = $data['payment_id'];

        $params = array(
            'order_increment_id' => $orderIncrementId,
        );
        // If data asynchronous payment notification is success, we invoice the order, else we cancel
        if (Oyst_OneClick_Model_Payment_Method_Freepay::EVENT_CODE_FRAUD_VALIDATION == $data['event_code'] && $data['success']) {
            //save new status and result in db
            $notification->registerNotificationFinish();

            // @TODO implement fraud validation
            return 'by-pass fraud validation';
        }

        if (Oyst_OneClick_Model_Payment_Method_Freepay::EVENT_CODE_AUTHORISATION == $data['event_code'] && $data['success']) {
            $result = $this->invoice($order, $data);
        }

        if (Oyst_OneClick_Model_Payment_Method_Freepay::EVENT_CODE_CAPTURE == $data['event_code'] && $data['success']) {
            $result = $this->invoice($order, $data);
        }

        if (Oyst_OneClick_Model_Payment_Method_Freepay::EVENT_CODE_CANCELLATION == $data['event_code'] && $data['success']) {
            $result = $this->cancel($order, $paymentId);
        }

        if (!$data['success']) {
            $order->addStatusHistoryComment(
                sprintf(
                    $this->__('Last %s notification fail.'),
                    $data['event_code']
                )
            )->save();
        }

        //save new status and result in db
        $notification
            ->setOrderId($result['order_id'])
            ->registerNotificationFinish();

        return json_encode(array('order_id' => $result['order_id']));
    }

    /**
     * Create Invoice for order
     *
     * @param Mage_Sales_Model_Order $order
     * @param mixed $transactionData
     *
     * @return array
     */
    public function invoice(Mage_Sales_Model_Order $order, $transactionData = false)
    {
        //prepare invoice
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

        //prepare transaction and create invoice
        if ($transactionData) {
            $this->_addTransaction($order, $transactionData);
        } else {
            //pay offline
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            //don't notify customer
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);

            //save order and invoice
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
        }

        return array('order_id' => $order->getId());
    }

    /**
     * Cancel Order
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $paymentId
     *
     * @return array
     */
    public function cancel(Mage_Sales_Model_Order $order, $paymentId)
    {
        if ($order->canUnhold()) {
            $order->unhold()->save();
        }

        if (!$order->canCancel()) {
            $order->addStatusHistoryComment(
                $this->__('Cancel Order is not possible. Payment Id: "%s".', $paymentId)
            )->save();
        } else {
            try {
                $order->cancel();
                $order->getStatusHistoryCollection(true);
                $order->addStatusHistoryComment(
                    $this->__('Success Cancel Order. Payment Id: "%s".', $paymentId)
                )->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return array(
            'order_id' => $order->getId(),
        );
    }

    /**
     * Create order Transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $transactionData
     */
    protected function _addTransaction($order, $transactionData)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $paymentId = !empty($transactionData['payment_id']) ? $transactionData['payment_id'] : false;

        if ($order->getId() && $paymentId) {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();

            $amount = $this->getFormatAmount($transactionData);

            // save transaction details to sales_payment_transaction
            $additionalInfo = array(
                $oystHelper->__('Payment Service Providers') => Oyst_OneClick_Model_Payment_Method_Freepay::PAYMENT_METHOD_NAME,
                $oystHelper->__('Transaction Number') => $transactionData['payment_id'],
                $oystHelper->__('Transaction Type') => 'DEBIT',
                $oystHelper->__('Transaction Status') => $transactionData['event_code'],
                $oystHelper->__('Amount') => $amount . ' ' . $transactionData['amount']['currency'],
            );

            if (!empty($transactionData['additional_data']) && isset($transactionData['additional_data']['expiry_date'])) {
                $expiryMonth = '';
                $expiryYear = '';
                list($expiryMonth, $expiryYear) = explode('/', $transactionData['additional_data']['expiry_date']);

                // save payment infos to sales_flat_order_payment
                $order->getPayment()
                    ->setCcType('CB')
                    ->setCcLast4($transactionData['additional_data']['card_summary'])
                    ->setCcExpMonth($expiryMonth)
                    ->setCcExpYear($expiryYear);

                $additionalInfo[$oystHelper->__('Credit Card No Last 4')]  = $transactionData['additional_data']['card_summary'];
                $additionalInfo[Mage::helper('oyst_oneclick')->__('Expiration Date')] = $expiryMonth . ' / ' . $expiryYear;
            }

            // set transaction addition information
            $payment->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $additionalInfo
            );

            $payment->setTransactionId($paymentId)
                ->setCurrencyCode()
                ->setPreparedMessage("Success")
                ->setParentTransactionId(false)
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(1)
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $additionalInfo);

            if (Oyst_OneClick_Model_Payment_Method_Freepay::EVENT_CODE_AUTHORISATION == $transactionData['event_code']) {
                $payment->registerAuthorizationNotification($amount);
            } elseif (Oyst_OneClick_Model_Payment_Method_Freepay::EVENT_CODE_CAPTURE == $transactionData['event_code']) {
                $payment->registerCaptureNotification($amount, true);
            }

            Mage::dispatchEvent(
                'oyst_oneclick_model_payment_data_add_transaction_after',
                array('payment' => $payment, 'order' => $order)
            );

            $order->save();
        }
    }

    /**
     * Construct Oyst Secure Url
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        $params = $this->_constructParams();

        /** @var Oyst_OneClick_Model_ApiWrapper_Type_Payment $paymentApiWrapper */
        $paymentApiWrapper = Mage::getModel('oyst_oneclick/apiWrapper_type_payment');
        $response = $paymentApiWrapper->getPaymentUrl($params);

        return $response;
    }

    /**
     * Get url params
     *
     * @return array
     */
    protected function _constructParams()
    {
        $orderIncrementId = Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
        $params['order_id'] = $orderIncrementId;
        $params['label'] = $this->_getConfig('invoice_label');

        $this->_addAmount($params);
        $this->_addUrls($params, $orderIncrementId);
        $this->_addUserInfos($params);

        return $params;
    }

    /**
     * Add amount to pay as param url
     *
     * @param array Url params
     *
     * @return null
     */
    protected function _addAmount(&$params)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $amount = new OystPrice($checkoutSession->getQuote()->getGrandTotal(), 'EUR');
        $params['amount'] = $amount->toArray();
    }

    /**
     * Add urls
     *
     * @param array $params
     * @param int $orderId
     *
     * @return null
     */
    protected function _addUrls(&$params, $orderId)
    {
        $notificationUrl = sprintf(
            "%s" . 'order_increment_id/' . "%d",
            $this->_getConfig('notification_url'),
            $orderId
        );
        $cancelUrl = $this->_getConfig('cancel_url') . 'order_increment_id/'  . $orderId;
        $errorUrl = $this->_getConfig('error_url') . 'order_increment_id/' . $orderId;
        $returnUrl = $this->_getConfig('return_url') . 'order_increment_id/' . $orderId;

        $params['urls'] = array(
            'notification' => $notificationUrl,
            'cancel' => $cancelUrl,
            'error' => $errorUrl,
            'return' => $returnUrl,
        );
    }

    /**
     * Add customer infos
     *
     * @param array $params
     *
     * @return null
     */
    protected function _addUserInfos(&$params)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        $customer = $quote->getCustomer();

        if ($quote->getCheckoutMethod() == 'guest') {
            $params['user']['additional_data'] = $this->_getCustomerInfosFromQuote($quote);
            $params['user']['email'] = $quote->getCustomerEmail();
            $params['user']['first_name'] = $quote->getCustomerFirstname();
            $params['user']['last_name'] = $quote->getCustomerLastname();
            $params['user']['phone'] = $quote->getBillingAddress()->getTelephone();
        } else {
            $params['user']['additional_data'] = $customer->getData();
            $params['user']['email'] = $customer->getEmail();
            $params['user']['first_name'] = $customer->getFirstname();
            $params['user']['last_name'] = $customer->getLastname();
            $params['user']['phone'] = ($customer->getPhone()) ?
                $customer->getPhone() : $quote->getBillingAddress()->getTelephone();
        }

        $params['user']['language'] = Mage::app()->getLocale()
            ->getLocale()
            ->getLanguage();
        $params['user']['addresses'][] = $this->_getAddresses($quote->getShippingAddress());
        $params['user']['billing_addresses'][] = $this->_getAddresses($quote->getBillingAddress());
    }

    /**
     * Get customer address datas in array
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return array
     */
    protected function _getAddresses($address)
    {
        $attr = array(
            'city' => 'city',
            'company_name' => 'company',
            'complementary' => 'complementary',
            'country' => 'country_id',
            'first_name' => 'firstname',
            'label' => 'label',
            'last_name' => 'lastname',
            'postcode' => 'postcode',
            'street' => 'street',
        );

        foreach ($attr as $oystAttr => $mageAttr) {
            if ($address->getData($mageAttr)) {
                $param[$oystAttr] = $address->getData($mageAttr);
            } elseif ($oystAttr == 'label') { // label is required even if empty
                $param['label'] = $address->getAddressType();
            }
        }

        return $param;
    }

    /**
     * Get all optional customer information from quote
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function _getCustomerInfosFromQuote($quote)
    {
        $attr = array();
        foreach ($quote->getData() as $key => $data) {
            if (preg_match("/^customer_*/", $key)) {
                $attr[$key] = $data;
            }
        }

        return $attr;
    }

    /**
     * Format the amount to get the right syntax
     *
     * @param array $transactionData
     *
     * @return float|int $amount
     */
    protected function getFormatAmount($transactionData)
    {
        $amount = !empty($transactionData['amount']) ?
            !empty($transactionData['amount']['value']) ? $transactionData['amount']['value'] : 0 : 0;

        //must transform amount from YYYYY to YYY.YY
        if ($amount > 0) {
            $amount = (float)$amount / 100;
        } else {
            $amount = 0;
        }

        return $amount;
    }
}

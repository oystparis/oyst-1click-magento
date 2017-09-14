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
 * Order Helper
 */
class Oyst_OneClick_Helper_Order_Data extends Mage_Core_Helper_Abstract
{
    protected $paymentMethod = Oyst_OneClick_Model_Payment_Method_Oneclick::PAYMENT_METHOD_NAME;

    /**
     * Sync order from notification
     *
     * @param array $event
     * @param array $data
     *
     * @return array
     */
    public function syncFromNotification($event, $data)
    {
        $oystOrderId = $data['order_id'];

        // Get last notification
        /** @var Oyst_OneClick_Model_Notification $lastNotification */
        $lastNotification = Mage::getModel('oyst_oneclick/notification');
        $lastNotification = $lastNotification->getLastNotification('order', $oystOrderId);

        // If last notification is not finished
        if ($lastNotification->getId() && $lastNotification->getStatus() != 'finished') {
            Mage::throwException($this->__('Last Notification with order id "%s" is not finished', $oystOrderId));
        }

        // Create new notification in db with status 'start'
        $notification = Mage::getModel('oyst_oneclick/notification');
        $notification->setData(
            array(
                'event' => $event,
                'oyst_data' => Zend_Json::encode($data),
                'status' => 'start',
                'created_at' => Zend_Date::now(),
                'executed_at' => Zend_Date::now(),
            )
        );
        $notification->save();

        $params = array(
            'oyst_order_id' => $oystOrderId
        );
        // Sync Order From Api
        $result = $this->sync($params);

        // Save new status and result in db
        $notification->setStatus('finished')
            ->setOrderId($result['magento_order_id'])
            ->setExecutedAt(Mage::getSingleton('core/date')->gmtDate())
            ->save();

        return array(
            'magento_order_id' => $result['magento_order_id']
        );
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
            $response = $orderApi->getOrder($oystOrderId);
            Mage::helper('oyst_oneclick')->log($response);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        // Save order in Magento
        $order = $this->_importOrder($response);
        $response['magento_order_id'] = $order->getId();

        return $response;
    }

    /**
     * Import order from Oyst to Magento
     *
     * @param array $params
     *
     * @return array
     */
    protected function _importOrder($params)
    {
        // Register a 'lock' for not update status to Oyst
        Mage::register('order_status_changing', true);

        // Init temporary quote
        $quote = $this->_initQuote($params);

        // Init quote customer
        $quote = $this->_initCustomerInfos($params, $quote);

        // Init quote address
        $quote = $this->_initAddresses($params, $quote);
        $quote->getPayment()->importData(array('method' => 'oyst_oneclick'));

        // Transform quote to order
        $order = $this->_submitQuote($quote);
        $order->setCreatedAt($params['created_at']);

        $order->addStatusHistoryComment(
            $this->__('%s import order id: "%s".',
                $this->paymentMethod,
                $params['id'])
        )->save();

        // Change status of order if need to be invoice
        $order = $this->_changeStatus($params, $order);
        Mage::unregister('order_status_changing');

        return $order;
    }

    /**
     * Init temporary cart
     *
     * @param array $params
     *
     * @return array
     */
    protected function _initQuote($params)
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')->setIsSuperMode(true);
        $quote->setCreatedAt($params['created_at']);
        $quote->setUpdatedAt($params['created_at']);
        $quote->setOystOrderId($params['id']);

        foreach ($params['items'] as $item) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($item['product_reference']);
            $product->setTitle($item['product']['title']);
            $product->setPrice($helper->getHumanAmount($item['product_amount']['value']));

            $request = array('qty' => $item['quantity']);

            if (Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE == $product->getTypeId()) {
                $links = Mage::getModel('downloadable/product_type')->getLinks($product);
                $linkId = 0;
                foreach ($links as $link) {
                    $linkId = $link->getId();
                }

                $request = array_merge($request, array('links' => $linkId));
            }

            if (isset($item['product']['variations']) &&
                isset($item['product']['variations']['informations']) &&
                !is_null($item['product']['variations']['informations'])) {
                $superAttr = array();
                foreach ($item['product']['variations']['informations'] as $attributeCode => $attributeValue) {
                    $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);

                    $attributeCodeId = $attribute->getId();
                    if (!is_null($attributeCodeId) &&
                        !is_null($optionId = $attribute->getSource()->getOptionId($attributeValue))
                    ) {
                        $superAttr[$attributeCodeId] = $optionId;
                    }
                }

                $request = array_merge($request, array('super_attribute' => $superAttr));
            }

            $quote->addProduct($product, new Varien_Object($request));
        }

        return $quote;
    }

    /**
     * Init Customer
     *
     * @param array $params
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _initCustomerInfos($params, $quote)
    {
        // Already customer ; Check by website
        if ($customer = $this->_getCustomerByEmailAndWebsite($params['user']['email'])) {
            $quote->setCustomerFirstname($customer->getFirstname());
            $quote->setCustomerLastname($customer->getLastname());

            $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER)
                ->setCustomerId($customer->getId())
                ->setCustomerEmail($customer->getEmail())
                ->setCustomerIsGuest(false)
                ->save();
        }

        // New guest
        if (is_null($customer)) {
            $firstname = '';
            if (!empty($params['user']['first_name'])) {
                $firstname = $params['user']['first_name'];
            }

            $lastname = '';
            if (!empty($params['user']['last_name'])) {
                $lastname = $params['user']['last_name'];
            }

            $email = Mage::getStoreConfig('trans_email/ident_general/email');
            if (!empty($params['user']['email'])) {
                $email = $params['user']['email'];
            }

            $quote->setCustomerFirstname($firstname);
            $quote->setCustomerLastname($lastname);

            $quote->setCheckoutMethod('guest')
                ->setCustomerId(null)
                ->setCustomerEmail($email)
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)
                ->save();
        }

        return $quote;
    }

    /**
     * Get the customer by email
     *
     * @param $email
     * @param null $websiteId
     *
     * @return bool|Mage_Customer_Model_Customer
     */
    protected function _getCustomerByEmailAndWebsite($email, $websiteId = null)
    {
        $customer = function ($email, $websiteId) {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($email);
            if ($customer->getId()) {
                return $customer;
            }
        };

        if ($websiteId) {
            return $customer($email, $websiteId);
        } else {
            foreach (Mage::app()->getWebsites() as $website) {
                return $customer($email, $website->getWebsiteId());
            }
        }

        return false;
    }

    /**
     * Init quote addresses
     * Warning : we don't have an order example to know if it will have a customer id
     *
     * @param array $params
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _initAddresses($params, $quote)
    {
        // Consider all address info only from API same as New guest
        $defaultShippingAddress = $defaultBillingAddress = Mage::getModel('customer/address');

        // Format addresses
        $shippingInfoFormated = $this->_formatAddress($defaultShippingAddress, 'shipping', $params);
        $billingInfoFormated = $this->_formatAddress($defaultBillingAddress, 'billing', $params);

        // Add address
        $quote->getBillingAddress()->addData($billingInfoFormated);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($shippingInfoFormated);

        $shippingAddress = $shippingAddress->setShippingMethod($params['shipment']['carrier']['id'])
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $shippingAddress->save();

        // Save quote
        $quote->save();

        return $quote;
    }

    /**
     * Transform Magento address to formatted array
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param string $type
     * @param array $params
     *
     * @return array
     */
    protected function _formatAddress($address, $type, $params)
    {
        $orderAddress = $params['user']['address'];

        $street = array_filter($address->getStreet());

        $formattedAddress = array(
            'city' => (string)$address->getCity() ? $address->getCity() : $orderAddress['city'],
            'country_id' => (string)$address->getCountryId() ? $address->getCountryId() : $orderAddress['country'],
            'firstname' => (string)$address->getFirstname() ? $address->getFirstname() : $orderAddress['first_name'],
            'lastname' => (string)$address->getLastname() ? $address->getLastname() : $orderAddress['last_name'],
            'postcode' => (string)$address->getPostcode() ? $address->getPostcode() : $orderAddress['postcode'],
            'street' => !empty($street) ? $address->getStreet() : $orderAddress['street'],
            'telephone' => (string)$address->getTelephone() ? $address->getTelephone() : $params['user']['phone'],
            'region_id' => (string)$address->getRegionId() ? $address->getRegionId() : 'region_id',
            'region' => (string)$address->getRegion() ? $address->getRegion() : $orderAddress['city'],
            'use_for_shipping' => '0',
            'name' => 'freeshipping_freeshipping'//$orderAddress['label']
        );

        if ('shipping' === $type) {
            $formattedAddress['shipping_method'] = 'freeshipping_freeshipping';
            $formattedAddress['use_for_shipping'] = '1';
            $formattedAddress['same_as_billing'] = '0';
        }

        return $formattedAddress;
    }

    /**
     * Transform temporary cart to order
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _submitQuote($quote)
    {
        try {
            /** @var Mage_Sales_Model_Service_Quote $service */
            $service = Mage::getModel('sales/service_quote', $quote);
            $order = false;
            if (method_exists($service, 'submitAll')) {
                $service->submitAll();
                $order = $service->getOrder();
            } else {
                $order = $service->submit();
            }
            if (!$order) {
                throw new Exception('Service unable to create order based on given quote.');
            }
            $order->save();
        } catch (Exception $e) {
            Mage::helper('oyst_oneclick')->log('Error create order: ' . $e->getMessage());
        }

        return $order;
    }

    /**
     * Change Order Status
     *
     * @param array $params
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _changeStatus($params, $order)
    {
        // Take the last status and change order status
        $currentStatus = $params['current_status'];

        // Update Oyst order to accepted and auto-generate invoice
        if (in_array($currentStatus, array(Oyst_OneClick_Model_Order_ApiWrapper::STATUS_PENDING))) {
            /** @var Oyst_OneClick_Model_Order_ApiWrapper $orderApi */
            $orderApi = Mage::getModel('oyst_oneclick/order_apiWrapper');

            try {
                $response = $orderApi->updateOrder($params['id'], Oyst_OneClick_Model_Order_ApiWrapper::STATUS_ACCEPTED);
                Mage::helper('oyst_oneclick')->log($response);

                $order = $this->_initTransaction($params, $order);

                $order->addStatusHistoryComment(
                    $this->__('%s update order status to: "%s".',
                        $this->paymentMethod,
                        Oyst_OneClick_Model_Order_ApiWrapper::STATUS_ACCEPTED)
                )->save();

                $invIncrementIDs = array();
                if ($order->hasInvoices()) {
                    foreach ($order->getInvoiceCollection() as $inv) {
                        $invIncrementIDs[] = $inv->getIncrementId();
                    }
                }
                $order->addStatusHistoryComment(
                    $this->__('%s generate invoice: "%s".',
                        $this->paymentMethod,
                        rtrim(implode(',', $invIncrementIDs), ','))
                )->save();

            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        if (in_array($currentStatus, array('denied', 'refunded'))) {
            $result = $this->cancel($order);
        }

        return $order;
    }

    /**
     * Add transaction to order
     *
     * @param $params
     * @param $order
     *
     * @return mixed
     */
    protected function _initTransaction($params, $order)
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');

        // Set transaction info
        $payment = $order->getPayment();
        $payment->setTransactionId($params['transaction']['id'])
            ->setCurrencyCode($params['transaction']['amount']['currency'])
            ->setPreparedMessage($this->__('%s', $this->paymentMethod))
            ->setShouldCloseParentTransaction(true)
            ->setIsTransactionClosed(1)
            ->registerCaptureNotification($helper->getHumanAmount($params['transaction']['amount']['value']));
        $order->save();

        return $order;
    }

    /**
     * Cancel and Refund Order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function cancelAndRefund($order)
    {
        if ($order->canCreditmemo()) {
            $invoiceId = $order->getInvoiceCollection()->clear()->setPageSize(1)->getFirstItem()->getId();

            if (!$invoiceId) {
                return $this;
            }

            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId)->setOrder($order);

            /** @var Mage_Sales_Model_Service_Order $service */
            $service = Mage::getModel('sales/service_order', $order);

            /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
            $creditmemo = $service->prepareInvoiceCreditmemo($invoice);

            $backToStock = array();
            foreach ($order->getAllItems() as $item) {
                $backToStock[$item->getId()] = true;
            }

            // Process back to stock flags
            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                if (Mage::helper('cataloginventory')->isAutoReturnEnabled()) {
                    $creditmemoItem->setBackToStock(true);
                } else {
                    $creditmemoItem->setBackToStock(false);
                }
            }

            $creditmemo->register();

            /** @var Mage_Core_Model_Resource_Transaction $transactionSave */
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($creditmemo)
                ->addObject($creditmemo->getOrder());

            if ($creditmemo->getInvoice()) {
                $transactionSave->addObject($creditmemo->getInvoice());
            }

            $transactionSave->save();
        }
    }
}

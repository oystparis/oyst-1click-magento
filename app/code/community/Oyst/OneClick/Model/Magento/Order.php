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
 * Order Model
 */
class Oyst_OneClick_Model_Magento_Order
{
    /** @var Mage_Sales_Model_Quote|null $quote */
    private $quote = null;

    /** @var Mage_Sales_Model_Order $order */
    private $order = null;

    private $additionalData = array();

    /** @var string[] API response */
    private $apiData = null;

    public function __construct($orderResponse)
    {
        $this->apiData = $orderResponse;
    }

    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->quote = $quote;
        return $this;
    }

    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    public function saveOrder()
    {
        try {
            if (!$this->quote->getCustomerId() 
              && Mage::getStoreConfig('oyst/oneclick/new_customer_account')) {
                $customer = $this->createCustomer(
                    $this->quote->getCustomerFirstname(), $this->quote->getCustomerLastname(), $this->quote->getCustomerEmail()
                );
                $this->quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
                $this->quote->setCustomer($customer);
                //$this->quote->save();
            }

            if (!$this->quote->getCustomerId()) {
                $this->quote->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
                $this->quote->setCustomerIsGuest(true);
            } else {
                $this->quote->setCustomerIsGuest(false);
            }
            
            $this->order = $this->placeOrder();
            $this->order->save();
            $this->quote->setIsActive(false)->save();
        } catch (Exception $e) {
            Mage::helper('oyst_oneclick')->log('Error create order: ' . $e->getMessage());
            throw $e;
        }
    }

    private function placeOrder()
    {
        if (version_compare(Mage::getVersion(), '1.4.1', '>=')) {
            /** @var Mage_Sales_Model_Service_Quote $service */
            $service = Mage::getModel('sales/service_quote', $this->quote);
            $service->setOrderData($this->additionalData);
            $service->submitAll();

            return $service->getOrder();
        }

        // Magento version 1.4.0 backward compatibility code

        /** @var Mage_Sales_Model_Convert_Quote $quoteConverter */
        $quoteConverter = Mage::getSingleton('sales/convert_quote');

        /** @var Mage_Sales_Model_Order $orderObj */
        $orderObj = $quoteConverter->addressToOrder($this->quote->getShippingAddress());

        $orderObj->setBillingAddress($quoteConverter->addressToOrderAddress($this->quote->getBillingAddress()));
        $orderObj->setShippingAddress($quoteConverter->addressToOrderAddress($this->quote->getShippingAddress()));
        $orderObj->setPayment($quoteConverter->paymentToOrderPayment($this->quote->getPayment()));

        $items = $this->quote->getShippingAddress()->getAllItems();

        foreach ($items as $item) {
            /** @var Mage_Sales_Model_Quote_Item $orderItem */
            $orderItem = $quoteConverter->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $orderObj->addItem($orderItem);
        }

        $orderObj->addData($this->additionalData);

        $orderObj->setCanShipPartiallyItem(false);
        $orderObj->place();
        $orderObj->save();

        return $orderObj;
    }

    /**
     * Create new customer.
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     *
     * @return false|Mage_Core_Model_Abstract
     */
    private function createCustomer($firstname, $lastname, $email)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $store = Mage::app()->getStore();
        $websiteId = $store->getWebsiteId();

        try {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($this->quote->getCustomerFirstname())
                ->setLastname($this->quote->getCustomerLastname())
                ->setEmail($this->quote->getCustomerEmail());
            $customer->save();

            // Send welcome email
            $customer->sendNewAccountEmail('registered', '', $store->getId(), $customer->generatePassword(16));

            /** @var Mage_Customer_Model_Address $address */
            $address = Mage::getModel('customer/address');
            $quoteAddress = $this->quote->isVirtual() ? $this->quote->getBillingAddress() : $this->quote->getShippingAddress();

            $address->setCustomerId($customer->getId())
                ->setFirstname($customer->getFirstname())
                ->setLastname($customer->getLastname())
                ->setCountryId($quoteAddress->getCountryId())
                ->setPostcode($quoteAddress->getPostcode())
                ->setCity($quoteAddress->getCity())
                ->setTelephone($quoteAddress->getTelephone())
                ->setStreet($quoteAddress->getStreet())
                ->setIsDefaultBilling(true)
                ->setIsDefaultShipping(true)
                ->setSaveInAddressBook(true);
            if (($validateRes = $address->validate())!==true) {
                throw new Exception(implode('\n', $validateRes));
            }
            $address->save();
        } catch (Exception $e) {
            Mage::helper('oyst_oneclick')->log($e->getMessage());
        }

        Mage::dispatchEvent('oyst_oneclick_model_magento_order_create_customer_after', array('quote' => $this->quote, 'request' => $this->apiData, 'customer' => $customer));

        return $customer;
    }
}

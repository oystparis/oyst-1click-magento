<?php

class Oyst_OneClick_Model_OystCustomerManagement extends Oyst_OneClick_Model_AbstractOystManagement
{
    public function createMagentoCustomerFromOrder(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $store = $order->getStore();
        $websiteId = $store->getWebsiteId();

        try {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($order->getCustomerFirstname())
                ->setLastname($order->getCustomerLastname())
                ->setEmail($order->getCustomerEmail());
            $customer->save();

            // Send welcome email
            $customer->sendNewAccountEmail('registered', '', $store->getId(), $customer->generatePassword(16));

            /** @var Mage_Customer_Model_Address $address */
            $address = Mage::getModel('customer/address');

            $address->setCustomerId($customer->getId())
                ->setFirstname($customer->getFirstname())
                ->setLastname($customer->getLastname())
                ->setCountryId($order->getBillingAddress()->getCountryId())
                ->setPostcode($order->getBillingAddress()->getPostcode())
                ->setCity($order->getBillingAddress()->getCity())
                ->setTelephone($order->getBillingAddress()->getTelephone())
                ->setStreet($order->getBillingAddress()->getStreet())
                ->setIsDefaultBilling(true)
                ->setIsDefaultShipping(true)
                ->setSaveInAddressBook(true);
            $address->save();
        } catch (Exception $e) {
            Mage::log($e->__toString(), null, 'error_oyst.log', true);
        }

        Mage::dispatchEvent(
            'oyst_oneclick_model_oyst_customer_management_create_magento_customer_from_order_after', 
            array('customer' => $customer, 'order' => $order)
        );

        return $customer;
    }
}
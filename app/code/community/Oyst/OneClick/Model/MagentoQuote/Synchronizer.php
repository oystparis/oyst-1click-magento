<?php

class Oyst_OneClick_Model_MagentoQuote_Synchronizer
{
    public function syncMagentoQuote(
        array $oystCheckout,
        Mage_Sales_Model_Quote $quote,
        Mage_Customer_Model_Customer $customer,
        Mage_SalesRule_Model_Coupon $coupon
    )
    {
        Mage::getSingleton('checkout/session')->replaceQuote($quote);
        $quote->setOystId($oystCheckout['oyst_id']);

        $this->syncMagentoAddresses($quote, $oystCheckout['billing'], $oystCheckout['shipping']);
        $this->syncMagentoCustomer($quote, $customer, $oystCheckout['user']);
        $this->syncMagentoQuoteItems($quote, $oystCheckout['items']);
        $this->syncMagentoCoupon($quote, $coupon);
        $this->syncMagentoPaymentMethod($quote);

        Mage::dispatchEvent(
            'oyst_oneclick_model_magento_quote_sync_quote_after',
            array('quote' => $quote, 'oyst_checkout' => $oystCheckout)
        );

        return true;
    }

    protected function syncMagentoQuoteItems(
        Mage_Sales_Model_Quote $quote,
        array $oystCheckoutItems
    )
    {
        $cartData = array();

        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            foreach ($oystCheckoutItems as $oystCheckoutItem) {
                if ($item->getId() == $oystCheckoutItem['internal_reference']) {
                    $cartData[$item->getId()]['qty'] = $oystCheckoutItem['quantity'];
                    break;
                }
            }

            if (!isset($cartData[$item->getId()]['qty'])) {
                $cartData[$item->getId()]['qty'] = 0;
            }
        }

        $cart = Mage::getSingleton('checkout/cart');
        $cartData = $cart->suggestItemsQty($cartData);
        $cart->updateItems($cartData);
        Mage::helper('oyst_oneclick')->handleQuoteErrors($cart->getQuote());
        $cart->save();

        return true;
    }

    protected function syncMagentoCustomer(
        Mage_Sales_Model_Quote $quote,
        Mage_Customer_Model_Customer $customer,
        array $oystCheckoutUser
    )
    {
        if($customer->getId()) {
            $quote->setCustomer($customer);
        } else {
            $quote->setCustomerEmail($oystCheckoutUser['email']);
            $quote->setCustomerFirstname($oystCheckoutUser['firstname']);
            $quote->setCustomerLastname($oystCheckoutUser['lastname']);
            $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
        }

        Mage::dispatchEvent(
            'oyst_oneclick_model_magento_quote_sync_customer_after',
            array('quote' => $quote, 'customer' => $customer, 'oyst_checkout_user' => $oystCheckoutUser)
        );

        return true;
    }

    protected function syncMagentoCoupon(
        Mage_Sales_Model_Quote $quote,
        Mage_SalesRule_Model_Coupon $coupon
    )
    {
        if ($coupon->getId()) {
            $quote->setCouponCode($coupon->getCode());
        }

        return true;
    }

    protected function syncMagentoAddresses(
        Mage_Sales_Model_Quote $quote,
        array $oystCheckoutBilling,
        array $oystCheckoutShipping
    )
    {
        /* @var Mage_Sales_Model_Quote_Address $billingAddress */
        $billingAddress = $quote->getBillingAddress();
        $billingAddressData = $this->getAddressData($oystCheckoutBilling['address']);
        $billingAddress->addData($billingAddressData);

        $billingAddress->setSaveInAddressBook(false);
        if (($validateRes = $billingAddress->validate()) !== true) {
            throw new Mage_Checkout_Exception(implode('\n', $validateRes));
        }

        /* @var Mage_Sales_Model_Quote_Address $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddressData = $this->getAddressData($oystCheckoutShipping['address']);
        $shippingAddress->setSameAsBilling(0);
        $shippingAddress->addData($shippingAddressData);

        $shippingAddress->setSaveInAddressBook(false);
        if (($validateRes = $shippingAddress->validate()) !== true) {
            throw new Mage_Checkout_Exception(implode('\n', $validateRes));
        }

        Mage::dispatchEvent('oyst_oneclick_model_magento_quote_sync_addresses_after', array('quote' => $quote));

        return true;
    }

    protected function getAddressData(
        array $oystCheckoutAddress
    )
    {
        $addressData = [];

        $addressData['firstname'] = $oystCheckoutAddress['firstname'];
        $addressData['lastname'] = $oystCheckoutAddress['lastname'];
        $addressData['city'] = $oystCheckoutAddress['city'];
        $addressData['postcode'] = $oystCheckoutAddress['postcode'];
        $addressData['street'] = [$oystCheckoutAddress['street1'], $oystCheckoutAddress['street2']];
        $addressData['country_id'] = $oystCheckoutAddress['country']['code'];
        $addressData['telephone'] = $oystCheckoutAddress['phone_mobile'];
        $addressData['email'] = $oystCheckoutAddress['email'];
        $addressData['company'] = $oystCheckoutAddress['company'];

        return $addressData;
    }

    protected function syncMagentoPaymentMethod(
        Mage_Sales_Model_Quote $quote
    )
    {
        /** @var Oyst_OneClick_Model_Payment_Method_OneClick $paymentMethod */
        $paymentMethod = Mage::getModel('oyst_oneclick/payment_method_oneClick');

        /** @var Mage_Sales_Model_Quote_Payment $payment */
        $payment = $quote->getPayment();

        $payment
            ->importData(array('method' => $paymentMethod->getCode()));

        Mage::dispatchEvent('oyst_oneclick_model_magento_quote_sync_payment_method_after', array('quote' => $quote));

        return true;
    }
}


<?php

class Oyst_OneClick_Model_OystCheckoutManagement extends Oyst_OneClick_Model_AbstractOystManagement
{
    public function getOystCheckoutFromMagentoQuote($id)
    {
        $quote = Mage::registry('oyst_oneclick_current_quote');
        if(empty($quote) || $quote->getId() != $id) {
            $quote = $this->getMagentoQuote($id);
        }
        Mage::helper('oyst_oneclick')->handleQuoteErrors($quote);

        $shippingMethods = $this->getShippingMethodList($quote->getShippingAddress());

        $productIds = array();
        foreach ($quote->getAllItems() as $item) {
            $productIds[] = $item->getProductId();
        }

        $products = $this->getMagentoProductsById(
            $productIds,
            $quote->getStoreId()
        );

        $this->addNewsletterSubscriberToCustomer($quote->getCustomer());

        return Mage::getModel('oyst_oneclick/oystCheckout_builder')->buildOystCheckout($quote, $shippingMethods, $products);
    }

    public function syncMagentoQuoteWithOystCheckout($oystId, array $oystCheckout)
    {
        /* @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getMagentoQuote($oystCheckout['internal_id']);
        /* @var Mage_Customer_Model_Customer $customer */
        $customer = $this->getMagentoCustomer($oystCheckout['user']['email'], $quote->getStore()->getWebsiteId());
        /* @var Mage_SalesRule_Model_Coupon $coupon */
        $coupon = $this->getMagentoCoupon($oystCheckout['coupons']);
        Mage::getModel('oyst_oneclick/magentoQuote_synchronizer')->syncMagentoQuote($oystCheckout, $quote, $customer, $coupon);

        if(!$quote->isVirtual()) {
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            /* @var array $methodsAvailable */
            $methodsAvailable = $this->getShippingMethodList($quote->getShippingAddress());
            $this->resolveSetShippingMethodStrategy($quote->getShippingAddress(), $methodsAvailable, $oystCheckout['shipping']);
        }

        $quote->setTotalsCollectedFlag(false)->collectTotals();
        Mage::helper('oyst_oneclick')->handleQuoteErrors($quote);
        $quote->save();
        Mage::register('oyst_oneclick_current_quote', $quote, true);

        return $this->getOystCheckoutFromMagentoQuote($quote->getId());
    }

    /**
     * Business logic : if shipping method requested by Oyst OneClick is available after all quote recalculations,
     * then set it else use the cheapeast shipping method available.
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @param array $methodsAvailable
     * @param array $oystCheckoutShipping
     * @return $this
     */
    public function resolveSetShippingMethodStrategy(
        Mage_Sales_Model_Quote_Address $shippingAddress,
        array $methodsAvailable,
        array $oystCheckoutShipping
    )
    {
        $isRequestedShippingMethodAvailable = false;
        $shippingMethod = null;
        if ($oystCheckoutShipping['method_applied']) {
            foreach ($methodsAvailable as $methodAvailable) {
                $shippingMethod = $methodAvailable->getCode();
                if ($shippingMethod == $oystCheckoutShipping['method_applied']['reference']) {
                    $isRequestedShippingMethodAvailable = true;
                    break;
                }
            }
        }

        if (!$isRequestedShippingMethodAvailable) {
            $oystMethodsAvailable = [];
            foreach ($oystCheckoutShipping['methods_available'] as $oystMethodAvailable) {
                $oystMethodsAvailable[] = $oystMethodAvailable['reference'];
            }

            $cheapestShippingMethodAvailable = [];
            foreach ($methodsAvailable as $methodAvailable) {
                if (!in_array($methodAvailable->getCode(), $oystMethodsAvailable)) {
                    continue;
                }

                if (empty($cheapestShippingMethodAvailable)
                 || $methodAvailable->getAmount() < $cheapestShippingMethodAvailable['amount']) {
                    $cheapestShippingMethodAvailable = [
                        'amount' => $methodAvailable->getAmount(),
                        'code' => $methodAvailable->getCode(),
                    ];
                }
            }
            if (isset($cheapestShippingMethodAvailable['code'])) {
                $shippingMethod = $cheapestShippingMethodAvailable['code'];
            }
        }

        $shippingAddress->setShippingMethod($shippingMethod);
        return $this;
    }

    protected function getShippingMethodList(
        Mage_Sales_Model_Quote_Address $shippingAddress
    )
    {
        $shippingAddress->setCollectShippingRates(true);

        $rates = $shippingAddress
            ->collectShippingRates()
            ->getAllShippingRates();

        return $rates;
    }
}
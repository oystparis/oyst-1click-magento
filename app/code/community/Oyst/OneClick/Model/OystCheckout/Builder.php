<?php

class Oyst_OneClick_Model_OystCheckout_Builder extends Oyst_OneClick_Model_Common_AbstractBuilder
{
    public function buildOystCheckout(
        Mage_Sales_Model_Quote $quote,
        array $shippingMethods,
        Mage_Catalog_Model_Resource_Product_Collection $products
    )
    {
        $oystCheckout = array();

        $oystCheckout['oyst_id'] = $quote->getOystId();
        $oystCheckout['internal_id'] = $quote->getId();
        $oystCheckout['ip'] = $quote->getRemoteIp();
        $oystCheckout['currency'] = $quote->getQuoteCurrencyCode();

        $oystCheckout['user'] = $this->buildOystCheckoutUser($quote->getCustomer(), $quote);
        $oystCheckout['totals'] = $this->buildOystCheckoutTotals($quote);
        $oystCheckout['billing'] = $this->buildOystCheckoutBilling($quote->getBillingAddress());
        $oystCheckout['items'] = $this->buildOystCheckoutItemsFacade($quote->getAllItems(), $products);
        $oystCheckout['shop'] = $this->buildOystCommonShop($quote->getStore());

        if (!$quote->isVirtual()) {
            $oystCheckout['shipping'] = $this->buildOystCheckoutShipping($quote->getShippingAddress(), $shippingMethods);
            $oystCheckout['discounts'] = $this->buildOystCheckoutDiscounts($quote->getTotals());
        } else {
            $oystCheckout['discounts'] = $this->buildOystCheckoutDiscounts($quote->getTotals());
        }

        if ($quote->getCouponCode()) {
            $oystCheckout['coupons'] = $this->buildOystCheckoutCoupons($quote->getCouponCode());
        }

        Mage::dispatchEvent(
            'oyst_oneclick_model_oyst_checkout_builder_build_oyst_checkout',
            array('oyst_checkout' => $oystCheckout, 'quote' => $quote, 'shipping_methods' => $shippingMethods, 'products' => $products)
        );

        return $oystCheckout;
    }

    protected function buildOystCheckoutTotals(
        Mage_Sales_Model_Quote $quote
    )
    {
        $oystCheckoutTotals = array();

        $oystCheckoutTotals['details_tax_incl'] = $this->buildOystCheckoutTotalDetailsTaxIncl($quote);
        $oystCheckoutTotals['details_tax_excl'] = $this->buildOystCheckoutTotalDetailsTaxExcl($quote);

        return $oystCheckoutTotals;
    }

    protected function buildOystCheckoutTotalDetailsTaxIncl(
        Mage_Sales_Model_Quote $quote
    )
    {
        $oystCheckoutTotalDetails = array();

        $oystCheckoutTotalDetails['total'] = $quote->getGrandTotal();
        $oystCheckoutTotalDetails['total_discount'] = $quote->getDiscountAmount();
        $oystCheckoutTotalDetails['total_items'] = $quote->getSubtotalInclTax();
        $oystCheckoutTotalDetails['total_shipping'] = $quote->getShippingInclTax();

        return $oystCheckoutTotalDetails;
    }

    protected function buildOystCheckoutTotalDetailsTaxExcl(
        Mage_Sales_Model_Quote $quote
    )
    {
        $oystCheckoutTotalDetails = array();

        $oystCheckoutTotalDetails['total'] = $quote->getGrandTotal() - $quote->getTaxAmount();

        return $oystCheckoutTotalDetails;
    }

    protected function buildOystCheckoutItemsFacade(
        array $items,
        Mage_Catalog_Model_Resource_Product_Collection $products
    )
    {
        $oystCheckoutItems = array();

        $itemsByParentItemId = array();
        foreach ($items as $item) {
            if ($item->getData('parent_item_id')) {
                $itemsByParentItemId[$item->getData('parent_item_id')][] = $item;
            }
        }

        foreach ($items as $item) {
            if (!$item->getData('parent_item_id')) {
                $childItems = isset($itemsByParentItemId[$item->getId()]) ? $itemsByParentItemId[$item->getId()] : null;
                $oystCheckoutItems[] = $this->buildOystCheckoutItem($products, $item, $childItems);
            }
        }

        return $oystCheckoutItems;
    }

    protected function buildOystCheckoutItem(
        Mage_Catalog_Model_Resource_Product_Collection $products,
        Mage_Sales_Model_Quote_Item $item,
        array $childItems = null
    )
    {
        /* @var $item Mage_Sales_Model_Quote_Item */
        /* @var $childItems Mage_Sales_Model_Quote_Item[] */
        $product = $products->getItemById($item->getProductId());
        /* @var $oystCheckoutItem \Oyst\OneClick\Api\Data\OystCheckout\ItemInterface */
        $oystCheckoutItem = array();

        $oystCheckoutItem['name'] = $item->getName();
        $oystCheckoutItem['type'] = Mage::getSingleton('oyst_oneclick/constantsMapper')->mapMagentoProductTypeToOystCheckoutItemType($item->getProductType());
        $oystCheckoutItem['description_short'] = $product->getShortDescription();
        $oystCheckoutItem['reference'] = $item->getSku();
        $oystCheckoutItem['internal_reference'] = $item->getId();
        $oystCheckoutItem['weight'] = $product->getWeight();
        $oystCheckoutItem['quantity'] = $item->getQty();
        $oystCheckoutItem['price'] = $this->buildOystCheckoutItemPrice($item);
        $oystCheckoutItem['image'] = $product->getOystImageUrl();
        if (Mage::helper('oyst_oneclick/salesRule')->isItemFreeProduct($item)) {
            $oystCheckoutItem['oyst_display'] = Oyst_OneClick_Helper_Constants::OYST_DISPLAY_FREE;
        } else {
            $oystCheckoutItem['oyst_display'] = Oyst_OneClick_Helper_Constants::OYST_DISPLAY_NORMAL;
        }

        if (isset($childItems) && $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $this->addVariantInfosToOystCheckoutItem($oystCheckoutItem, $products, $item, $childItems[0]);
        } elseif (isset($childItems) && $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $this->addBundleInfosToOystCheckoutItem($oystCheckoutItem, $products, $childItems);
        }

        return $oystCheckoutItem;
    }

    protected function addVariantInfosToOystCheckoutItem(
        array &$oystCheckoutItem,
        Mage_Catalog_Model_Resource_Product_Collection $products,
        Mage_Sales_Model_Quote_Item $item,
        Mage_Sales_Model_Quote_Item $childItem
    )
    {
        $product = $products->getItemById($item->getProductId());
        $childProduct = $products->getItemById($childItem->getProductId());

        $attributesVariant = array();
        $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributes($product);
        foreach ($configurableAttributes as $configurableAttribute) {
            $oystCheckoutItemAttribute = array();

            $oystCheckoutItemAttribute['code'] = $configurableAttribute->getProductAttribute()->getAttributeCode();
            $oystCheckoutItemAttribute['label'] = $configurableAttribute->getProductAttribute()->getStoreLabel();
            foreach ($configurableAttribute->getProductAttribute()->getSource()->getAllOptions(false) as $option) {
                if ($option['value'] == $childProduct->getData($configurableAttribute->getProductAttribute()->getAttributeCode())) {
                    $oystCheckoutItemAttribute['value'] = $option['label'];
                }
            }

            $attributesVariant[] = $oystCheckoutItemAttribute;
        }

        $oystCheckoutItem['attributes_variant'] = $attributesVariant;
        $oystCheckoutItem['child_items'] = array($this->buildOystCheckoutItem($products, $childItem));
    }

    protected function addBundleInfosToOystCheckoutItem(
        array &$oystCheckoutItem,
        Mage_Catalog_Model_Resource_Product_Collection $products,
        array $childItems
    )
    {
        $childOystCheckoutItems = array();
        foreach($childItems as $childItem) {
            $childOystCheckoutItems[] = $this->buildOystCheckoutItem($products, $childItem);
        }
        $oystCheckoutItem['child_items'] = $childOystCheckoutItems;
    }

    protected function buildOystCheckoutItemPrice(
        Mage_Sales_Model_Quote_Item $item
    )
    {
        $oystCheckoutItemPrice = array();

        $oystCheckoutItemPrice['tax_incl'] = $item->getPriceInclTax();
        $oystCheckoutItemPrice['tax_excl'] = $item->getPrice();
        $oystCheckoutItemPrice['total_tax_incl'] = $item->getRowTotalInclTax();
        $oystCheckoutItemPrice['total_tax_excl'] = $item->getRowTotal();

        return $oystCheckoutItemPrice;
    }

    protected function buildOystCheckoutAddress(
        Mage_Sales_Model_Quote_Address $address
    )
    {
        $oystCheckoutAddress = array();

        $oystCheckoutAddress['firstname'] = $address->getFirstname();
        $oystCheckoutAddress['lastname'] = $address->getLastname();
        $oystCheckoutAddress['email'] = $address->getEmail();
        $oystCheckoutAddress['city'] = $address->getCity();
        $oystCheckoutAddress['postcode'] = $address->getPostcode();
        $oystCheckoutAddress['country'] = $this->buildOystCommonCountry($address->getCountryModel()->getCountryId(), $address->getCountryModel()->getName());
        $oystCheckoutAddress['street1'] = $address->getStreet(1);
        $oystCheckoutAddress['street2'] = $address->getStreet(2);
        $oystCheckoutAddress['phone_mobile'] = $address->getTelephone();
        $oystCheckoutAddress['phone'] = $address->getTelephone();
        $oystCheckoutAddress['company'] = $address->getCompany();

        return $oystCheckoutAddress;
    }

    protected function buildOystCheckoutBilling(
        Mage_Sales_Model_Quote_Address $billingAddress
    )
    {
        $oystCheckoutBilling = array();

        $oystCheckoutBilling['address'] = $this->buildOystCheckoutAddress($billingAddress);

        return $oystCheckoutBilling;
    }

    protected function buildOystCheckoutShipping(
        Mage_Sales_Model_Quote_Address $shippingAddress,
        array $shippingMethods
    )
    {
        $oystCheckoutShipping = array();

        $oystCheckoutShipping['address'] = $this->buildOystCheckoutAddress($shippingAddress);
        $oystCheckoutShipping['methods_available'] = $this->buildOystCheckoutShippingMethodsAvailable($shippingMethods, $shippingAddress);
        $oystCheckoutShipping['method_applied'] = $this->buildOystCheckoutShippingMethodApplied($shippingAddress->getShippingMethod(), $shippingMethods, $shippingAddress);

        foreach ($oystCheckoutShipping['methods_available'] as $idx => $methodAvailable) {
            if ($methodAvailable['reference'] == $oystCheckoutShipping['method_applied']['reference']) {
                $oystCheckoutShipping['methods_available'][$idx] = $oystCheckoutShipping['method_applied'];
            }
        }

        return $oystCheckoutShipping;
    }

    protected function buildOystCheckoutShippingMethodsAvailable(
        array $shippingMethods,
        Mage_Sales_Model_Quote_Address $shippingAddress
    )
    {
        $oystCheckoutShippingMethods = [];
        /** @var Mage_Tax_Helper_Data $coreHelper */
        $taxHelper = Mage::helper('tax');

        foreach ($shippingMethods as $shippingMethod) {
            $oystCheckoutShippingMethod = array();

            if ($taxHelper->shippingPriceIncludesTax()) {
                $oystCheckoutShippingMethod['amount_tax_incl'] = $shippingMethod->getPrice();
            } else {
                $oystCheckoutShippingMethod['amount_tax_excl'] = $shippingMethod->getPrice();
                $oystCheckoutShippingMethod['amount_tax_incl'] = $taxHelper->getShippingPrice($shippingMethod->getPrice(), true, $shippingAddress);
            }

            $oystCheckoutShippingMethod['label'] = $shippingMethod->getMethodTitle();
            $oystCheckoutShippingMethod['reference'] = $shippingMethod->getCode();

            $oystCheckoutShippingMethods[] = $oystCheckoutShippingMethod;
        }

        return $oystCheckoutShippingMethods;
    }

    protected function buildOystCheckoutShippingMethodApplied(
        $shippingMethod,
        array $shippingMethods,
        Mage_Sales_Model_Quote_Address $shippingAddress
    )
    {
        foreach ($this->buildOystCheckoutShippingMethodsAvailable($shippingMethods, $shippingAddress) as $oystCheckoutShippingMethod) {
            if ($oystCheckoutShippingMethod['reference'] == $shippingMethod) {
                $oystCheckoutShippingMethod['amount_tax_incl'] = $shippingAddress->getShippingInclTax();
                return $oystCheckoutShippingMethod;
            }
        }

        return null;
    }

    protected function buildOystCheckoutUser(
        Mage_Customer_Model_Customer $customer,
        Mage_Sales_Model_Quote $quote
    )
    {
        $oystCommonUser = array();

        if ($customer->getId()) {
            $oystCommonUser['email'] = $customer->getEmail();
            $oystCommonUser['firstname'] = $customer->getFirstname();
            $oystCommonUser['lastname'] = $customer->getLastname();
            $oystCommonUser['newsletter'] = $customer->getNewsletterSubscriber()->isSubscribed();
        } else {
            $oystCommonUser['email'] = $quote->getCustomerEmail();
            $oystCommonUser['firstname'] = $quote->getCustomerFirstname();
            $oystCommonUser['lastname'] = $quote->getCustomerLastname();
            $oystCommonUser['newsletter'] = false;
        }

        return $oystCommonUser;
    }

    protected function buildOystCheckoutDiscounts(array $totals)
    {
        $oystCheckoutDiscounts = [];

        foreach ($totals as $total) {
            if ($total->getData('code') == 'discount') {
                if ($total->getFullInfo()) {
                    foreach ($total->getFullInfo() as $salesRuleId => $discountInfo) {
                        $oystCheckoutDiscount = array();
                        $oystCheckoutDiscount['label'] = strip_tags($discountInfo['label']);
                        $oystCheckoutDiscount['amount_tax_incl'] = abs($discountInfo['amount']);

                        $oystCheckoutDiscounts[] = $oystCheckoutDiscount;
                    }
                } else {
                    $oystCheckoutDiscount = array();
                    $oystCheckoutDiscount['label'] = $total->getData('title');
                    $oystCheckoutDiscount['amount_tax_incl'] = abs($total->getData('value'));

                    $oystCheckoutDiscounts[] = $oystCheckoutDiscount;
                }
            }
        }

        return $oystCheckoutDiscounts;
    }

    protected function buildOystCheckoutCoupons($couponCode)
    {
        $oystCheckoutCoupons = array();

        $oystCheckoutCoupon = array();
        $oystCheckoutCoupon['code'] = $couponCode;

        $oystCheckoutCoupons[] = $oystCheckoutCoupon;

        return $oystCheckoutCoupons;
    }
}

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
 * Item Quote Model
 */
class Oyst_OneClick_Model_Magento_Quote_Item extends Mage_Sales_Model_Quote_Item
{
    /**
     * Specify item price (base calculation price and converted price will be refreshed too)
     *
     * @param   float $value
     *
     * @return  Mage_Sales_Model_Quote_Item_Abstract
     */
    public function setPrice($value)
    {
        $this->setBaseCalculationPrice(null);
        // Don't set converted price to 0
        // $this->setConvertedPrice(null);

        return $this->setData('price', $value);
    }

    ////////// FOR initializeQuoteItemsV2 //////////

    /** @var Mage_Sales_Model_Quote */
    private $quote = null;

    /** @var string[] API response */
    private $apiData = null;

    /** @var Mage_Catalog_Model_Product */
    private $product = null;

    /** @var Mage_GiftMessage_Model_Message */
    private $giftMessage = null;

    public function init(Mage_Sales_Model_Quote $quote, $orderResponse)
    {
        $this->quote = $quote;
        $this->apiData = $orderResponse;

        return $this;
    }

    /**
     * @return Mage_Catalog_Model_Product|null
     *
     * @throws Oyst_OneClick_Model_Exception
     */
    public function getProduct()
    {
        if (!is_null($this->product)) {
            return $this->product;
        }

        if (!is_null($this->apiData['product_reference'])) {
            $productId = $this->apiData['product_reference'];
        }

        // @TODO API EndpointShipment: need improvement change reference to product_reference
        if (!is_null($this->apiData['reference'])) {
            $productId = $this->apiData['reference'];
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->load($productId);

        if ($product->isGroupedType()) {
            $this->product = $this->getAssociatedGroupedProduct();

            if (is_null($this->product)) {
                throw new Oyst_OneClick_Model_Exception('There is no associated Products found for Grouped Product.');
            }
        } else {
            $this->product = $this->proxyItem->getProduct();

            if ($this->proxyItem->getMagentoProduct()->isBundleType()) {
                $this->product->setPriceType(Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_PARENT);
            }
        }

        // tax class id should be set before price calculation
        $this->product->setTaxClassId($this->getProductTaxClassId());

        return $this->product;
    }

    private function getAssociatedGroupedProduct()
    {
        $associatedProducts = $this->product->getAssociatedProducts();

        $associatedProductId = reset($associatedProducts);

        $product = Mage::getModel('catalog/product')
            ->setStoreId($this->quote->getStoreId())
            ->load($associatedProductId);

        return $product->getId() ? $product : null;
    }

    private function getProductTaxClassId()
    {
        $proxyOrder = $this->proxyItem->getProxyOrder();
        $itemTaxRate = $this->proxyItem->getTaxRate();
        $isOrderHasTax = $this->proxyItem->getProxyOrder()->hasTax();
        $hasRatesForCountry = Mage::getSingleton('oyst_oneclick/magento_tax_helper')
            ->hasRatesForCountry($this->quote->getShippingAddress()->getCountryId());
        $calculationBasedOnOrigin = Mage::getSingleton('oyst_oneclick/magento_tax_helper')
            ->isCalculationBasedOnOrigin($this->quote->getStore());

        if ($proxyOrder->isTaxModeNone()
            || ($proxyOrder->isTaxModeChannel() && $itemTaxRate <= 0)
            || ($proxyOrder->isTaxModeMagento() && !$hasRatesForCountry && !$calculationBasedOnOrigin)
            || ($proxyOrder->isTaxModeMixed() && $itemTaxRate <= 0 && $isOrderHasTax)
        ) {
            return Oyst_OneClick_Model_Magento_Product::TAX_CLASS_ID_NONE;
        }

        if ($proxyOrder->isTaxModeMagento()
            || $itemTaxRate <= 0
            || $itemTaxRate == $this->getProductTaxRate()
        ) {
            return $this->getProduct()->getTaxClassId();
        }

        // Create tax rule according to channel tax rate
        // ---------------------------------------
        /** @var $taxRuleBuilder Oyst_OneClick_Model_Magento_Tax_Rule_Builder */
        $taxRuleBuilder = Mage::getModel('oyst_oneclick/Magento_Tax_Rule_Builder');
        $taxRuleBuilder->buildTaxRule(
            $itemTaxRate,
            $this->quote->getShippingAddress()->getCountryId(),
            $this->quote->getCustomerTaxClassId()
        );

        $taxRule = $taxRuleBuilder->getRule();
        $productTaxClasses = $taxRule->getProductTaxClasses();
        // ---------------------------------------

        return array_shift($productTaxClasses);
    }

    private function getProductTaxRate()
    {
        /** @var $taxCalculator Mage_Tax_Model_Calculation */
        $taxCalculator = Mage::getSingleton('tax/calculation');

        $request = $taxCalculator->getRateRequest(
            $this->quote->getShippingAddress(),
            $this->quote->getBillingAddress(),
            $this->quote->getCustomerTaxClassId(),
            $this->quote->getStore()
        );
        $request->setProductClassId($this->getProduct()->getTaxClassId());

        return $taxCalculator->getRate($request);
    }

    public function getRequest()
    {
        $request = new Varien_Object();
        $request->setQty($this->proxyItem->getQty());

        // grouped and downloadable products doesn't have options
        if ($this->proxyItem->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED ||
            $this->proxyItem->getProduct()->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
        ) {
            return $request;
        }

        /** @var $magentoProduct Oyst_OneClick_Model_Magento_Product */
        $magentoProduct = Mage::getModel('oyst_oneclick/magento_product')->setProduct($this->getProduct());
        $options = $this->proxyItem->getOptions();

        if (empty($options)) {
            return $request;
        }

        if ($magentoProduct->isSimpleType()) {
            $request->setOptions($options);
        } else if ($magentoProduct->isBundleType()) {
            $request->setBundleOption($options);
        } else if ($magentoProduct->isConfigurableType()) {
            $request->setSuperAttribute($options);
        }

        return $request;
    }

    public function getGiftMessageId()
    {
        $giftMessage = $this->getGiftMessage();

        return $giftMessage ? $giftMessage->getId() : null;
    }

    public function getGiftMessage()
    {
        if (!is_null($this->giftMessage)) {
            return $this->giftMessage;
        }

        $giftMessageData = $this->proxyItem->getGiftMessage();

        if (!is_array($giftMessageData)) {
            return NULL;
        }

        $giftMessageData['customer_id'] = (int)$this->quote->getCustomerId();
        /** @var $giftMessage Mage_GiftMessage_Model_Message */
        $giftMessage = Mage::getModel('giftmessage/message')->addData($giftMessageData);

        if ($giftMessage->isMessageEmpty()) {
            return NULL;
        }

        $this->giftMessage = $giftMessage->save();

        return $this->giftMessage;
    }

    public function getAdditionalData(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        $additionalData = $this->proxyItem->getAdditionalData();

        $existAdditionalData = $quoteItem->getAdditionalData();
        $existAdditionalData = is_string($existAdditionalData) ? @unserialize($existAdditionalData) : array();

        return serialize(array_merge((array)$existAdditionalData, $additionalData));
    }
}

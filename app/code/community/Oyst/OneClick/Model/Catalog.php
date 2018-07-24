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

use Oyst\Classes\OneClickItem;
use Oyst\Classes\OneClickMerchantDiscount;
use Oyst\Classes\OneClickOrderCartEstimate;
use Oyst\Classes\OneClickShipmentCatalogLess;
use Oyst\Classes\OystCarrier;
use Oyst\Classes\OystCategory;
use Oyst\Classes\OystPrice;
use Oyst\Classes\OystProduct;

/**
 * Catalog Model
 */
class Oyst_OneClick_Model_Catalog extends Mage_Core_Model_Abstract
{
    /**
     * Supported type of product
     *
     * @var array
     */
    protected $supportedProductTypes = array(
        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
        Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
        Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    );

    /**
     * Custom attribute code
     *
     * @var array
     */
    protected $customAttributesCode = array();

    /**
     * Custom attribute code
     *
     * @var array
     */
    protected $configurableAttributesCode = array();

    /**
     * @var int
     */
    protected $configurableProductChildId = null;

    /**
     * @var bool Used to check if it's the first api call to display the button
     */
    private $isPreload;

    /**
     * Object construct
     */
    public function __construct()
    {
        if (!$this->getConfig('enable')) {
            Mage::throwException(Mage::helper('oyst_oneclick')->__('1-Click module is not enabled.'));
        }
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function getConfig($code)
    {
        return Mage::getStoreConfig("oyst/oneclick/$code");
    }

    /**
     * Catalog process from notification controller
     *
     * @param array $event
     * @param array $apiData
     *
     * @return string
     */
    public function processNotification($event, $apiData)
    {
        // Create new notification in db with status 'start'
        /** @var Oyst_OneClick_Model_Notification $notification */
        $notification = Mage::getModel('oyst_oneclick/notification');
        $notification->registerNotificationStart($event, $apiData);
        Mage::helper('oyst_oneclick')->log('Start processing notification: ' . $notification->getNotificationId());

        // Do action for each event type
        switch ($event) {
            case 'order.cart.estimate':
                $response = $this->cartEstimate($apiData);
                break;

            default:
                $response = '';
                Mage::helper('oyst_oneclick')->log('No action defined for event ' . $event);
                break;
        }

        // Save new status and result in db
        $notification
            ->setMageResponse($response)
            ->registerNotificationFinish();
        Mage::helper('oyst_oneclick')->log('End processing notification: ' . $notification->getNotificationId());

        return $response;
    }

    /**
     * Return OystProduct array.
     *
     * @param array $dataFormated
     *
     * @return OystProduct[]|array Array of OystProduct or array with errors
     */
    public function getOystProducts($dataFormated)
    {
        $oystProducts = array();

        if ($this->isPreload = $dataFormated['preload']) {
            $oystProducts[] = $this->addDummyOystProduct();
        } else {
            $this->loadCustomAttributesCode();

            /** @var Mage_Sales_Model_Quote $quote */
            $quote = $this->getQuote();

            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                if ($quoteItem->getData('is_free_product')
                 || $quoteItem->getData('oyst_skip_item_in_authorize')) {
                    continue;
                }

                if ($quoteItem->getProduct()->isConfigurable()) {
                    $parentQuoteItem = $quote->getItemsCollection()->getItemByColumnValue('parent_item_id', $quoteItem->getId());
                    $this->configurableProductChildId = $parentQuoteItem->getProductId();
                }

                $oystProducts[] = $this->format($quoteItem);

                $this->configurableProductChildId = null;
                $this->configurableAttributesCode = array();
            }
        }

        return $oystProducts;
    }

    /**
     * Transform Database Data to formatted product
     *
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     *
     * @return OystProduct
     */
    protected function format($quoteItem)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->load($quoteItem->getProductId());
        if (!empty($this->configurableProductChildId)
         && $product->isConfigurable()) {
            $childProduct = Mage::getModel('catalog/product')->load($this->configurableProductChildId);
            $this->setConfigurableAttributesCode($product);
        }

        // This price is overwritten later with taxes and others stuff
        $price = new OystPrice(1, $this->getCatalogBaseCurrencyCode());

        $oystProduct = new OystProduct($product->getId(), $product->getName(), $price, $quoteItem->getQty());

        $this->addAmount($oystProduct, $quoteItem);
        $this->addComplexAttributes($product, $oystProduct);
        $this->addImages($product, $oystProduct);
        $this->addCustomAttributesToInformations(isset($childProduct) ? $childProduct : $product, $oystProduct);
        $this->addOptionsToInformations($oystProduct, $quoteItem);

        $oystProduct->__set('reference', $product->getEntityId() . ';' . $quoteItem->getId());

        // @TODO Temporary code, waiting to allow any kind of field in product e.g. variation_reference
        if ($product->isConfigurable()) {
            $oystProduct->__set('reference', $product->getId() . ';' . $quoteItem->getId() . ';' . $this->configurableProductChildId);
            $oystProduct->__set('variation_reference', $this->configurableProductChildId);
        }

        return $oystProduct;
    }

    /**
     * Return the selected custom attributes code
     *
     * @return array
     */
    protected function loadCustomAttributesCode()
    {
        $code = 'customattributes';
        $attrsIds = explode(',', Mage::getStoreConfig("oyst/oneclick/$code"));

        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->addFieldToFilter('attribute_id', array('in' => $attrsIds))
            ->addFieldToSelect('attribute_code')
        ;

        foreach ($attributes as $attribute) {
            $this->customAttributesCode[] = $attribute->getAttributeCode();
        }
    }

    /**
     * @return array
     */
    protected function getProductAttributesCode()
    {
        $attributeCodes = array_unique(
            array_merge(
                $this->customAttributesCode,
                $this->configurableAttributesCode
            )
        );

        return $attributeCodes;
    }

    /**
     * Add price to oyst product.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     * @param Mage_Sales_Model_Quote $quote
     */
    protected function addAmount(OystProduct &$oystProduct, Mage_Sales_Model_Quote_Item $quoteItem)
    {
        $oystPriceIncludingTaxes = $this->getOystPriceFromQuoteItem($quoteItem);
        $oystProduct->__set('amountIncludingTax', $oystPriceIncludingTaxes);
    }

    protected function getOystPriceFromQuoteItem(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        /** @var Mage_Checkout_Helper_Data $checkout */
        $checkout = Mage::helper('checkout');

        $priceInclTax = round($checkout->getPriceInclTax($quoteItem), 2);

        return new OystPrice($priceInclTax, $this->getCatalogBaseCurrencyCode());
    }

    /**
     * Add complex attribute to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function addComplexAttributes(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $oystProduct->__set('url', $product->getUrlInStore(array('_ignore_category' => true)));
        $oystProduct->__set('materialized', !($product->isVirtual()) ? true : false);
    }

    /**
     * Add picture link of product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function addImages(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $attribute = Mage::getSingleton('catalog/product')->getResource()->getAttribute('media_gallery');
        $media = Mage::getResourceSingleton('catalog/product_attribute_backend_media');
        $gallery = $media->loadGallery($product, new Varien_Object(array('attribute' => $attribute)));

        $images = array();
        foreach ($gallery as $image) {
            $images[] = $product->getMediaConfig()->getMediaUrl($image['file']);
        }

        $catalogPlaceholderImage = Mage::getStoreConfig('catalog/placeholder/image_placeholder');
        if (empty($images) && !empty($catalogPlaceholderImage)) {
            $images[] = sprintf(
                '%s/placeholder/%s',
                Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl(),
                Mage::getStoreConfig('catalog/placeholder/image_placeholder')
            );
        }

        if (!empty($images)) {
            $oystProduct->__set('images', $images);
        }
    }

    /**
     * Add custom attributes to oyst product informations.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function addCustomAttributesToInformations(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $attributeCodes = $this->getProductAttributesCode();

        Mage::helper('oyst_oneclick')->log('$attributeCodes');
        Mage::helper('oyst_oneclick')->log($attributeCodes);

        $informations = array();
        $description = array();

        foreach ($attributeCodes as $attributeCode) {
            $value = '';

            if (($attribute = $product->getResource()->getAttribute($attributeCode))
                && !(null === $product->getData($attributeCode))) {
                $value = $attribute->getFrontend()->getValue($product);
            }

            if (empty($value)) {
                continue;
            }
            Mage::helper('oyst_oneclick')->log('$attributeCode: ' . $attributeCode . '  -  value: ' . $value);

            $informations[$attributeCode] = $value;
            if (in_array($attributeCode, $this->configurableAttributesCode)) {
                $description[] = $attribute->getStoreLabel() . ':'. $value;
            }
        }
        $oystProduct->__set('informations', $informations);
        $oystProduct->__set('description', implode(', ', $description));
    }

    /**
     * Add product custom options to oyst product informations.
     *
     * @param OystProduct $oystProduct
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     */
    protected function addOptionsToInformations(OystProduct &$oystProduct, Mage_Sales_Model_Quote_Item $quoteItem)
    {
        $productOptions = array();

        /* @var $helper Mage_Catalog_Helper_Product_Configuration */
        $helper = Mage::helper('catalog/product_configuration');

        // @codingStandardsIgnoreLine
        $options = $helper->getCustomOptions($quoteItem);

        foreach ($options as $option) {
            $productOptions[$option['label']] = $option['value'];
        }

        $oystProduct->__set('informations', array_merge($oystProduct->__get('informations'), $productOptions));
    }

    protected function getQuote()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::registry('oyst-quote');

        if (is_null($quote)) {
            /** @var Mage_Checkout_Model_Session $oystRelatedQuoteId */
            $oystRelatedQuoteId = Mage::getSingleton('checkout/session')->getOystRelatedQuoteId();
            $quote = Mage::getModel('sales/quote')->load($oystRelatedQuoteId);
        }

        return $quote;
    }

    /**
     * Check if product is supported
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    public function isSupportedProduct($product)
    {
        $supported = false;

        if (in_array($product->getTypeId(), $this->supportedProductTypes)) {
            $supported = true;
        }

        $transport = new Varien_Object();
        $transport->setIsSupported($supported);
        Mage::dispatchEvent('oyst_oneclick_model_catalog_is_supported_product', array(
            'product' => $product, 'transport' => $transport
        ));
        $supported = $transport->getIsSupported();

        return $supported;
    }

    /**
     * Get cart estimate.
     *
     * @param array $apiData
     *
     * @return string
     */
    public function cartEstimate($apiData)
    {
        /** @var Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder */
        $magentoQuoteBuilder = Mage::getModel('oyst_oneclick/magento_quote', $apiData);

        $magentoQuoteBuilder->syncQuoteFacade();

        // Object to format data of EndpointShipment
        $oneClickOrderCartEstimate = new OneClickOrderCartEstimate();

        $this->getCartRules($magentoQuoteBuilder, $oneClickOrderCartEstimate);

        $this->validateCoupon($apiData, $magentoQuoteBuilder, $oneClickOrderCartEstimate);

        $this->getShipments($apiData, $magentoQuoteBuilder, $oneClickOrderCartEstimate);

        $this->getCartAmount($apiData, $magentoQuoteBuilder, $oneClickOrderCartEstimate);

        $this->getCartItems($magentoQuoteBuilder, $oneClickOrderCartEstimate);

        return $oneClickOrderCartEstimate->toJson();
    }

    /**
     * Validate coupon.
     *
     * @param array $apiData
     * @param Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder
     * @param OneClickOrderCartEstimate $oneClickOrderCartEstimate
     */
    private function validateCoupon($apiData, &$magentoQuoteBuilder, &$oneClickOrderCartEstimate)
    {
        if (!isset($apiData['discount_coupon'])) {
            return;
        }

        $coupons = explode(',', $magentoQuoteBuilder->getQuote()->getCouponCode());
        if (in_array($apiData['discount_coupon'], $coupons)) {
            return;
        }

        $errorMessage = Mage::helper('sales')->__('Coupon code %s is not valid.', strip_tags($apiData['discount_coupon']));

        $oneClickOrderCartEstimate->setDiscountCouponError($errorMessage);
    }

    /**
     * Get shipments.
     *
     * @param array $apiData
     * @param Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder
     * @param OneClickOrderCartEstimate $oneClickOrderCartEstimate
     */
    protected function getShipments($apiData, &$magentoQuoteBuilder, &$oneClickOrderCartEstimate)
    {
        if ($magentoQuoteBuilder->getQuote()->isVirtual()) {
            return;
        }

        /** @var Mage_Core_Model_Store $storeId */
        $storeId = Mage::getModel('core/store')->load($apiData['order']['context']['store_id']);

        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $magentoQuoteBuilder->getQuote()->getShippingAddress();

        $rates = $address
            ->collectShippingRates()
            ->getAllShippingRates();
        $isPrimarySet = false;

        /** @var Mage_Tax_Helper_Data $coreHelper */
        $taxHelper = Mage::helper('tax');

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        $ignoredShipments = Mage::helper('oyst_oneclick/shipments')->getIgnoredShipments();
        foreach ($rates as $rate) {
            $rateData = $rate->toArray();
            try {
                if (in_array($rateData['code'], $ignoredShipments)) {
                    continue;
                }

                $price = $coreHelper->currency($rateData['price'], false, false);
                if (!$taxHelper->shippingPriceIncludesTax()) {
                    $price = $taxHelper->getShippingPrice($price, true, $address);
                }

                $rateCode = $rateData['code'];
                $mappingName = $this->getConfigMappingName($rateCode, $storeId);

                // For Webshopapps Matrix rates module
                if (strpos($rateData['code'], 'matrixrate_matrixrate') !== false) {
                    $rateCode = 'matrixrate_matrixrate';
                    $mappingName = $rateData['method_title'];
                }

                Mage::helper('oyst_oneclick')->log(
                    sprintf(
                        '%s (%s): %s',
                        trim($mappingName),
                        $rateData['code'],
                        $price
                    )
                );
                $carrierMapping = $this->getConfigMappingDelay($rateCode, $storeId);

                // This mean it's disable for 1-Click
                if ("0" === $carrierMapping || is_null($carrierMapping)) {
                    continue;
                }

                $oystPrice = new OystPrice($price, Mage::app()->getStore()->getCurrentCurrencyCode());

                $oystCarrier = new OystCarrier(
                    $rateData['code'],
                    trim($mappingName),
                    $carrierMapping
                );

                $shipment = new OneClickShipmentCatalogLess(
                    $oystPrice,
                    $this->getConfigCarrierDelay($rateCode, $storeId),
                    $oystCarrier
                );

                if ($rateCode === $this->getConfig('carrier_default')) {
                    $shipment->setPrimary(true);
                    $isPrimarySet = true;
                }

                $oneClickOrderCartEstimate->addShipment($shipment);
            } catch (Exception $e) {
                Mage::logException($e);
                continue;
            }
        }

        if (!$isPrimarySet) {
            $oneClickOrderCartEstimate->setDefaultPrimaryShipmentByType();
        }
    }

    /**
     * Get cart rules.
     *
     * @param Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder
     * @param OneClickOrderCartEstimate $oneClickOrderCartEstimate
     */
    protected function getCartRules(&$magentoQuoteBuilder, &$oneClickOrderCartEstimate)
    {
        $discountRules = array();

        if (is_null($quoteAppliedRuleIds = $magentoQuoteBuilder->getQuote()->getAppliedRuleIds())) {
            return;
        }

        if (!is_null($couponCode = $magentoQuoteBuilder->getQuote()->getCouponCode())) {
            Mage::helper('oyst_oneclick')->log('CouponCode: ' . $couponCode);
        }

        /** @var Mage_Sales_Model_Resource_Quote_Item_Collection $quoteItems */
        $quoteItems = Mage::getResourceModel('sales/quote_item_collection')
            ->setQuote($magentoQuoteBuilder->getQuote())
            ->addFieldToFilter('parent_item_id', array('null' => true))
        ;

        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quoteItems->getData() as $quoteItemData) {
            // Manage gift product
            if (0 == $quoteItemPrice = $quoteItemData['price']) {
                $freeItem = new OneClickItem(
                    $quoteItemData['product_id'],
                    new OystPrice($quoteItemPrice, $magentoQuoteBuilder->getQuote()->getBaseCurrencyCode()),
                    $quoteItemData['qty']
                );
                $freeItem->__set('title', $quoteItemData['name']);
                $oneClickOrderCartEstimate->addFreeItems($freeItem);

                continue;
            }
        }

        // Handle Classic discount rules
        foreach ($magentoQuoteBuilder->getQuote()->getTotals() as $total) {
            if ($total->getCode() != 'discount') {
                continue;
            }

            if ($total->getFullInfo()) {
                /** @var Mage_SalesRule_Model_Resource_Rule_Collection $salesRuleCollection */
                $salesRuleCollection = Mage::getModel('salesrule/rule')->getCollection();
                $salesRules = $salesRuleCollection
                    ->addFieldToFilter('rule_id', array('in' => array_keys($total->getFullInfo())))
                    ->addFieldToFilter('simple_action', array('nin' => $this->getForbiddenSalesRulesActions()))
                    ->setOrder('sort_order', $salesRuleCollection::SORT_ORDER_ASC);

                foreach ($total->getFullInfo() as $salesRuleId => $discountInfo) {
                    if (!in_array($salesRuleId, explode(',', $quoteAppliedRuleIds))) {
                        continue;
                    }

                    $salesRule = $salesRules->getItemById($salesRuleId);
                    Mage::helper('oyst_oneclick')->log('RuleId: ' . $salesRule->getRuleId() . ' - Type: ' . $salesRule->getSimpleAction());

                    $discountRules[] = array(
                        'name' => $salesRule->getName(),
                        'amount' => $discountInfo['amount']
                    );
                }
            } else {
                $discountRules[] = array(
                    'name' => $total->getTitle(),
                    'amount' => abs($total->getValue())
                );
            }
        }

        foreach ($discountRules as $discountRule) {
            $merchantDiscount = new OneClickMerchantDiscount(
                new OystPrice($discountRule['amount'], $magentoQuoteBuilder->getQuote()->getBaseCurrencyCode()),
                $discountRule['name']
            );

            $oneClickOrderCartEstimate->addMerchantDiscount($merchantDiscount);

            Mage::helper('oyst_oneclick')->log(Zend_Json::encode($merchantDiscount->toArray()));
        }
    }

    /**
     * Get cart amount.
     *
     * @param array $apiData
     * @param Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder
     * @param OneClickOrderCartEstimate $oneClickOrderCartEstimate
     */
    protected function getCartAmount($apiData, &$magentoQuoteBuilder, &$oneClickOrderCartEstimate)
    {
        // Get order amount
        $totals = $magentoQuoteBuilder->getQuote()->getTotals();
        $grandTotal = $magentoQuoteBuilder->getQuote()->getGrandTotal();

        if (!isset($totals['shipping'])) {
            $shippingAmount = 0;

            $cartEstimate = $oneClickOrderCartEstimate->toArray();
            foreach ($cartEstimate['shipments'] as $shipment) {
                if (is_null($apiData['order']['shipment']) && $shipment['primary']) {
                    $shippingAmount = Mage::helper('oyst_oneclick')->getHumanAmount($shipment['amount']['value']);
                    break;
                }

                // shipments changed in modal
                if (!is_null($apiData['order']['shipment']) &&
                    $apiData['order']['shipment']['id'] == $shipment['carrier']['id']
                ) {
                    $shippingAmount = Mage::helper('oyst_oneclick')->getHumanAmount($shipment['amount']['value']);
                    break;
                }
            }

            Mage::helper('oyst_oneclick')->log('$shippingAmount: ' . $shippingAmount);

            $grandTotal = $totals['grand_total']->getValue() + $shippingAmount;
        }

        Mage::helper('oyst_oneclick')->log('$grandTotal: ' . $grandTotal);
        $oneClickOrderCartEstimate->setCartAmount(new OystPrice($grandTotal, $magentoQuoteBuilder->getQuote()->getBaseCurrencyCode()));
    }

    /**
     * Get config for carrier delay from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function getConfigCarrierDelay($code, $storeId)
    {
        return Mage::getStoreConfig("oyst_oneclick/carrier_delay/$code", $storeId);
    }

    /**
     * Get config for carrier mapping from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function getConfigMappingDelay($code, $storeId)
    {
        return Mage::getStoreConfig("oyst_oneclick/carrier_mapping/$code", $storeId);
    }

    /**
     * Get config for carrier name from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function getConfigMappingName($code, $storeId)
    {
        return Mage::getStoreConfig("oyst_oneclick/carrier_name/$code", $storeId);
    }

    /**
     * This method is required because the API service authorize Order which is called on preload
     * does not accept an empty cart, so we have to artificially force a dummy product
     * @return OystProduct
     */
    public function addDummyOystProduct()
    {
        $price = new OystPrice(1, $this->getCatalogBaseCurrencyCode());

        return new OystProduct(1, 'Dummy Product', $price, 1);
    }

    public function getCatalogBaseCurrencyCode($storeId = null)
    {
        return Mage::app()->getStore($storeId)->getBaseCurrencyCode();
    }

    protected function getForbiddenSalesRulesActions()
    {
        $transport = new Varien_Object();
        $transport->setForbiddenSalesRulesActions(array('add_gift'));
        Mage::dispatchEvent('oyst_oneclick_model_catalog_get_forbidden_sales_rules_actions', array(
            'transport' => $transport
        ));
        return $transport->getForbiddenSalesRulesActions();
    }

    /**
     * Get cart items.
     *
     * @param Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder
     * @param OneClickOrderCartEstimate $oneClickOrderCartEstimate
     */
    protected function getCartItems($magentoQuoteBuilder, $oneClickOrderCartEstimate)
    {
        $oystItems = array();

        foreach ($magentoQuoteBuilder->getQuote()->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $oystPriceIncludingTaxes = $this->getOystPriceFromQuoteItem($item);

            $reference = null;

            if (in_array($item->getProductType(), array(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE))) {
                $childItem = null;
                foreach ($magentoQuoteBuilder->getQuote()->getAllItems() as $tmpItem) {
                    if ($tmpItem->getParentItemId() == $item->getId()) {
                        $childItem = $tmpItem;
                        break;
                    }
                }
                $reference = $item->getProductId() . ';' . $item->getId() . ';' . $childItem->getProductId();
            } else {
                $reference = $item->getProductId() . ';' . $item->getId();
            }

            $oystItem = new OneClickItem($reference, $oystPriceIncludingTaxes, $item->getQty());

            $oystPriceForCrossedOutAmount = $this->getOystPriceFromQuoteItem($item);
            $oystPriceForCrossedOutAmount->setValue($item->getProduct()->getPrice());
            if ($oystPriceIncludingTaxes->getValue() < $oystPriceForCrossedOutAmount->getValue()) {
                $oystItem->__set('crossedOutAmount', $oystPriceForCrossedOutAmount);
                $oystItem->__set('message', Mage::helper('oyst_oneclick')->__('Oyst has recognized you as a customer, so you benefit a promotion on this product.'));
            }

            $oystItems[] = $oystItem;
        }

        $oneClickOrderCartEstimate->setItems($oystItems);
    }

    protected function setConfigurableAttributesCode(Mage_Catalog_Model_Product $product)
    {
        foreach (Mage::getModel('catalog/product_type_configurable')->getConfigurableAttributes($product) as $attribute) {
            $this->configurableAttributesCode[] = $attribute->getData('product_attribute')->getAttributeCode();
        }
    }
}

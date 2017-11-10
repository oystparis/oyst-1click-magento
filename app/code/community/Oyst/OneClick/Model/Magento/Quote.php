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
 * Quote Model
 */
class Oyst_OneClick_Model_Magento_Quote
{
    protected $paymentMethod = Oyst_OneClick_Model_Payment_Method_Oneclick::PAYMENT_METHOD_NAME;

    /** @var Mage_Sales_Model_Quote */
    private $quote = null;

    /** @var string[] API response */
    private $apiData = null;

    /** @var array */
    private $rowTotalOyst = array();

    public function __construct($orderResponse)
    {
        $this->apiData = $orderResponse;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * Builds the quote object (from Oyst order), which then can be converted to magento order
     *
     * @return array
     */
    public function buildQuote()
    {
        try {
            // do not change invoke order
            // ---------------------------------------
            $this->initializeQuote();
            $this->initializeCustomer();
            $this->initializeAddresses();

            $this->configureTaxCalculation();

            $this->initializeCurrency();
            $this->initializeQuoteItems();
            $this->initializePaymentMethodData();

            $this->quote->collectTotals()->save();

            // Not managed by API
            //$this->prepareOrderNumber();
        } catch (Exception $e) {
            $this->quote->setIsActive(false)->save();
            Mage::helper('oyst_oneclick')->log('Error build quote: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param int $storeId
     */
    private function initializeQuote($storeId = null)
    {
        $this->quote = Mage::getModel('sales/quote');

        $this->quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);

        $store = Mage::app()->getStore($storeId);

        $this->quote->setStore($store);
        $this->quote->getStore()->setCurrentCurrencyCode($this->apiData['order_amount']['currency']);
        $this->quote->setRemoteIp($this->apiData['context']['remote_addr']);

        $this->quote->setIsMultiShipping(false);
        $this->quote->setIsSuperMode(true);
        $this->quote->setCreatedAt($this->apiData['created_at']);
        $this->quote->setUpdatedAt($this->apiData['created_at']);
        $this->quote->setOystOrderId($this->apiData['id']);

        $this->quote->save();

        /** @var Mage_Checkout_Model_Session */
        Mage::getSingleton('checkout/session')->replaceQuote($this->quote);
    }

    private function initializeCustomer()
    {
        // Already customer ; Check by website
        if ($customer = $this->getCustomerByEmailAndWebsite($this->apiData['user']['email'])) {
            if ($customer instanceof Mage_Customer_Model_Customer) {
                $this->quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
                $this->quote->assignCustomer($customer);
                $this->quote->setCustomer($customer);
            }
        }

        // New guest
        if (!$customer) {
            $firstname = !empty($this->apiData['user']['first_name']) ? $this->apiData['user']['first_name'] : '';
            $lastname = !empty($this->apiData['user']['last_name']) ? $this->apiData['user']['last_name'] : '';
            $email = !empty($this->apiData['user']['email']) ?
                $this->apiData['user']['email'] :
                Mage::getStoreConfig('trans_email/ident_general/email');
            $this->quote->setCustomerFirstname($firstname);
            $this->quote->setCustomerLastname($lastname);
            $this->quote->setCustomerEmail($email);
            $this->quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            $this->quote->setCustomerIsGuest(true);
            $this->quote->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        }

        $this->quote->save();
    }

    /**
     * Get the customer by email
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return bool|Mage_Customer_Model_Customer
     */
    private function getCustomerByEmailAndWebsite($email, $websiteId = null)
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
     * Consider all address info only from API same as New guest
     */
    private function initializeAddresses()
    {
        // Bypass some info for EndpointShipment
        if (isset($this->apiData['shipment'])) {
            $carrier = explode('_', $this->apiData['shipment']['carrier']['id']);
        }

        /** @var Mage_Sales_Model_Quote_Address $billingAddress */
        $billingAddress = $this->quote->getBillingAddress();
        $billingInfoFormated = $this->getAddressData($billingAddress);
        $billingAddress->addData($billingInfoFormated);
        $billingAddress->implodeStreetAddress();
        if (isset($this->apiData['shipment'])) {
            $billingAddress->setLimitCarrier($carrier[0]);
            $billingAddress->setShippingMethod($this->apiData['shipment']['carrier']['id']); // bad api naming
        }
        $billingAddress->setSaveInAddressBook(false);
        $billingAddress->setShouldIgnoreValidation(true);
        $billingAddress->setCollectShippingRates(true);


        /** @var Mage_Sales_Model_Quote_Address $shippingAddress */
        $shippingAddress = $this->quote->getShippingAddress();
        $shippingInfoFormated = $this->getAddressData($shippingAddress);
        $shippingAddress->setSameAsBilling(0); // maybe just set same as billing?
        $shippingAddress->addData($shippingInfoFormated);
        $shippingAddress->implodeStreetAddress();
        if (isset($this->apiData['shipment'])) {
            $shippingAddress->setLimitCarrier($carrier[0]);
            $shippingAddress->setShippingMethod($this->apiData['shipment']['carrier']['id']); // bad api naming
        }
        $shippingAddress->setSaveInAddressBook(false);
        $shippingAddress->setShouldIgnoreValidation(true);
        $shippingAddress->setCollectShippingRates(true);
    }

    /**
     * Transform Magento address to formatted array
     *
     * @return array
     */
    private function getAddressData()
    {
        $customerAddress = $this->apiData['user']['address'];

        $country = Mage::getModel('directory/country')->loadByCode($customerAddress['country']);

        $street = isset($customerAddress['street']) ? $customerAddress['street'] : '';
        $street .= isset($customerAddress['complementary']) ? ' ' . $customerAddress['complementary'] : '';

        $formattedAddress = array(
            'email' => $this->apiData['user']['email'],
            'firstname' => isset($customerAddress['first_name']) ? $customerAddress['first_name'] : '',
            'lastname' => isset($customerAddress['last_name']) ? $customerAddress['last_name'] : '',
            'telephone' => $this->apiData['user']['phone'],
            'street' => $street,
            'postcode' => isset($customerAddress['postcode']) ? $customerAddress['postcode'] : '',
            'city' => isset($customerAddress['city']) ? $customerAddress['city'] : '',
            'region' => isset($customerAddress['city']) ? $customerAddress['city'] : '',
            'region_id' =>  null,
            'country_id' => $country->getIso2Code(),
            'company' => isset($customerAddress['company_name']) ? $customerAddress['company_name'] : '',
            'name' => (isset($customerAddress['label']) && 'N/A' != $customerAddress['label']) ? $customerAddress['label'] : '',
            'save_in_address_book' => 0,
        );

        return $formattedAddress;
    }

    private function configureTaxCalculation()
    {
        // This prevents customer session initialization (which affects cookies)
        // see Mage_Tax_Model_Calculation::getCustomer()
        Mage::getSingleton('tax/calculation')->setCustomer($this->quote->getCustomer());
    }

    private function initializeCurrency()
    {
        // @TODO : remove this if all is ok with apiData
        // Default API currency
        $currentCurrency = 'EUR';

        if ($this->apiData['order_amount']) {
            $currentCurrency = $this->apiData['order_amount']['currency'];
        }

        $this->quote->getStore()->setCurrentCurrencyCode($currentCurrency);
    }

    private function initializeQuoteItems()
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');

        // Check if store include tax (Product and shipping cost)
        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($this->quote->getStore());
        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($this->quote->getStore());

        // Add product in quote
        $this->addOystProducts(
            $this->apiData['items'],
            $priceIncludeTax
        );

        // @TODO EndpointShipment: improve this bad hack
        if (isset($this->apiData['shipment'])) {
            // Get shipping cost with tax
            $shippingCost = $helper->getHumanAmount($this->apiData['shipment']['amount']['value']);
        }
        // If shipping cost not include tax -> get shipping cost without tax
        if (!$shippingIncludeTax) {
            $basedOn = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON, $this->quote->getStore());
            $countryId = (Mage_Sales_Model_Quote_Address::TYPE_SHIPPING == $basedOn)
                ? $this->quote->getCountryId()
                : $this->quote->getCountryId();
            $shippingTaxClass = Mage::getStoreConfig(
                Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                $this->quote->getStore()
            );
            $taxCalculator = Mage::getModel('tax/calculation');
            $taxRequest = new Varien_Object();
            $taxRequest->setCountryId($countryId)
                ->setCustomerClassId($this->quote->getCustomer()->getTaxClassId())
                ->setProductClassId($shippingTaxClass);
            $taxRate = (float)$taxCalculator->getRate($taxRequest);

            if (isset($shippingCost)) {
                $taxShippingCost = (float)$taxCalculator->calcTaxAmount($shippingCost, $taxRate, true);
                $shippingCost = $shippingCost - $taxShippingCost;
            }
        }

        // @TODO EndpointShipment: improve this bad hack
        if (isset($this->apiData['shipment'])) {
            // Set shipping price and shipping method for current order
            $this->quote->getShippingAddress()
                ->setShippingPrice($shippingCost)
                ->setShippingMethod($this->apiData['shipment']['carrier']['id']);
        }

        $this->quote->setTotalsCollectedFlag(false);
        $this->quote->save();
        $this->clearQuoteItemsCache();
        // Collect totals
        $this->quote->collectTotals();

        // Re-adjust cents for item quote
        // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
        // @TODO EndpointShipment: improve this bad hack
        if (isset($this->apiData['shipment']) && !$priceIncludeTax && isset($this->apiData['order_amount']['value'])) {
            if ($this->quote->getGrandTotal() != $helper->getHumanAmount($this->apiData['order_amount']['value'])) {
                $quoteItems = $this->quote->getAllItems();

                foreach ($quoteItems as $item) {
                    $rowTotalOyst = $this->rowTotalOyst[$item->getProduct()->getId()];
                    if ($rowTotalOyst != $item->getRowTotalInclTax()) {
                        $diff = $rowTotalOyst - $item->getRowTotalInclTax();
                        $item->setPriceInclTax($item->getPriceInclTax() + ($diff / $item->getQty()));
                        $item->setBasePriceInclTax($item->getPriceInclTax());
                        $item->setPrice($item->getPrice() + ($diff / $item->getQty()));
                        $item->setOriginalPrice($item->getPrice());
                        $item->setRowTotal($item->getRowTotal() + $diff);
                        $item->setBaseRowTotal($item->getRowTotal());
                        $item->setRowTotalInclTax((float)$rowTotalOyst);
                        $item->setBaseRowTotalInclTax($item->getRowTotalInclTax());
                    }
                }
            }
        }

        $this->quote->save();
    }

    /**
     * Add products from API to current quote
     *
     * @param $items product list to be added
     * @param boolean $priceIncludeTax
     *
     * @return  Oyst_Sync_Model_Quote
     */
    private function addOystProducts($items, $priceIncludeTax = true)
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');

        foreach ($items as $item) {
            if (isset($item['product_reference'])) {
                $productId = $item['product_reference'];
            }
            // @TODO EndpointShipment: need improvement
            if (isset($item['reference'])) {
                $productId = $item['reference'];
            }
            // @TODO EndpointShipment: to remove with AuthorizeV2 / order.cart.estimate
            if (isset($item['variation_reference'])) {
                $configurableProductChildId = $item['variation_reference'];
                /** @var Mage_Catalog_Model_Product $product */
                $configurableProductChild = Mage::getModel('catalog/product')->load($configurableProductChildId);
            }

            /** @var Mage_Catalog_Model_Product $product */
            if ($product = Mage::getModel('catalog/product')->load($productId)) {
                // Get unit price with tax for order.v2.new
                if (isset($item['product_reference'])) {
                    $price = $product->getPrice();
                    $this->rowTotalOyst[$product->getId()] = $price * $item['quantity'];
                }
                // @TODO EndpointShipment: need improvement
                if (isset($item['reference'])) {
                    $price = $product->getPrice();
                    $this->rowTotalOyst[$product->getId()] = $price * $item['quantity'];
                }
                // @TODO EndpointShipment: to remove with AuthorizeV2 / order.cart.estimate
                if (isset($item['variation_reference'])) {
                    $price = $configurableProductChild->getPrice();
                    $this->rowTotalOyst[$configurableProductChildId] = $price * $item['quantity'];
                }

                // If price not include tax -> get shipping cost without tax
                if (!$priceIncludeTax) {
                    $basedOn = Mage::getStoreConfig(
                        Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON,
                        $this->quote->getStore()
                    );
                    $countryId = (Mage_Sales_Model_Quote_Address::TYPE_SHIPPING == $basedOn)
                        ? $this->quote->getShippingAddress()->getCountryId()
                        : $this->quote->getBillingAddress()->getCountryId();

                    $taxCalculator = Mage::getModel('tax/calculation');
                    $taxRequest = new Varien_Object();
                    $taxRequest->setCountryId($countryId)
                        ->setCustomerClassId($this->quote->getCustomer()->getTaxClassId())
                        ->setProductClassId($product->getTaxClassId());
                    $taxRate = $taxCalculator->getRate($taxRequest);
                    $tax = (float)$taxCalculator->calcTaxAmount($price, $taxRate, true);
                    $price = $price - $tax;
                }
//                $product->setPrice($price);
//                $product->setSpecialPrice($price);
//                $product->setFinalPrice($price);

                $request = array('qty' => $item['quantity']);

                if (Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE == $product->getTypeId()) {
                    /** @var Mage_Downloadable_Model_Product_Type $links */
                    $links = Mage::getModel('downloadable/product_type')->getLinks($product);
                    $linkId = 0;
                    foreach ($links as $link) {
                        $linkIds[] = $link->getId();
                    }

                    $request = array_merge(
                        $request,
                        array(
                            'links' => $linkIds,
                            'is_downloadable' => true,
                            'real_product_type' => Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
                        )
                    );
                }

/* REMOVE to work only with variation_reference
                // For configurable on order.v2.new
                if (isset($item['product']) && isset($item['product']['variations']) &&
                    isset($item['product']['variations']['informations']) &&
                    !is_null($item['product']['variations']['informations'])
                ) {
                    $options = array();
                    $titleAttributes = array();
                    foreach ($item['product']['variations']['informations'] as $attributeCode => $attributeValue) {
                        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);

                        $attributeCodeId = $attribute->getId();
                        if (!is_null($attributeCodeId) &&
                            !is_null($optionId = $attribute->getSource()->getOptionId($attributeValue))
                        ) {
                            $options[$attributeCodeId] = $optionId;
                        }

                        $titleAttributes[] = $attributeValue;
                    }

                    // Option "import with product's title from Oyst seen in modal"
                    if (Mage::getStoreConfig('oyst/oneclick/orders/title', $this->quote->getStore())) {
                        $product->setName($item['product']['title'] . ' - ' . implode(' - ', $titleAttributes));
                    }

                    $request = array_merge($request, array('super_attribute' => $options));
                }
*/
                if (isset($item['variation_reference'])) {
                    // Collect options applicable to the configurable product
                    $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

                    $options = array();
                    foreach ($productAttributeOptions as $productAttribute) {
                        $allValues = array_column($productAttribute['values'], 'value_index');
                        $currentProductValue = $configurableProductChild->getData($productAttribute['attribute_code']);
                        if (in_array($currentProductValue, $allValues)) {
                            $options[$productAttribute['attribute_id']] = $currentProductValue;
                        }
                    }

                    // hack: for child product weight was not load
                    $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'weight');
                    $options[$attribute->getId()] = $configurableProductChild->getWeight();

                    $request = array_merge($request, array('super_attribute' => $options));
                }

                $this->quote->addProduct($product, new Varien_Object($request));
            }
        }
    }

    private function initializePaymentMethodData()
    {
        /** @var Oyst_OneClick_Model_Payment_Method_Oneclick $paymentMethod */
        $paymentMethod = Mage::getModel('oyst_oneclick/payment_method_oneclick');

        $this->quote->getPayment()->importData(array('method' => $paymentMethod->getCode()));
    }

    /**
     * Mage_Sales_Model_Quote_Address caches items after each collectTotals call. Some extensions calls collectTotals
     * after adding new item to quote in observers. So we need clear this cache before adding new item to quote.
     */
    private function clearQuoteItemsCache()
    {
        foreach ($this->quote->getAllAddresses() as $address) {
            /** @var $address Mage_Sales_Model_Quote_Address */
            $address->unsetData('cached_items_all');
            $address->unsetData('cached_items_nominal');
            $address->unsetData('cached_items_nonominal');
        }
    }

    private function prepareOrderNumber()
    {
        $orderNumber = $this->quote->getReservedOrderId();
        if (empty($orderNumber)) {
            $orderNumber = $this->quote->getResource()->getReservedOrderId($this->quote);
        }

        if ($this->quote->getResource()->isOrderIncrementIdUsed($orderNumber)) {
            $orderNumber = $this->quote->getResource()->getReservedOrderId($this->quote);
        }

        $this->quote->setReservedOrderId($orderNumber);
        $this->quote->save();
    }

    /**
     * WIP
     */
    private function initializeQuoteItemsV2()
    {
        foreach ($this->apiData['items'] as $item) {

            $this->clearQuoteItemsCache();

            /** @var $quoteItemBuilder Oyst_OneClick_Model_Magento_Quote_Item */
            $quoteItemBuilder = Mage::getModel('oyst_oneclick/magento_quote_item');
            $quoteItemBuilder->init($this->quote, $item);

            $product = $quoteItemBuilder->getProduct();
            $request = $quoteItemBuilder->getRequest();

            // ---------------------------------------
            $productOriginalPrice = (float)$product->getPrice();

            $price = $item->getBasePrice();
            $product->setPrice($price);
            $product->setSpecialPrice($price);
            // ---------------------------------------

            // see Mage_Sales_Model_Observer::substractQtyFromQuotes
            $this->quote->setItemsCount($this->quote->getItemsCount() + 1);
            $this->quote->setItemsQty((float)$this->quote->getItemsQty() + $request->getQty());

            $result = $this->quote->addProduct($product, $request);
            if (is_string($result)) {
                throw new Oyst_OneClick_Model_Exception($result);
            }

            $quoteItem = $this->quote->getItemByProduct($product);

            if ($quoteItem !== false) {
                $weight = $product->getTypeInstance()->getWeight();
                if ($product->isConfigurable()) {
                    // hack: for child product weight was not load
                    $simpleProductId = $product->getCustomOption('simple_product')->getProductId();
                    $weight = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                        $simpleProductId, 'weight', 0
                    );
                }

                $quoteItem->setStoreId($this->quote->getStoreId());
                $quoteItem->setOriginalCustomPrice($item->getPrice());
                $quoteItem->setOriginalPrice($productOriginalPrice);
                $quoteItem->setBaseOriginalPrice($productOriginalPrice);
                $quoteItem->setWeight($weight);
                $quoteItem->setNoDiscount(1);

                $giftMessageId = $quoteItemBuilder->getGiftMessageId();
                if (!empty($giftMessageId)) {
                    $quoteItem->setGiftMessageId($giftMessageId);
                }

                $quoteItem->setAdditionalData($quoteItemBuilder->getAdditionalData($quoteItem));
            }
        }
    }
}

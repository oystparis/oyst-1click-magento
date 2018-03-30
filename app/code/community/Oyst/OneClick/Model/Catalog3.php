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
use Oyst\Classes\OneClickShipmentCalculation;
use Oyst\Classes\OneClickShipmentCatalogLess;
use Oyst\Classes\OneClickStock;
use Oyst\Classes\OystCarrier;
use Oyst\Classes\OystCategory;
use Oyst\Classes\OystPrice;
use Oyst\Classes\OystProduct;

/**
 * Catalog Model
 */
class Oyst_OneClick_Model_Catalog3 extends Mage_Core_Model_Abstract
{
    /**
     * @var bool
     */
    private $isPreload;

    /**
     * @var bool
     */
    private $priceIncludesTax;

    /**
     * @var array
     */
    private $products = array();

    /**
     * @var string
     */
    private $query;

    /**
     * @var Mage_Tax_Helper_Data
     */
    private $taxHelper;

    /**
     * @var Mage_Weee_Model_Tax
     */
    private $weeTaxModel;

    /**
     * @var Mage_CatalogRule_Model_Rule
     */
    private $catalogRuleModel;

    /**
     * Supported type of product.
     *
     * @var array
     */
    protected $supportedProductTypes = array(
        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
        //Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
        //Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        //Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    );

    /**
     * Object constructor.
     */
    public function __construct()
    {
        if (!Mage::getStoreConfig('oyst/oneclick/enable')) {
            Mage::throwException(Mage::helper('oyst_oneclick')->__('1-Click module is not enabled.'));
        }

        $this->taxHelper = Mage::helper('tax');
        $this->weeTaxModel = Mage::getModel('wee/tax');
        $this->catalogRuleModel = Mage::getModel('catalogrule/rule');

        parent::__construct();
    }

    /**
     * Start shipping class
     *
     */

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
        $notification->setData(array(
            'event' => $event,
            'oyst_data' => Zend_Json::encode($apiData),
            'status' => 'start',
            'created_at' => Mage::getModel('core/date')->gmtDate(),
            'executed_at' => Mage::getModel('core/date')->gmtDate(),
        ));
        $notification->save();
        Mage::helper('oyst_oneclick')->log('Start processing notification: ' . $notification->getNotificationId());

        // Do action for each event type
        switch ($event) {
            case 'order.cart.estimate':
                $response = $this->cartEstimate($apiData);
                break;

            // Reduce qty in order or cancel booking
            case 'order.stock.released':
                $response = $this->stockReleased($apiData);
                break;

            // Increase qty in order
            case 'order.stock.book':
                $response = $this->stockBook($apiData);
                break;

            default:
                $response = '';
                Mage::helper('oyst_oneclick')->log('No action defined for event ' . $event);
                break;
        }

        // Save new status and result in db
        $notification->setStatus('finished')
            ->setMageResponse($response)
            ->setExecutedAt(Mage::getSingleton('core/date')->gmtDate())
            ->save();
        Mage::helper('oyst_oneclick')->log('End processing notification: ' . $notification->getNotificationId());

        return $response;
    }

    /**
     * Get the shipping methods and apply cart rule
     *
     * @param $data
     *
     * @return string
     */
    public function cartEstimate($apiData)
    {
        /** @var Mage_Core_Model_Store $storeId */
        $storeId = Mage::getModel('core/store')->load($apiData['order']['context']['store_id']);

        /** @var Oyst_OneClick_Model_Magento_Quote $magentoQuoteBuilder */
        $magentoQuoteBuilder = Mage::getModel('oyst_oneclick/magento_quote', $apiData);
        $magentoQuoteBuilder->buildQuote();

        // Object to format data of EndpointShipment
        $oneClickShipmentCalculation = new OneClickShipmentCalculation();

        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $magentoQuoteBuilder->getQuote()->getShippingAddress();

        $rates = $address
            ->collectShippingRates()
            ->getShippingRatesCollection();
        $isPrimarySet = false;

        $taxHelper = Mage::helper('tax');
        $coreHelper = Mage::helper('core');

        $ignoredShipments = Mage::helper('oyst_oneclick/shipments')->getIgnoredShipments();
        foreach ($rates as $rate) {
            try {
                if (in_array($rate->getCode(), $ignoredShipments)) {
                    continue;
                }

                $price = $coreHelper->currency($rate->getPrice(), true, false);
                if (!$taxHelper->shippingPriceIncludesTax()) {
                    $price = $taxHelper->getShippingPrice($price, true, $address);
                }

                $rateCode = $rate->getCode();
                $mappingName = $this->getConfigMappingName($rateCode, $storeId);

                // For Webshopapps Matrix rates module
                if (strpos($rate->getCode(), 'matrixrate_matrixrate') !== false) {
                    $rateCode = 'matrixrate_matrixrate';
                    $mappingName = $rate->getMethodTitle();
                }

                Mage::helper('oyst_oneclick')->log(
                    sprintf('%s (%s): %s',
                        trim($mappingName),
                        $rate->getCode(),
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
                    $rate->getCode(),
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

                $oneClickShipmentCalculation->addShipment($shipment);
            } catch (Exception $e) {
                Mage::logException($e);
                continue;
            }
        }

        if (!$isPrimarySet) {
            $oneClickShipmentCalculation->setDefaultPrimaryShipmentByType();
        }

        $magentoQuoteBuilder->getQuote()->setIsActive(false)->save();

        return $oneClickShipmentCalculation->toJson();
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
     * End shipping class
     *
     * /










    /**
     * Check if product is supported.
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    public function isSupportedProduct($product)
    {
        $supported = false;

        if ($product->getIsOneclickActiveOnProduct()) {
            return $supported;
        }

        if (in_array($product->getTypeId(), $this->supportedProductTypes)) {
            $supported = true;
        }

        return $supported;
    }

    /**
     * Check items stock.
     *
     * @param array $data
     *
     * @return bool
     */
    private function checkItemsQty($data)
    {
        $stockItems = Mage::getModel('cataloginventory/stock_item')
            ->getCollection()
            ->addFieldToFilter('product_id', array('in' => array_keys($data)));

        $itemValues = $statusValues = array();
        $websiteId = Mage::app()->getWebsite()->getId();

        foreach ($stockItems as $stockItem) {
            $checkQuoteItemQty = $stockItem->checkQuoteItemQty($data[$stockItem->getProductId()], $stockItem->getQty());

            $qty = $stockItem->getQty() - $data[$stockItem->getProductId()];
            $itemValues[] = '(' . $stockItem->getItemId() . ', ' . $qty . ')';
            $statusValues[] = '(' . $stockItem->getProductId(). ', ' . $websiteId . ', 1, ' . $qty . ')';

            if ($checkQuoteItemQty->getData('has_error')) {
                return false;
            }
        }

        // @TODO Check for magento functionality. This is just in case nothing works.
        $this->query = "INSERT INTO cataloginventory_stock_item (item_id, qty) VALUES " . implode(',', $itemValues) .
            " ON DUPLICATE KEY UPDATE qty=VALUES(qty);";
        $this->query .= "INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty) VALUES " .
            implode(',', $statusValues). " ON DUPLICATE KEY UPDATE qty=VALUES(qty);";

        return true;
    }

    /**
     * Get products.
     *
     * @param array $data
     *
     * @return mixed|null
     */
    private function getProducts($data)
    {
        $childrenIds = $stockFilter = array();

        foreach ($data as $item) {
            $index = 'configurableProductChildId';

            if (array_key_exists('configurableProductChildId', $item)) {
                $childrenIds[$item['configurableProductChildId']] = $item['productId'];
                $index = 'configurableProductChildId';
            }

            $this->products[$item['productId']]['quantity'] = $stockFilter[$item[$index]] = $item['quantity'];
        }

        if (!$this->checkItemsQty($stockFilter)) {
            return null;
        }

        $products = $this->getProductCollection(array_keys($this->products));
        $childProducts = $this->getProductCollection(array_keys($childrenIds));

        foreach ($childProducts as $childProduct) {
            $this->products[$childrenIds[$childProduct->getId()]]['childProduct'] = $childProduct;
        }

        return $products;
    }

    /**
     * Get product collection.
     *
     * @param array $data
     *
     * @return mixed
     */
    private function getProductCollection($data)
    {
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->getFieldToFilter('entity_id', array('in' => $data))
            ->addAttributeToSelect('*');

        return $products;
    }

    /**
     * Generate oystproducts.
     *
     * @param $dataFormatted
     *
     * @return array
     */
    public function getOystProducts($dataFormatted)
    {
        $productsFormatted = array();
        $this->isPreload = filter_var($dataFormatted['preload'], FILTER_VALIDATE_BOOLEAN);
        $this->priceIncludesTax = $this->taxHelper->priceIncludesTax();

        if (!$products = $this->getProducts(Zend_Json::decode($dataFormatted['products']))) {
            return $productsFormatted;
        }

        foreach ($products as $product) {
            $productsFormatted[] = $this->format($product);
        }

        //$this->runQuery();

        return $productsFormatted;
    }

    /**
     * Book stock units.
     */
    private function runQuery()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->query($this->query);
    }

    /**
     * Format product into oystproduct.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param null $qty
     *
     * @return OystProduct
     */
    private function format(Mage_Catalog_Model_Product $product, $qty = null)
    {
        $price = new OystPrice(1, 'EUR');
        $qty = is_null($qty) ? 1 : $qty;

        $oystProduct = new OystProduct($product->getEntityId(), $product->getName(), $price, $qty);
        $this->addAmount($product, $oystProduct);

        if ($image = Mage::helper('catalog/image')->init($product, 'thumbnail')->__toString()) {
            $oystProduct->__set('images', array($image));
        }

        // @TODO Add product variations.

        return $oystProduct;
    }

    /**
     * Add amount to oystproduct.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    private function addAmount(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        // @TODO Add price calculation for grouped product.

        if (!$this->catalogRuleModel->loadProductRules($product)) {
            switch ($product->getTypeId()) {
                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                    $this->setupConfigurableProduct($product);
                    break;
                case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                    break;
            }
        }

        $finalPrice = $product->getFinalPrice();

        if (!$this->priceIncludesTax) {
            $oystPriceExcludingTaxes = new OystPrice(
                $this->taxHelper->getPrice($product, $finalPrice, false),
                'EUR'
            );

            $oystProduct->__set('amount_excluding_taxes', $oystPriceExcludingTaxes->toArray());
        }

        if (Mage::getStoreConfig(Mage_Weee_Helper_Data::XML_PATH_FPT_ENABLED)) {
            $finalPrice += $this->weeTaxModel->getWeeeAmount($product);
        }

        $oystProduct->__set(
            'price-including-tax',
            new OystPrice(round($finalPrice, 2), 'EUR')
        );
    }

    /**
     * Select configurable option.
     *
     * @param Mage_Catalog_Model_Product $product
     */
    private function setupConfigurableProduct(Mage_Catalog_Model_Product &$product)
    {
        $configurableAttributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $optionValues = array();

        foreach ($configurableAttributes as $configurableAttribute) {
            $attributeCode = $configurableAttribute['attribute_code'];
            $attributeId = $configurableAttribute['attribute_id'];
            $optionValue = $this->products[$product->getId()]['childProduct']->getData($attributeCode);
            $optionValues[$attributeId] = $optionValue;
        }

        $product->addCustomOption('attributes', serialize($optionValues));
    }

    /**
     * Release booked products.
     *
     * @param $apiData
     */
    public function stockReleased($apiData)
    {
        try {
            if (!isset($apiData['products'])) {
                throw new \InvalidArgumentException(Mage::helper('oyst_oneclick')->__('Products info is missing'));
            }

            $products = $this->getReleasedProducts($apiData);

            $stockItems = Mage::getModel('cataloginventory/stock_item')
                ->getCollection()
                ->addFieldToFilter('product_id', array('in' => array_keys($products)));

            $itemValues = $statusValues = array();
            $websiteId = Mage::app()->getWebsite()->getId();

            foreach ($stockItems as $stockItem) {
                $qty = $stockItem->getQty() + $products[$stockItem->getProductId()];
                $itemValues[] = '(' . $stockItem->getItemId() . ', ' . $qty . ')';
                $statusValues[] = '(' . $stockItem->getProductId(). ', ' . $websiteId . ', 1, ' . $qty . ')';
            }

            // @TODO Check for magento functionality. This is just in case nothing works.
            $this->query = "INSERT INTO cataloginventory_stock_item (item_id, qty) VALUES " . implode(',', $itemValues) .
                " ON DUPLICATE KEY UPDATE qty=VALUES(qty);";
            $this->query .= "INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty) VALUES " .
                implode(',', $statusValues). " ON DUPLICATE KEY UPDATE qty=VALUES(qty);";

            //$this->runQuery();
        } catch (Exception $exception) {
            Mage::logException($exception);
        }
    }

    /**
     * Get released products data.
     *
     * @param $apiData
     *
     * @return array
     */
    public function getReleasedProducts($apiData)
    {
        $products = array();

        foreach ($apiData['products'] as $product) {
            $qty = isset($product['quantity']) ? $product['quantity'] : 1;

            if (0 === $qty) {
                continue;
            }

            $productId = $product['reference'];

            if (false !== strpos($productId, ';')) {
                $p = explode(';', $productId);
                $product['reference'] = $p[0];
                $product['variation_reference'] = $p[1];
            }

            if (isset($product['variation_reference'])) {
                $productId = $product['variation_reference'];
            }

            $products[$productId] = $qty;
        }

        return $products;
    }
}

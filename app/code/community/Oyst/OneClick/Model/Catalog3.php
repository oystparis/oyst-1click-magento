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
     * @var array
     */
    private $products = array();

    /**
     * @var string
     */
    private $query;

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

        parent::__construct();
    }

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
    public function checkItemsQty($data)
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

    public function getOystProducts($dataFormatted)
    {
        $productsFormatted = array();
        $this->isPreload = filter_var($dataFormatted['preload'], FILTER_VALIDATE_BOOLEAN);


        if (!$products = $this->getProducts(Zend_Json::decode($dataFormatted['products']))) {
            return $productsFormatted;
        }

        foreach ($products as $product) {
            $productsFormatted[] = $this->format($product);
        }

        return $productsFormatted;
    }

    /**
     * Book stock units.
     */
    public function bookStockItems()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->query($this->query);
    }

    public function format(Mage_Catalog_Model_Product $product, $qty = null)
    {
        $price = new OystPrice(1, 'EUR');
        $qty = is_null($qty) ? 1 : $qty;

        $oystProduct = new OystProduct($product->getEntityId(), $product->getName(), $price, $qty);

        $this->addAmount($product, $oystProduct);
    }

    public function addAmount(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        if ($product->isConfigurable()) {

        }
    }

    private function getConfigurablePrice($product)
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

        return $product->getFinalPrice();
    }
}
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

use Oyst\Classes\OystCategory;
use Oyst\Classes\OystPrice;
use Oyst\Classes\OystProduct;
use Oyst\Classes\OystSize;

/**
 * Catalog Helper
 */
class Oyst_OneClick_Helper_Catalog_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Translate Product attribute for Oyst <-> Magento
     *
     * @var array
     */
    protected $_productAttrTranslate = array(
        'entity_id' => array(
            'lib_method' => 'setRef',
            'type' => 'string',
            'required' => true
        ),
        'status' => array(
            'lib_method' => 'setActive',
            'type' => 'bool',
        ),
        'name' => array(
            'lib_method' => 'setTitle',
            'type' => 'string',
            'required' => true
        ),
        'short_description' => array(
            'lib_method' => 'setShortDescription',
            'type' => 'string',
        ),
        'description' => array(
            'lib_method' => 'setDescription',
            'type' => 'string',
        ),
        'manufacturer' => array(
            'lib_method' => 'setManufacturer',
            'type' => 'string',
            'required' => true
        ),
        'weight' => array(
            'lib_method' => 'setWeight',
            'type' => 'string',
        ),
    );

    /**
     * Object construct
     *
     * @return null
     */
    public function __construct()
    {
        if (!$this->_getConfig('enable')) {
            Mage::throwException($this->__('1-Click module is not enabled.'));
        }
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/oneclick/$code");
    }

    /**
     * Synchronisation process from notification controller
     *
     * @param array $event
     * @param array $data
     * @return number
     */
    public function syncFromNotification($event, $data)
    {
        // Get last notification
        /** @var Oyst_OneClick_Model_Notification $lastNotification */
        $lastNotification = Mage::getModel('oyst_oneclick/notification');
        $lastNotification = $lastNotification->getLastNotification('catalog', $data['import_id']);

        // If last notification is not finished
        if ($lastNotification->getId() && $lastNotification->getStatus() != 'finished') {
            Mage::throwException($this->__('Last Notification id %s is not finished', $data['import_id']));
        }

        // If last notification finish but with same id
        if ($lastNotification->getId() && $lastNotification->getImportRemaining() <= 0) {
            $response['totalCount'] = Mage::getModel('catalog/product')->getCollection()->count();
            $response['import_id'] = $data['import_id'];
            $response['remaining'] = 0;

            return $response;
        }

        // Set param 'num_per_page'
        if ($numberPerPack = $this->_getConfig('catalog_number_per_pack')) {
            $params['num_per_page'] = $numberPerPack;
        }

        // Set param 'import_id'
        $params['import_id'] = $data['import_id'];

        // Get last notification with this id and have remaining
        $notificationCollection = Mage::getModel('oyst_oneclick/notification')->getCollection()->addDataIdToFilter('catalog', $data['import_id']);
        $excludedProductsId = array();

        // Set id to exclude
        foreach ($notificationCollection as $pastNotification) {
            if ($productsId = Zend_Json::decode($pastNotification->getProductsId())) {
                $excludedProductsId = array_merge($excludedProductsId, $productsId);
            }
        }
        $params['product_id_exclude_filter'] = $excludedProductsId;

        // Create new notification in db with status 'start'
        $notification = Mage::getModel('oyst_oneclick/notification');
        $notification->setData(array(
            'event' => $event,
            'oyst_data' => Zend_Json::encode($data),
            'status' => 'start',
            'created_at' => Zend_Date::now(),
            'executed_at' => Zend_Date::now(),
        ));
        $notification->save();
        Mage::helper('oyst_oneclick')->log('Start of import id: ' . $data['import_id']);

        // Synchronize with Oyst
        $notification->setImportStart(Zend_Date::now());
        list($result, $importedProductIds) = $this->sync($params);
        $notification->setImportEnd(Zend_Date::now());

        // Set param for db
        $response['import_id'] = $data['import_id'];
        $response['totalCount'] = Mage::getModel('catalog/product')->getCollection()->count();
        $done = $response['totalCount'] - count($excludedProductsId) - count($importedProductIds);
        $response['remaining'] = ($done <= 0) ? 0 : $done;

        // Save new status and result in db
        $notification->setStatus('finished')
            ->setOystData(Zend_Json::encode($data))
            ->setProductsId(Zend_Json::encode($importedProductIds))
            ->setImportQty(count($importedProductIds))
            ->setImportRemaining($response['remaining'])
            ->setExecutedAt(Zend_Date::now())
            ->save();
        Mage::helper('oyst_oneclick')->log('End of import id: ' . $data['import_id']);

        // Import in progress
        if (0 < $response['remaining']) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('oyst_oneclick')->__(
                    '1-Click synchronization %s/%s (import id: %s).',
                    $response['remaining'],
                    $response['totalCount'],
                    $response['import_id']
                )
            );
        }

        // Import is finished
        if (0 == $response['remaining']) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('oyst_oneclick')->__(
                    '1-Click synchronization was successfully done (import id: %s).',
                    $response['import_id']
                )
            );
        }

        return $response;
    }

    /**
     * Synchronization process
     *
     * @param array $params
     *
     * @return array
     */
    public function sync($params = array())
    {
        // Get list of product from params
        $collection = $this->_prepareCollection($params);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->log('Product Collection Sql : ' . $collection->getSelect()->__toString());

        // Format list into OystProduct
        list($productsFormated, $importedProductIds) = $this->_format($collection);

        // Sync API
        /** @var Oyst_OneClick_Model_Catalog_ApiWrapper $catalogApi */
        $catalogApi = Mage::getModel('oyst_oneclick/catalog_apiWrapper');

        try {
            $response = $catalogApi->postProducts($productsFormated);
            $oystHelper->log($response);
        } catch (Exception $e) {
            Mage::logException($e);
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError($oystHelper->__('Could not synchronize catalog. Ckeck log files.'));
        }

        return array($response, $importedProductIds);
    }

    /**
     * Prepare Database Request with filters
     *
     * @param array $params
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _prepareCollection($params)
    {
        // Construct param for list in db request
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

        if (!empty($params) && is_array($params)) {
            if (!empty($params["product_id_include_filter"])) {
                $collection->addAttributeToFilter('entity_id', array(
                    'in' => $params['product_id_include_filter']
                ));
            }

            if (!empty($params["product_id_exclude_filter"])) {
                $collection->addAttributeToFilter('entity_id', array(
                    'nin' => $params['product_id_exclude_filter']
                ));
            }

            if (!empty($params['num_per_page'])) {
                $collection->setPage(0, $params['num_per_page']);
            }
        }

        // do not use : that include 'catalog_product_index_price' with inner join
        // but this table is used only if product is active once.
        // we want ALL products so let's join manually
        // Mage::getSingleton('cataloginventory/stock')->addItemsToProducts($collection);
        // $collection->addPriceData();
        $collection->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
        $collection->joinField('backorders', 'cataloginventory/stock_item', 'backorders', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
        $collection->joinField('min_sale_qty', 'cataloginventory/stock_item', 'min_sale_qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
        $collection->getSelect()->order('FIELD(type_id, "configurable", "grouped", "simple", "downloadable", "virtual", "bundle")');

        Mage::helper('oyst_oneclick')->log('The catalog product size is: ' . $collection->getSize());

        return $collection;
    }

    /**
     * Transform Database Data to formatted array
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $products
     *
     * @return array
     */
    protected function _format($products)
    {
        $importedProductIds = $productsFormated = array();
        foreach ($products as $product) {
            if (in_array($product->getId(), $importedProductIds)) {
                continue;
            }

            $oystProduct = new OystProduct();

            // Get product attributes
            $attributes = $this->_getAttributes($product, $this->_productAttrTranslate, $oystProduct);
            $importedProductIds[] = $product->getId();
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                // Get sku attributes
                $productIds = $this->_addVariations($product, $attributes, $oystProduct);
            }

            // Add others attributes
            $this->_addAmount($product, $oystProduct);
            $this->_addComplexAttributes($product, $oystProduct);
            $this->_addCategories($product, $oystProduct);
            $this->_addImages($product, $oystProduct);
            $this->_addRelatedProducts($product, $oystProduct);

            $productsFormated[] = $oystProduct;
        }

        $importedProductIds = array_unique($importedProductIds);

        return array($productsFormated, $importedProductIds);
    }

    /**
     * Get product attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $translateAttribute $this->_skusAttrTranslate || $this->_productAttrTranslate
     * @param OystProduct $oystProduct
     *
     * @return array
     */
    protected function _getAttributes(Mage_Catalog_Model_Product $product, Array $translateAttribute, OystProduct &$oystProduct)
    {
        $attributes = array();
        foreach ($translateAttribute as $attributeCode => $simpleAttribute) {
            if ($data = $product->getData($attributeCode)) {
                if ($simpleAttribute['type'] == 'jsonb') {
                    $data = Zend_Json::encode(array(
                        'meta' => $data
                    ));
                } else {
                    settype($data, $simpleAttribute['type']);
                }
                if ($data !== null) {
                    $oystProduct->{$simpleAttribute['lib_method']}($data);
                }
            } elseif (array_key_exists('required', $simpleAttribute) && $simpleAttribute['required'] == true) {
                if ('jsonb' == $simpleAttribute['type']) {
                    $data = '{}';
                } else {
                    $data = 'Empty';
                    settype($data, $simpleAttribute['type']);
                }
            }
        }

        return $attributes;
    }

    /**
     * Add variations attributes to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     *
     * @return array
     */
    protected function _addVariations(Mage_Catalog_Model_Product $product, &$attributes, OystProduct &$oystProduct)
    {
        /** @var Mage_Catalog_Model_Product_Type_Configurable $childProducts */
        $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);

        list($variationProductsFormated, $importedProductIds) = $this->_format($childProducts);

        $oystProduct->setVariations($variationProductsFormated);
    }

    /**
     * Add price to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    public function _addAmount(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $price = $product->getPrice();
        $oystPrice = new OystPrice($price, 'EUR');

        $oystProduct->setAmountIncludingTax($oystPrice);
    }

    /**
     * Add complex attribute to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function _addComplexAttributes(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $oystProduct->setUrl($product->getUrlModel()->getProductUrl($product));
        $product->unsRequestPath();
        $oystProduct->setUrl($product->getUrlInStore(array('_ignore_category' => true)));
        $oystProduct->setMaterialized(($product->isVirtual()) ? true : false);

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $oystProduct->setAvailableQuantity((int)$stock->getQty());

        $productActive = ('1' === $product->getStatus()) ? true : false;
        $oystProduct->setActive($productActive);

        // @TODO add verification of news_from_date/news_to_date
        $oystProduct->setCondition('new');
    }

    /**
     * Add categories to product array
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function _addCategories(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
        $categoryCollection = $product->getCategoryCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('url_key');

        $oystCategory = array();

        /** @var Mage_Catalog_Model_Category $category */
        foreach ($categoryCollection as $category) {
            // Count slash to determine if it's a main category
            $isMain = substr_count($category->getPath(), '/') == 2;
            $oystCategory[] = new OystCategory($category->getId(), $category->getName(), $isMain);
        }

        if (empty($oystCategory)) {
            // Category is mandatory
            $oystCategory[] = new OystCategory('none', 'none');
        }

        $oystProduct->setCategories($oystCategory);
    }

    /**
     * Add picture link of product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function _addImages(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $attribute = Mage::getSingleton('catalog/product')->getResource()->getAttribute('media_gallery');
        $media = Mage::getResourceSingleton('catalog/product_attribute_backend_media');
        $gallery = $media->loadGallery($product, new Varien_Object(array('attribute' => $attribute)));

        $images = array();
        foreach ($gallery as $image) {
            $images[] = $product->getMediaConfig()->getMediaUrl($image['file']);
        }

        if (empty($images)) {
            $images[] = sprintf('%s/placeholder/%s',
                Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl(),
                Mage::getStoreConfig('catalog/placeholder/image_placeholder')
            );
        }

        $oystProduct->setImages($images);
    }

    /**
     * Add related product of parent product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function _addRelatedProducts(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        if ($relatedProducts = $product->getRelatedProductIds()) {
            $oystProduct->setRelatedProducts($relatedProducts);
        }
    }
}

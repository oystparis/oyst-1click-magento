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

/**
 * Catalog Helper
 */
class Oyst_OneClick_Helper_Catalog_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Supported type of product
     *
     * @var array
     */
    protected $_supportedProductTypes = array(
        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        //Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
        //Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
        //Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        //Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    );

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

        'ean' => array(
            'lib_method' => 'setEan',
            'type' => 'string',
        ),
        'isbn' => array(
            'lib_method' => 'setEan',
            'type' => 'string',
        ),
        'upc' => array(
            'lib_method' => 'setEan',
            'type' => 'string',
        ),
    );

    /**
     * Custom attribute code
     *
     * @var array
     */
    protected $_customAttributesCode = array('color', 'size');

    /**
     * Selected system attribute code
     *
     * @var array
     */
    protected $_systemSelectedAttributesCode = array();

    /**
     * Variations attribute code
     *
     * @var array
     */
    protected $_variationAttributesCode = array('price', 'final_price');

    /**
     * User defined attribute code
     *
     * @var array
     */
    protected $_userDefinedAttributeCode = array();

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
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToFilter('type_id', array('in' => $this->_supportedProductTypes));
        $collection->addAttributeToSelect('*');

        if (!empty($params) && is_array($params)) {
            if (!empty($params['product_id_include_filter'])) {
                $collection->addAttributeToFilter('entity_id', array(
                    'in' => $params['product_id_include_filter']
                ));
            }

            if (!empty($params['product_id_exclude_filter'])) {
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
        $collection->getSelect()->order('FIELD(type_id, ' . ('"'.implode('","', $this->_supportedProductTypes).'"') . ')');

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

            if (is_array($products)) {
                // This need to be improved
                $product = Mage::getModel('catalog/product')->load($product->getId());
            }

            // Get product attributes
            $this->_getAttributes($product, $this->_productAttrTranslate, $oystProduct);
            $importedProductIds[] = $product->getId();
            if ($product->isConfigurable()) {
                $this->_addVariations($product, $oystProduct);
            }

            // Add others attributes
            $this->_addAmount($product, $oystProduct);
            $this->_addComplexAttributes($product, $oystProduct);
            $this->_addCategories($product, $oystProduct);
            $this->_addImages($product, $oystProduct);
            $this->_addRelatedProducts($product, $oystProduct);
            $this->_addCustomAttributesToInformation($product, $oystProduct);

            $productsFormated[] = $oystProduct;
        }

        $importedProductIds = array_unique($importedProductIds);

        return array($productsFormated, $importedProductIds);
    }

    /**
     * Return the user defined attributes code
     *
     * @return array
     */
    protected function _getUserDefinedAttributeCode()
    {
        /** @var Mage_Eav_Model_Entity_Type $type */
        $type = Mage::getModel('eav/entity_type');
        $type->loadByCode('catalog_product');

        /* @var $attrs Mage_Eav_Model_Resource_Entity_Attribute_Collection */
        $attrs = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($type)
            ->addFieldToFilter('is_user_defined', true)
            ->addFieldToFilter('frontend_input', 'select');

        $userDefinedAttributeCode = array();
        /* @var $attribute Mage_Eav_Model_Entity_Attribute */
        foreach ($attrs as $attribute) {
            $userDefinedAttributeCode[] = $attribute->getAttributeCode();
        }

        return $userDefinedAttributeCode;
    }

    /**
     * Return the selected system attributes code
     *
     * @return array
     */
    protected function _getSystemSelectedAttributeCode()
    {
        $code = 'systemattributes';
        $attrsIds = explode(',', Mage::getStoreConfig("oyst/oneclick/$code"));

        $systempSelectedAttributeCode = array();
        foreach ($attrsIds as $attributeId) {
            $attribute = Mage::getModel('eav/entity_attribute')->load($attributeId);
            $systempSelectedAttributeCode[] = $attribute->getAttributeCode();
        }

        return $systempSelectedAttributeCode;
    }

    /**
     * Return the product attributes code defined by user
     *
     * @param Mage_Catalog_Model_Product $product
     * @param $userDefinedAttributeCode
     *
     * @return array
     */
    protected function _getProductAttributeCodeDefinedByUser(Mage_Catalog_Model_Product $product, $userDefinedAttributeCode)
    {
        $attributes = $product->getAttributes();
        $productAttributeCode = array();
        foreach ($attributes as $attribute) {
            $productAttributeCode[] = $attribute->getAttributeCode();
        }

        $attributeCodes = array_unique(
            array_merge(
                array_intersect($userDefinedAttributeCode, $productAttributeCode),
                $this->_customAttributesCode,
                $this->_systemSelectedAttributesCode
            )
        );

        return $attributeCodes;
    }

    /**
     * Get product attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $translateAttribute
     * @param OystProduct $oystProduct
     */
    protected function _getAttributes(Mage_Catalog_Model_Product $product, Array $translateAttribute, OystProduct &$oystProduct)
    {
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
    }

    /**
     * Add variations attributes to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     *
     * @return array
     */
    protected function _addVariations(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $productAttributeCodeDefinedByUser = $this->_getProductAttributeCodeDefinedByUser($product, $this->_userDefinedAttributeCode);

        $requiredAttributesCode = array_unique(
            array_merge(
                array_keys($this->_productAttrTranslate),
                $this->_customAttributesCode,
                $this->_systemSelectedAttributesCode,
                $productAttributeCodeDefinedByUser,
                $this->_variationAttributesCode
            )
        );

        $requiredAttributesIds = array();
        foreach ($requiredAttributesCode as $requiredAttributeCode) {
            $requiredAttributesIds[] = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', $requiredAttributeCode);
        }

        /** @var Mage_Catalog_Model_Product_Type_Configurable $childProducts */
        $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts($requiredAttributesIds, $product);

        list($variationProductsFormated, $importedProductIds) = $this->_format($childProducts);

        $oystProduct->setVariations($variationProductsFormated);
    }

    /**
     * Add price to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     */
    protected function _addAmount(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $prices = $this->_getPrices($product);
        $oystPriceIncludingTaxes = new OystPrice($prices['price-including-tax'], 'EUR');
        $oystProduct->setAmountIncludingTax($oystPriceIncludingTaxes);

        if (isset($prices['price-excluding-tax'])) {
            $oystPriceExcludingTaxes = new OystPrice($prices['price-excluding-tax'], 'EUR');
            $oystProduct->setAmountExcludingTax($oystPriceExcludingTaxes);
        }
    }

    /**
     * Get prices
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Product $configurable
     * @param integer $storeId
     *
     * @return array
     */
    protected function _getPrices(Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product $configurable = null, $storeId = null)
    {
        $store = Mage::app()->getStore($storeId);
        $priceIncludesTax = Mage::helper('tax')->priceIncludesTax($store);
        $shippingPriceIncludesTax = Mage::helper('tax')->shippingPriceIncludesTax($store);
        $calculator = Mage::getSingleton('tax/calculation');
        $taxClassId = $product->getTaxClassId();
        $request = $calculator->getRateRequest(null, null, null, $store);
        $taxPercent = $calculator->getRate($request->setProductClassId($taxClassId));

        if ($configurable) {
            $price = $configurable->getPrice();
            $finalPrice = $configurable->getFinalPrice();
            $configurablePrice = 0;
            $configurableOldPrice = 0;
            $attributes = $configurable->getTypeInstance(true)->getConfigurableAttributes($configurable);
            $attributes = Mage::helper('core')->decorateArray($attributes);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $product->getData($productAttribute->getAttributeCode());
                    if (count($attribute->getPrices()) > 0) {
                        foreach ($attribute->getPrices() as $priceChange) {
                            if (is_array($price) && array_key_exists('value_index',
                                    $price) && $price['value_index'] == $attributeValue
                            ) {
                                $configurableOldPrice += (float)($priceChange['is_percent'] ? (((float)$priceChange['pricing_value']) * $price / 100) : $priceChange['pricing_value']);
                                $configurablePrice += (float)($priceChange['is_percent'] ? (((float)$priceChange['pricing_value']) * $finalPrice / 100) : $priceChange['pricing_value']);
                            }
                        }
                    }
                }
            }
            $configurable->setConfigurablePrice($configurablePrice);
            $configurable->setParentId(true);
            Mage::dispatchEvent(
                'catalog_product_type_configurable_price',
                array('product' => $configurable)
            );
            $configurablePrice = $configurable->getConfigurablePrice();
            $price = $product->getPrice() + $configurableOldPrice;
            $finalPrice = $product->getFinalPrice() + $configurablePrice;
        } else {
            if ($product->getTypeId() == 'grouped') {
                $price = 0;
                $finalPrice = 0;
                $childs = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($product->getId());
                $childs = $childs[Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED];
                foreach ($childs as $value) {
                    $product = Mage::getModel('catalog/product')->load($value);
                    $price += $product->getPrice();
                    $finalPrice += $product->getFinalPrice();
                }
                $priceIncludingTax = Mage::helper('tax')->getPrice(
                    $product->setTaxPercent(null),
                    $price,
                    true
                );
                $finalPriceIncludingTax = Mage::helper('tax')->getPrice(
                    $product->setTaxPercent(null),
                    $finalPrice,
                    true
                );
            } else {
                $price = $product->getPrice();
                $finalPrice = $product->getFinalPrice();
            }
        }

        $priceIncludingTax = $price;
        $finalPriceIncludingTax = $finalPrice;
        if (!$priceIncludesTax) {
            $data['price-excluding-tax'] = round($finalPrice, 2);
            $priceIncludingTax = $price + $calculator->calcTaxAmount($price, $taxPercent, false);
            $finalPriceIncludingTax = $finalPrice + $calculator->calcTaxAmount($finalPrice, $taxPercent, false);
        }

        // Get prices
        $data['price-including-tax'] = round($finalPriceIncludingTax, 2);
        $data['price-before-discount'] = round($priceIncludingTax, 2);
        $discountAmount = $priceIncludingTax - $finalPriceIncludingTax;
        $data['discount-amount'] = $discountAmount > 0 ? round($discountAmount, 2) : '0';
        $data['discount-percent'] = $discountAmount > 0 ? round(($discountAmount * 100) / $priceIncludingTax,
            0) : '0';
        $data['start-date-discount'] = $product->getSpecialFromDate();
        $data['end-date-discount'] = $product->getSpecialToDate();

        // Retrieving promotions
        $dateTs = Mage::app()->getLocale()->storeTimeStamp($product->getStoreId());
        if (method_exists(Mage::getResourceModel('catalogrule/rule'), 'getRulesFromProduct')) {
            $promo = Mage::getResourceModel('catalogrule/rule')->getRulesFromProduct($dateTs, $product->getStoreId(), 1, $product->getId());
        } elseif (method_exists(Mage::getResourceModel('catalogrule/rule'), 'getRulesForProduct')) {
            $promo = Mage::getResourceModel('catalogrule/rule')->getRulesForProduct($dateTs, $product->getStoreId(), $product->getId());
        }
        if (count($promo)) {
            $promo = $promo[0];

            $from = isset($promo['from_time']) ? $promo['from_time'] : $promo['from_date'];
            $to = isset($promo['to_time']) ? $promo['to_time'] : $promo['to_date'];

            $data['start-date-discount'] = date('Y-m-d H:i:s', strtotime($from));
            $data['end-date-discount'] = is_null($to) ? '' : date('Y-m-d H:i:s', strtotime($to));
        }

        return $data;
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
        $oystProduct->setMaterialized(!($product->isVirtual()) ? true : false);

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $oystProduct->setAvailableQuantity((int)$stock->getQty());

        $productActive = ('1' === $product->getStatus()) ? true : false;
        $oystProduct->setActive($productActive);

        $oystProduct->setCondition('new');

        // @TODO add verification for discount price
        $isDiscounted = false;
        $oystProduct->setDiscounted($isDiscounted);
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

    /**
     * Add custom attributes to product information field
     *
     * @param Mage_Catalog_Model_Product $product
     * @param OystProduct $oystProduct
     * @param array $userDefinedAttributeCode
     */
    protected function _addCustomAttributesToInformation(Mage_Catalog_Model_Product $product, OystProduct &$oystProduct)
    {
        $attributeCodes = $this->_getProductAttributeCodeDefinedByUser($product, $this->_userDefinedAttributeCode);

        Mage::helper('oyst_oneclick')->log('$attributeCodes');
        Mage::helper('oyst_oneclick')->log($attributeCodes);

        foreach($attributeCodes as $attributeCode) {
            $value = '';
            if (($attribute = $product->getResource()->getAttribute($attributeCode)) && !is_null($product->getData($attributeCode))) {
                $value = $attribute->getFrontend()->getValue($product);
            }

            if (empty($value)) {
                continue;
            }
            Mage::helper('oyst_oneclick')->log('$attributeCode: ' . $attributeCode . '  -  value: ' . $value);

            $oystProduct->addInformation($attributeCode, $value);
        }
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
        if (in_array($product->getTypeId(), $this->_supportedProductTypes)) {
            $supported = true;
        }

        return $supported;
    }

    /**
     * Return a OystProduct
     *
     * @param int $productId
     *
     * @return OystProduct
     */
    public function getOystProduct($productId)
    {
        // Params used to pass data in prepare collection
        $params['product_id_include_filter'] = array($productId);

        // Get list of product from params
        $collection = $this->_prepareCollection($params);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');
        $oystHelper->log('Catalog less product collection Sql : ' . $collection->getSelect()->__toString());

        $this->_userDefinedAttributeCode = $this->_getUserDefinedAttributeCode();
        $this->_systemSelectedAttributesCode = $this->_getSystemSelectedAttributeCode();

        // Format list into OystProduct
        list($productsFormated, $importedProductIds) = $this->_format($collection);

        return $productsFormated[0];
    }

    /**
     * Count product collection
     *
     * @param array $param
     *
     * @return mixed
     */
    protected function _countProductCollection($param)
    {
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        if (!empty($params['product_id_include_filter'])) {
            $productCollection->addAttributeToFilter('entity_id', array(
                'in' => $params['product_id_include_filter']
            ));
        }
        if (!empty($params['product_id_exclude_filter'])) {
            $productCollection->addAttributeToFilter('entity_id', array(
                'nin' => $params['product_id_exclude_filter']
            ));
        }
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_supportedProductTypes));

        return $productCollection->getSize();
    }

    /**
     * In case of a configurable has only one product return the child product id
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return null|int
     */
    public function getConfigurableProductChildId($product)
    {
        if ($product->isConfigurable()) {
            /** @var Mage_Catalog_Model_Product_Type_Configurable $childProducts */
            $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProductIds($product);

            if (1 === count($childProducts)) {
                return $childProducts[0];
            }
        }

        return null;
    }
}

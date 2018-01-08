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
 * OneClick Block
 */
class Oyst_OneClick_Block_OneClick extends Mage_Core_Block_Template
{
    /**
     * Check if the product is supported
     *
     * @return mixed
     */
    public function isSupportedProduct()
    {
        /** @var Oyst_OneClick_Model_Catalog $oystCatalog */
        $oystCatalog = Mage::getModel('oyst_oneclick/catalog');

        return $oystCatalog->isSupportedProduct($this->getProduct());
    }

    /**
     * Retrieve product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * Get configurable product id or return null string
     *
     * @return string
     */
    public function getConfigurableProductChildIdIfExist()
    {
        /** @var Oyst_OneClick_Helper_Catalog_Data $catalogHelper */
        $catalogHelper = Mage::helper('oyst_oneclick/magento_data');

        $configurableProductChildId = $catalogHelper->getConfigurableProductChildId($this->getProduct());

        return null === $configurableProductChildId ? 'null' : $configurableProductChildId;
    }

    /**
     * Return the shop payment url
     * Used to get in server-to-server payment url
     *
     * @return mixed
     */
    public function getOneClickUrl()
    {
        $store = Mage::getSingleton('adminhtml/session_quote')->getStore();

        return Zend_Json::encode(Mage::getStoreConfig('oyst/oneclick/payment_url', $store->getId()));
    }

    /**
     * Is form validation enable
     *
     * @return mixed
     */
    public function isProductAddtocartFormValidate()
    {
        return Mage::getStoreConfig('oyst/oneclick/product_addtocart_form_validate');
    }

    /**
     * Return button customization
     *
     * @return mixed
     */
    public function getButtonCustomization()
    {
        $buttonCustomization = '';
        $customizationAttributes = array('theme', 'color', 'width', 'height');
        foreach ($customizationAttributes as $customizationAttribute) {
            if (Mage::getStoreConfig('oyst/oneclick/button_' . $customizationAttribute)) {
                $buttonCustomization .= sprintf(" data-" .  $customizationAttribute . "='%s'",
                    Mage::getStoreConfig('oyst/oneclick/button_' . $customizationAttribute)
                );
            }
        }

        return $buttonCustomization;
    }

    /**
     * Return button customization
     *
     * @return mixed
     */
    public function buttonPosition()
    {
        if ('before' === Mage::getStoreConfig('oyst/oneclick/button_before_addtocart')) {
            $buttonPosition = "addToCartBtns = document.getElementsByClassName('add-to-cart-buttons')[0];" . PHP_EOL;
            $buttonPosition .= "oystOneClickButton = document.getElementById('oyst-1click-button');" . PHP_EOL;
            $buttonPosition .= "prependChild(addToCartBtns, oystOneClickButton);" . PHP_EOL;

            return $buttonPosition;
        }
    }
}

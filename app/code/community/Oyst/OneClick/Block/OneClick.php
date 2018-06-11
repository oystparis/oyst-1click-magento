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
    const XML_PATH_CHECKOUT_ONEPAGE_XPATH_TO_APPEND_ONECLICK_BUTTON = 'oyst/oneclick/checkout_onepage_xpath_to_append_oneclick_button';

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
     * Check if button is enabled
     *
     * @return bool
     */
    public function isButtonEnabled()
    {
        if ('disabled' === Mage::getStoreConfig('oyst/oneclick/product_page_button_position')) {
            return false;
        }

        // TODO Change code attribute to avoid confusions
        if ($this->getProduct()->getIsOneclickActiveOnProduct()) {
            return false;
        }

        return true;
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
     * Return the shop payment url
     * Used to get in server-to-server payment url
     *
     * @return mixed
     */
    public function getOneClickUrl()
    {
        $store = Mage::getSingleton('adminhtml/session_quote')->getStore();

        return Zend_Json::encode($this->escapeUrl(Mage::getStoreConfig('oyst/oneclick/payment_url', $store->getId())));
    }

    public function getOneClickModalUrl()
    {
        return Zend_Json::encode($this->escapeUrl(Mage::helper('oyst_oneclick')->getOneClickModalUrl()));
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
     * @param string $path
     *
     * @return mixed
     */
    public function getButtonCustomization($path = '')
    {
        $buttonCustomization = '';

        $genericCustomizationAttributes = array('theme', 'color', 'rounded', 'smart');

        foreach ($genericCustomizationAttributes as $customizationAttribute) {
            $config = Mage::getStoreConfig('oyst/oneclick/button_' . $customizationAttribute);
            if (empty($config)) {
                continue;
            }

            if (in_array($customizationAttribute, array('rounded', 'smart'))) {
                $config = filter_var($config, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            }

            $buttonCustomization .= sprintf(" data-" . $customizationAttribute . "='%s'", $config);
        }

        $specificCustomizationAttributes = array('height', 'width');

        foreach ($specificCustomizationAttributes as $customizationAttribute) {
            $config = Mage::getStoreConfig('oyst/oneclick/' . $path . 'button_' . $customizationAttribute);
            if (empty($config)) {
                continue;
            }

            $buttonCustomization .= sprintf(" data-" . $customizationAttribute . "='%s'", $config);
        }

        $specificCustomizationAttributes = array('height', 'width');

        foreach ($specificCustomizationAttributes as $customizationAttribute) {
            $config = Mage::getStoreConfig('oyst/oneclick/' . $path . 'button_' . $customizationAttribute);
            if (empty($config)) {
                continue;
            }

            $buttonCustomization .= sprintf(" data-" .  $customizationAttribute . "='%s'", $config);
        }

        return $buttonCustomization;
    }

    /**
     * Move OneClick button in first position in add to cart buttons list
     *
     * @return mixed
     */
    public function oneClickButtonPickToFirstAddToCartButtons()
    {
        if ('before' === Mage::getStoreConfig('oyst/oneclick/product_page_button_position')) {
            $class = Mage::getStoreConfig('oyst/oneclick/product_page_buttons_wrapper_class');

            return 'oneClickButtonPickToFirstAddToCartButtons("' . $class . '");';
        }
    }

    /**
     * Add CSS styles block
     *
     * @return string
     */
    public function addCssStylesBlock()
    {
        $configuration = Mage::getStoreConfig('oyst/oneclick/button_wrapper_styles');

        if (!$configuration) {
            return null;
        }

        $configuration = rtrim($configuration, " \n\r;");
        $configuration = explode(';', $configuration);

        $styles = '<style>';

        foreach ($configuration as $item) {
            $item = str_replace(array('"', '&quot;'), array('\''), $item);
            $styles .= trim($item) . '; ';
        }

        $styles .= '</style>';

        return $styles;
    }

    /**
     * Get the product type: simple, configurable, grouped, ...
     *
     * @return mixed
     */
    public function getProductType()
    {
        return Zend_Json::encode($this->escapeHtml($this->getProduct()->getTypeId()));
    }

    /**
     * Get checkout onepage position to append the button
     *
     * @return string
     */
    public function getCheckoutOnepagePlacesToAppendOneClickButton()
    {
        return $this->escapeHtml(Mage::getStoreConfig(self::XML_PATH_CHECKOUT_ONEPAGE_XPATH_TO_APPEND_ONECLICK_BUTTON));
    }
}

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
 * Cart Model
 */
class Oyst_OneClick_Model_Cart
{
    public function initOystCheckout($params)
    {
        Mage::helper('oyst_oneclick')->log('$params');
        Mage::helper('oyst_oneclick')->log($params);

        if (!empty($params['add_to_cart_form'])
            && !$params['preload']
        ) {
            $this->_addToCart($params['add_to_cart_form']);
            unset($params['form_key']);
        }

        /** @var Oyst_OneClick_Model_ApiWrapper_Type_OneClick $response */
        $response = Mage::getModel('oyst_oneclick/apiWrapper_type_oneClick')->authorizeOrder($params);

        if (empty($response)) {
            throw new Exception(Mage::helper('oyst_oneclick')->__('Invalid Authorize Order Response.'));
        }

        return $response;
    }

    protected function _addToCart($addToCartFormParams)
    {
        $cart = Mage::getSingleton('checkout/cart');

        if (isset($addToCartFormParams['qty'])) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            $addToCartFormParams['qty'] = $filter->filter($addToCartFormParams['qty']);
        }

        $product = $this->_initProduct($addToCartFormParams);

        if (!$product) {
            throw new Exception(Mage::helper('oyst_oneclick')->__('Product is not available'));
        }

        $cart->addProduct($product, $addToCartFormParams);

        $related = isset($addToCartFormParams['related_product']) ? $addToCartFormParams['related_product'] : null;
        if (!empty($related)) {
            $cart->addProductsByIds(explode(',', $related));
        }

        Mage::dispatchEvent('oyst_oneclick_model_cart_add_to_cart', array('cart' => $cart, 'add_to_cart_form_params' => $addToCartFormParams));

        $cart->save();

        return true;
    }

    protected function _initProduct($addToCartFormParams)
    {
        $productId = isset($addToCartFormParams['product']) ? (int) $addToCartFormParams['product'] : null;

        if ($productId) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);
            if ($product->getId()) {
                return $product;
            }
        }

        return false;
    }

    /**
     * Mage_Sales_Model_Quote_Address caches items after each collectTotals call. Some extensions calls collectTotals
     * after adding new item to quote in observers. So we need clear this cache before adding new item to quote.
     */
    public function resetCartForSave()
    {
        $cart = Mage::getSingleton('checkout/cart');

        $cart->getQuote()->setDataChanges(true);
        $cart->getQuote()->setTotalsCollectedFlag(false);

        foreach ($cart->getQuote()->getAllAddresses() as $address) {
            /** @var $address Mage_Sales_Model_Quote_Address */
            $address->unsetData('cached_items_all');
            $address->unsetData('cached_items_nominal');
            $address->unsetData('cached_items_nonominal');
        }
    }
}

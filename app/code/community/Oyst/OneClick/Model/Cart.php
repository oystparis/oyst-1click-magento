<?php

/**
 * Cart Model
 */
class Oyst_OneClick_Model_Cart
{
    public function initOystCheckout($params)
    {
        if (!empty($params['add_to_cart_form'])
        && !$params['isPreload']) {
            $substractQuoteItemsQtys = array('quoteId' => $params['quoteId']);
            Mage::getModel('oyst_oneclick/oneClick_apiWrapper')->getCartItems($substractQuoteItemsQtys);
            $params['substract_quote_items_qtys'] = $substractQuoteItemsQtys;

            $this->_addToCart($params['add_to_cart_form']);
            unset($params['form_key']);
        }

        $response = $this->_authorizeOrder($params);
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
        $related = isset($addToCartFormParams['related_product']) ? $addToCartFormParams['related_product'] : null;

        if (!$product) {
            throw new Exception(Mage::helper('oyst_oneclick')->__('Product is not available'));
        }

        $cart->addProduct($product, $addToCartFormParams);
        if (!empty($related)) {
            $cart->addProductsByIds(explode(',', $related));
        }

        Mage::dispatchEvent('oyst_oneclick_checkout_type_oyst_add_to_cart', array('cart' => $cart, 'add_to_cart_form_params' => $addToCartFormParams));

        $cart->save();
        return true;
    }

    protected function _initProduct($addToCartFormParams)
    {
        $productId = isset($addToCartFormParams['product']) ? $addToCartFormParams['product'] : null;
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

    protected function _authorizeOrder($params)
    {
        $response = Mage::getModel('oyst_oneclick/oneClick_apiWrapper')->authorizeOrder($params);
        if (empty($response)) {
            throw new Exception(Mage::helper('oyst_oneclick')->__('Invalid Authorize Order Response.'));
        }

        return $response;
    }
}

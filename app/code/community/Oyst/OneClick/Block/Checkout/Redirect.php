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
 * Checkout Cart Redirect Block
 */
class Oyst_OneClick_Block_Checkout_Redirect extends Mage_Core_Block_Template
{
    /**
     * Get quote url.
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return Mage::getBaseUrl() . Oyst_OneClick_Helper_Data::RETURN_URL;
    }

    /**
     * Get loading page message.
     *
     * @return string
     */
    public function getMessage()
    {
        return Mage::getStoreConfig('oyst/oneclick/checkout_cart_cta_loading_message');
    }
}

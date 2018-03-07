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
class Oyst_OneClick_Block_Checkout_Cart_Redirect extends Mage_Core_Block_Template
{
    /* @var int $quoteId */
    private $quoteId;

    /**
     * Oyst_OneClick_Block_Cart_Redirect constructor.
     *
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        $this->quoteId = Mage::app()->getRequest()->getParam('cart_id', null);
        parent::__construct($args);
    }

    /**
     * Get quote id.
     *
     * @return int|mixed
     */
    public function getQuoteId()
    {
        return $this->quoteId;
    }

    /**
     * Get quote url.
     *
     * @return string
     */
    public function getQuoteUrl()
    {
        return Mage::getBaseUrl() . Oyst_OneClick_Helper_Data::QUOTE_URL;
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

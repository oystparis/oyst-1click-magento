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
     * Include JS in head if section is moneybookers
     */
    protected function _prepareLayout()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ('oyst_oneclick' == $section) {
            $this->getLayout()
                ->getBlock('head')
                ->addJs('oyst/oneclick.js');

            $this->getLayout()
                ->getBlock('head')
                ->addCss('oyst/css/oneclick.css');
        }
        parent::_prepareLayout();
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

        return Mage::getStoreConfig('oyst/oneclick/payment_url', $store->getId());
    }
}

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
 * Magento Data Helper
 */
class Oyst_OneClick_Helper_Magento_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    public function isSupportedProduct(Mage_Catalog_Model_Product $product)
    {
        /** @var Oyst_OneClick_Model_Catalog */
        return Mage::getModel('oyst_oneclick/catalog')->isSupportedProduct($product);
    }
}

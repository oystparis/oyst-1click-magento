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
 * Order Model
 */
class Oyst_OneClick_Model_Magento_Tax_Helper
{
    public function hasRatesForCountry($countryId)
    {
        return Mage::getModel('tax/calculation_rate')
            ->getCollection()
            ->addFieldToFilter('tax_country_id', $countryId)
            ->addFieldToFilter('code', array('neq' => Oyst_OneClick_Model_Magento_Tax_Rule_Builder::TAX_RATE_CODE))
            ->getSize();
    }

    /**
     * Return store tax rate for shipping
     *
     * @param Mage_Core_Model_Store $store
     * @return float
     */
    public function getStoreShippingTaxRate($store)
    {
        $request = new Varien_Object();
        $request->setProductClassId(Mage::getSingleton('tax/config')->getShippingTaxClass($store));

        return Mage::getSingleton('tax/calculation')->getStoreRate($request, $store);
    }

    public function isCalculationBasedOnOrigin($store)
    {
        return 'origin' == Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON, $store);
    }
}

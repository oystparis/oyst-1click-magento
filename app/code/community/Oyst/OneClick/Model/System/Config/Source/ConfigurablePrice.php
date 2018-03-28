<?php
/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license All rights reserved, Oyst
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * ConfigurablePrice Model
 */
class Oyst_OneClick_Model_System_Config_Source_ConfigurablePrice
{
    /**
     * Return the options for configurable price.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 0,
                'label' => Mage::helper('oyst_oneclick')->__(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE),
            ),
            array(
                'value' => 1,
                'label' => Mage::helper('oyst_oneclick')->__(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
            ),
        );
    }
}

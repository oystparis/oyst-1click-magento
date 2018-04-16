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
 * Catalog product button position
 */
class Oyst_OneClick_Model_System_Config_Source_Catalog_Product_Button_Position
{
    /**
     * Return the options for button position.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'disabled','label' => Mage::helper('oyst_oneclick')->__('Disabled')),
            array('value' => 'before', 'label' => Mage::helper('oyst_oneclick')->__('Before add to cart button')),
            array('value' => 'after', 'label' => Mage::helper('oyst_oneclick')->__('After add to cart button')),
        );
    }
}

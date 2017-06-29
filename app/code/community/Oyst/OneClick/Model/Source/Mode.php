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

use Oyst\Api\OystApiClientFactory;

/**
 * Source Mode Model
 */
class Oyst_OneClick_Model_Source_Mode
{
    const CUSTOM = 'custom';
    const PREPROD = OystApiClientFactory::ENV_PREPROD;
    const PROD = OystApiClientFactory::ENV_PROD;

    /**
     * Return the options for mode.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::PREPROD, 'label' => Mage::helper('oyst_oneclick')->__(self::PREPROD)),
            array('value' => self::PROD, 'label' => Mage::helper('oyst_oneclick')->__(self::PROD)),
            array('value' => self::CUSTOM, 'label' => Mage::helper('oyst_oneclick')->__(self::CUSTOM)),
        );
    }
}

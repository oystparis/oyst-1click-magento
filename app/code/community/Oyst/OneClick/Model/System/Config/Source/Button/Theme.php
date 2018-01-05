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
 * Mode Model
 */
class Oyst_OneClick_Model_System_Config_Source_Button_Theme
{
    /**
     * Return the options for button theme.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'normal', 'label' => Mage::helper('oyst_oneclick')->__('Normal')),
            array('value' => 'inversed', 'label' => Mage::helper('oyst_oneclick')->__('Inversed')),
        );
    }
}

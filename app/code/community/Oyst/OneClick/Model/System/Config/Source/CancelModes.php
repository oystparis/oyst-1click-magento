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
 * Cancel Modes
 */
class Oyst_OneClick_Model_System_Config_Source_CancelModes
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Mage_Sales_Model_Order::STATE_HOLDED,
                'label' => Mage::helper('sales')->__('On Hold'),
            ),
            array(
                'value' => Mage_Sales_Model_Order::STATE_CANCELED,
                'label' => Mage::helper('sales')->__('Canceled'),
            ),
        );
    }
}

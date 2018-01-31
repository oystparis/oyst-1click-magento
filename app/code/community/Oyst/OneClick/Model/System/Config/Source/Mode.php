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
class Oyst_OneClick_Model_System_Config_Source_Mode
{
    const CUSTOM = 'custom';

    /**
     * Return the options for mode.
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var Oyst_OneClick_Model_Api $oystClient */
        $oystApi = Mage::getModel('oyst_oneclick/api');

        $list = array();
        foreach ($oystApi->getEnvironments() as $environment) {
            $list[] = array('value' => $environment, 'label' => Mage::helper('oyst_oneclick')->__($environment));
        }

        return $list;
    }
}

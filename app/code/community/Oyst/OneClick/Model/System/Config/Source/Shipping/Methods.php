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
 * Shipping Methods Model
 */
class Oyst_OneClick_Model_System_Config_Source_Shipping_Methods
{
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        $store = null;
        $websiteCode = Mage::app()->getRequest()->getParam('website', false);
        if ($websiteCode) {
            /** @var Mage_Core_Model_Website $website */
            $website = Mage::getModel('core/website')->load($websiteCode);
            $store = $website->getDefaultStore();
        }

        $methods = array(array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- None --')));

        /** @var Mage_Shipping_Model_Config $configModel */
        $configModel = Mage::getSingleton('shipping/config');
        $carriers = $configModel->getActiveCarriers($store);
        foreach ($carriers as $carrierCode => $carrierModel) {
            if (!$carrierModel->isActive() && (bool)$isActiveOnlyFlag === true) {
                continue;
            }
            $carrierMethods = $carrierModel->getAllowedMethods();
            if (!$carrierMethods) {
                continue;
            }
            $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');
            $methods[$carrierCode] = array(
                'label' => $carrierTitle,
                'value' => array(),
            );
            foreach ($carrierMethods as $methodCode => $methodTitle) {
                if ('about' === $methodCode) {
                    continue;
                }
                $methods[$carrierCode]['value'][] = array(
                    'label' => '[' . $carrierCode . '] ' . $methodTitle . ' (' . $methodCode . ')',
                    'value' => $carrierCode . '_' . $methodCode,
                );
            }
        }

        return $methods;
    }
}

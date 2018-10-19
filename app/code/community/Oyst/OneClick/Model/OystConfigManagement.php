<?php

class Oyst_OneClick_Model_OystConfigManagement
{
    public function saveOystConfig(array $oystConfig)
    {
        Mage::getConfig()->saveConfig(
            Oyst_OneClick_Helper_Constants::CONFIG_PATH_OYST_CONFIG_MERCHANT_ID, $oystConfig['merchant_id']
        );
        Mage::getConfig()->saveConfig(
            Oyst_OneClick_Helper_Constants::CONFIG_PATH_OYST_CONFIG_SCRIPT_TAG, $oystConfig['script_tag']
        );
        Mage::getConfig()->saveConfig(
            Oyst_OneClick_Helper_Constants::CONFIG_PATH_OYST_CONFIG_ENDPOINTS, json_encode($oystConfig['endpoints'])
        );

        Mage::app()->cleanCache('CONFIG');

        return true;
    }

    public function getEcommerceConfig()
    {
        $carriers = Mage::getSingleton('shipping/config')->getActiveCarriers();
        array_shift($carriers);

        $allowedCountryCodes = explode(',', (string)Mage::getStoreConfig('general/country/allow'));
        $countries = Mage::getModel('directory/country')->getCollection()
            ->addFieldToFilter('country_id', array('in' => $allowedCountryCodes))
            ->toOptionArray(false);

        $orderStatuses = Mage::getModel('sales/order_config')->getStatuses();

        return Mage::getModel('oyst_oneclick/oystConfig_ecommerce_builder')->buildOystConfigEcommerce(
            $carriers,
            $countries,
            $orderStatuses
        );
    }
}

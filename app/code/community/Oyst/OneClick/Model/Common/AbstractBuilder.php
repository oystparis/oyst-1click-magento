<?php

abstract class Oyst_OneClick_Model_Common_AbstractBuilder
{
    protected function buildOystCommonCountry($code, $label)
    {
        $oystCommonCountry = array();

        $oystCommonCountry['code'] = $code;
        $oystCommonCountry['label'] = $label;

        return $oystCommonCountry;
    }

    protected function buildOystCommonShop(
        Mage_Core_Model_Store $store
    )
    {
        $oystCommonShop = array();

        $oystCommonShop['code'] = $store->getCode();
        $oystCommonShop['label'] = $store->getName();
        $oystCommonShop['url'] = $store->getBaseUrl();

        return $oystCommonShop;
    }
}

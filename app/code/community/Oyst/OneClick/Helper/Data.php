<?php

class Oyst_OneClick_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function addQuoteExtraData($quote, $key, $value)
    {
        $extraData = json_decode($quote->getOystExtraData(), true);

        if(empty($extraData)) {
            $extraData = array();
        }

        $extraData[$key] = $value;

        $quote->setOystExtraData(json_encode($extraData));
    }

    public function getSalesObjectExtraData($salesObject, $key)
    {
        $extraData = json_decode($salesObject->getOystExtraData(), true);
        return isset($extraData[$key]) ? $extraData[$key] : null;
    }
}
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

    public function handleQuoteErrors(Mage_Sales_Model_Quote $quote)
    {
        if ($quote->getHasError()) {
            $errorMessages = array();
            foreach ($quote->getErrors() as $error) {
                $errorMessages[] = $error->getCode();
            }
            throw new Mage_Checkout_Exception(implode('\n', $errorMessages));
        }
        return $this;
    }
}
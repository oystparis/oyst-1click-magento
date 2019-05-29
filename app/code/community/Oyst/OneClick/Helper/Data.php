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

    public function mapMagentoExceptionCodeToOystErrorCode($exceptionCode)
    {
        switch($exceptionCode) {
            case 3:
                return 'address-validation-failed';
            case 2:
                return 'coupon-error';
            case 1:
                return 'unhandled-address';
            default:
                return 'generic-error';
        }
    }

    public function validateAddress(Mage_Customer_Model_Address_Abstract $address, $store = null)
    {
        if (($validateRes = $address->validate()) !== true) {
            throw new Mage_Checkout_Exception(implode('\n', $validateRes), 3);
        }

        $allowCountries = explode(',', (string)Mage::getStoreConfig('general/country/allow', $store));
        if (!in_array($address->getCountryId(), $allowCountries)) {
            throw new Mage_Checkout_Exception('', 1);
        }
        
        return $this;
    }
}
<?php

class Oyst_OneClick_Model_OystConfig_Ecommerce_Builder
{
     public function buildOystConfigEcommerce(
        array $carriers,
        array $countries,
        array $orderStatuses,
        array $stores
    )
    {
        $oystConfigEcommerce = array();

        $oystConfigEcommerce['shipping_methods'] = $this->buildOystConfigEcommerceShippingMethods($carriers);
        $oystConfigEcommerce['countries'] = $this->buildOystConfigEcommerceCountries($countries);
        $oystConfigEcommerce['order_statuses'] = $this->buildOystConfigEcommerceOrderStatuses($orderStatuses);
        $oystConfigEcommerce['shops'] = $this->buildOystConfigEcommerceShops($stores);

        return $oystConfigEcommerce;
    }

    protected function buildOystConfigEcommerceShippingMethods(array $carriers)
    {
        $result = [];

        foreach ($carriers as $ccode => $carrier) {
            foreach($carrier->getAllowedMethods() as $mcode => $mlabel) {
                $oystConfigEcommerceShippingMethod = array();

                $oystConfigEcommerceShippingMethod['label'] = $mlabel;
                $oystConfigEcommerceShippingMethod['reference'] = $ccode.'_'.$mcode;

                $result[] = $oystConfigEcommerceShippingMethod;
            }
        }

        return $result;
    }

    protected function buildOystConfigEcommerceCountries(array $countries)
    {
        $result = [];

        foreach ($countries as $country) {
            $oystConfigEcommerceCountry = array();

            $oystConfigEcommerceCountry['label'] = $country['label'];
            $oystConfigEcommerceCountry['code'] = $country['value'];

            $result[] = $oystConfigEcommerceCountry;
        }

        return $result;
    }

    protected function buildOystConfigEcommerceOrderStatuses(array $orderStatuses)
    {
        $result = [];

        foreach ($orderStatuses as $key => $label) {
            $oystConfigEcommerceOrderStatus = array();

            $oystConfigEcommerceOrderStatus['label'] = $label;
            $oystConfigEcommerceOrderStatus['code'] = $key;

            $result[] = $oystConfigEcommerceOrderStatus;
        }

        return $result;
    }

    protected function buildOystConfigEcommerceShops(array $stores)
    {
        $result = [];

        foreach ($stores as $store) {
            $oystConfigEcommerceShop = array();

            $oystConfigEcommerceShop['code'] = $store->getCode();
            $oystConfigEcommerceShop['url'] = $store->getBaseUrl();
            $oystConfigEcommerceShop['label'] = $store->getName();

            $result[] = $oystConfigEcommerceShop;
        }

        return $result;
    }
}
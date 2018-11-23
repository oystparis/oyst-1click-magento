<?php

class Oyst_OneClick_Model_ConstantsMapper
{
    public function mapMagentoProductTypeToOystCheckoutItemType($magentoProductType)
    {
        $result = null;

        switch($magentoProductType) {
            case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
                $result = Oyst_OneClick_Helper_Constants::ITEM_TYPE_SIMPLE;
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:
                $result = Oyst_OneClick_Helper_Constants::ITEM_TYPE_VIRTUAL;
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                $result = Oyst_OneClick_Helper_Constants::ITEM_TYPE_BUNDLE;
                break;
            case Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE:
                $result = Oyst_OneClick_Helper_Constants::ITEM_TYPE_DOWNLOADABLE;
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                $result = Oyst_OneClick_Helper_Constants::ITEM_TYPE_VARIANT;
                break;
        }

        return $result;
    }
}
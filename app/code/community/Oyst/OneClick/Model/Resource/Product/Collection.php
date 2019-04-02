<?php

class Oyst_OneClick_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    public function isEnabledFlat()
    {
        return false;
    }
}
<?php

class Oyst_OneClick_Helper_SalesRule
{
    public function isItemFreeProduct(Mage_Sales_Model_Quote_Item $item)
    {
        return $item->getPrice() == 0 && $item->getProduct()->getPrice() > 0;
    }
}

<?php
/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Payment Controller
 */
class Oyst_OneClick_TestController extends Mage_Core_Controller_Front_Action
{
    private $products = [875];


    public function indexAction()
    {
        $product        = Mage::getModel('catalog/product')->load(404); //configurable product
        echo $product->getPrice();
        $optProduct     = Mage::getModel('catalog/product')->load(237); //associated simple product
        $confAttributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $pdtOptValues   = array();
        foreach ($confAttributes as $attribute) {
            $attrCode    = $attribute['attribute_code'];
            $attrId      = $attribute['attribute_id'];
            $optionValue = $optProduct->getData($attrCode);
            $pdtOptValues[$attrId] = $optionValue;
        }

        $product->addCustomOption('attributes', serialize($pdtOptValues));



    }

    public function testAction()
    {
        $stockItems = Mage::getModel('cataloginventory/stock_item')
            ->getCollection()
            ->addFieldToFilter('product_id', array('in' => [231, 232, 233]));

        $itemValues = $statusValues = array();

        foreach ($stockItems as $stockItem) {
            $qty = $stockItem->getQty() - 2;
            $itemValues[] = '(' . $stockItem->getItemId() . ', ' . $qty . ')';
            $statusValues[] = '(' . $stockItem->getProductId(). ', ' . '1, 1, ' . $qty . ')';
        }

        $query = "INSERT INTO cataloginventory_stock_item (item_id, qty) VALUES " . implode(',', $itemValues) .
            " ON DUPLICATE KEY UPDATE qty=VALUES(qty);";
        $query .= "INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty) VALUES " .
            implode(',', $statusValues). " ON DUPLICATE KEY UPDATE qty=VALUES(qty);";


    }

    public function iindexAction()
    {
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter(
                'entity_id',
                array('in' => [404, 237])
            )
            ->addAttributeToSelect('*');



        foreach ($products as $product) {
            var_dump($product->getImageUrl());
        }


    }
}

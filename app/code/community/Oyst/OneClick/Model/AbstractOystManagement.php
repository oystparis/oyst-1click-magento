<?php

class Oyst_OneClick_Model_AbstractOystManagement
{
    public function __construct()
    {
        // Disable country/state validation
        Mage::app()->getStore()->setConfig(Mage_Directory_Helper_Data::XML_PATH_STATES_REQUIRED, '');
    }
    
    protected function getMagentoCustomer($email, $websiteId = null)
    {
        return Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($email);
    }

    protected function getMagentoQuote($id)
    {
        $quote = Mage::getModel('sales/quote')->loadActive($id);

        if (!$quote->getId()) {
            throw new Exception('Invalid Quote');
        }

        return $quote;
    }

    protected function getMagentoQuoteByOystId($oystId)
    {
        return Mage::getModel('sales/quote')->getCollection()
            ->addFieldToFilter('oyst_id', $oystId)
            ->addFieldToFilter('is_active', 1)
            ->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_DESC)
            ->getFirstItem();
    }

    protected function getMagentoProductsById($ids, $storeId)
    {
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter('entity_id', array('in' => $ids))
            ->addAttributeToSelect('*')
            ->setStore($storeId)
            ->addFinalPrice();

        Mage::dispatchEvent(
            'oyst_oneclick_model_oyst_management_get_magento_products_by_id',
            array('products' => $products, 'store_id' => $storeId)
        );

        foreach ($products as $product) {
            $product->setOystImageUrl(
                Mage::helper('catalog/image')->init($product, 'small_image')->resize(135)->__toString()
            );
        }

        return $products;
    }

    protected function getMagentoCoupon($oystCoupons)
    {
        if (empty($oystCoupons)) {
            return Mage::getModel('salesrule/coupon');
        } else {
            foreach ($oystCoupons as $oystCoupon) {
                $coupon = Mage::getModel('salesrule/coupon')->load($oystCoupon->getCode(), 'code');
                if (!$coupon->getId()) {
                    throw new Exception('Invalid coupon code : '.$oystCoupon->getCode());
                }
                return $coupon;
            }
        }
    }

    protected function disableRegionRequired()
    {
        Mage::register(
            Oyst_OneClick_Helper_Constants::DISABLE_REGION_REQUIRED_REGISTRY_KEY, true, true
        );
    }
    
    protected function getMagentoOrderByOystId($oystId)
    {
        return Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('oyst_id', $oystId)
            ->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_DESC)
            ->getFirstItem();
    }
}
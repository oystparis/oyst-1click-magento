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
 * Order Model
 */
class Oyst_OneClick_Model_Magento_Order
{
    /** @var Mage_Sales_Model_Quote|null $quote */
    private $quote = null;

    /** @var Mage_Sales_Model_Order $order */
    private $order = null;

    private $additionalData = array();

    public function __construct(Mage_Sales_Model_Quote $quote)
    {
        $this->quote = $quote;
    }

    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    public function buildOrder()
    {
        try {
            $this->order = $this->placeOrder();
            $this->order->setCreatedAt($this->quote->getCreatedAt());
            $this->order->save();
            $this->quote->setIsActive(false)->save();
        } catch (Exception $e) {
            Mage::helper('oyst_oneclick')->log('Error create order: ' . $e->getMessage());
            throw $e;
        }
    }

    private function placeOrder()
    {
        if (version_compare(Mage::getVersion(), '1.4.1', '>=')) {
            /** @var Mage_Sales_Model_Service_Quote $service */
            $service = Mage::getModel('sales/service_quote', $this->quote);
            $service->setOrderData($this->additionalData);
            $service->submitAll();

            return $service->getOrder();
        }

        // Magento version 1.4.0 backward compatibility code

        /** @var Mage_Sales_Model_Convert_Quote $quoteConverter */
        $quoteConverter = Mage::getSingleton('sales/convert_quote');

        /** @var Mage_Sales_Model_Order $orderObj */
        $orderObj = $quoteConverter->addressToOrder($this->quote->getShippingAddress());

        $orderObj->setBillingAddress($quoteConverter->addressToOrderAddress($this->quote->getBillingAddress()));
        $orderObj->setShippingAddress($quoteConverter->addressToOrderAddress($this->quote->getShippingAddress()));
        $orderObj->setPayment($quoteConverter->paymentToOrderPayment($this->quote->getPayment()));

        $items = $this->quote->getShippingAddress()->getAllItems();

        foreach ($items as $item) {
            /** @var Mage_Sales_Model_Quote_Item $orderItem */
            $orderItem = $quoteConverter->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $orderObj->addItem($orderItem);
        }

        $orderObj->addData($this->additionalData);

        $orderObj->setCanShipPartiallyItem(false);
        $orderObj->place();
        $orderObj->save();

        return $orderObj;
    }
}

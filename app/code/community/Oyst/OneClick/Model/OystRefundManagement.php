<?php

class Oyst_OneClick_Model_OystRefundManagement extends Oyst_OneClick_Model_AbstractOystManagement
{
    public function createMagentoCreditmemo($oystId, array $oystRefund)
    {
        $order = $this->getMagentoOrderByOystId($oystId);

        Mage::getModel('oyst_oneclick/oystPaymentManagement')->handleMagentoOrdersToRefund(array($order->getId()));

        return true;
    }
}
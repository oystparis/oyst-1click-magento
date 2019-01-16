<?php

class Oyst_OneClick_Api_OystOrderController extends Oyst_OneClick_Controller_Api_AbstractController
{
    public function createMagentoCreditmemoAction()
    {
        $oystOrderId = $this->getRequest()->getParam('oyst_order_id');
        $oystRefund = $this->getRequest()->getParam('oyst_refund');

        try {
            $result = Mage::getModel('oyst_oneclick/oystRefundManagement')->createMagentoCreditmemo($oystOrderId, $oystRefund['oystRefund']);

            $this->getResponse()->setBody(json_encode($result));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
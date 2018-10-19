<?php

class Oyst_OneClick_Api_OystOrderController extends Oyst_OneClick_Controller_Api_AbstractController
{
    public function getOystOrderFromMagentoOrderAction()
    {
        $oystOrderId = $this->getRequest()->getParam('oyst_order_id');

        $result = Mage::getModel('oyst_oneclick/oystOrderManagement')->getOystOrderFromMagentoOrder($oystOrderId);

        $this->getResponse()->setBody(json_encode($result));
    }

    public function createOrderFromOystOrderAction()
    {
        $oystOrder = json_decode($this->getRequest()->getParam('oyst_order'), true);

        if ($oystOrder === null) {
            // TODO
        }

        $result = Mage::getModel('oyst_oneclick/oystOrderManagement')->createOrderFromOystOrder($oystOrder['oystOrder']);

        $this->getResponse()->setBody(json_encode($result));
    }

    public function syncMagentoOrderWithOystOrderStatusAction()
    {
        $oystOrderId = $this->getRequest()->getParam('oyst_order_id');
        $oystOrder = json_decode($this->getRequest()->getParam('oyst_order'), true);

        if ($oystOrder === null) {
            // TODO
        }

        $result = Mage::getModel('oyst_oneclick/oystOrderManagement')->syncMagentoOrderWithOystOrderStatus(
            $oystOrderId, $oystOrder['oystOrder']
        );

        $this->getResponse()->setBody(json_encode($result));
    }
}
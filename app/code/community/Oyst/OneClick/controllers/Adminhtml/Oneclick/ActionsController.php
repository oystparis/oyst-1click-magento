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
 * Adminhtml Actions Controller
 */
class Oyst_OneClick_Adminhtml_OneClick_ActionsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test if user can access to this sections
     *
     * @return bool
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/actions');
    }

    /**
     * Skip setup by setting the config flag accordingly
     */
    public function skipAction()
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');
        $helper->setIsInitialized();
        $this->_redirectReferer();
    }

    /**
     * Cancel and refund order
     */
    public function cancelAndRefundAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        if (empty($order->getOystOrderId())) {
            Mage::throwException('Order has no OystOrderId');
        }

        /** @var Oyst_OneClick_Helper_Order_Data $helper */
        $helper = Mage::helper('oyst_oneclick/order_data');

        /** @var Oyst_OneClick_Model_Order_ApiWrapper $orderApi */
        $orderApi = Mage::getModel('oyst_oneclick/order_apiWrapper');

        try {
            $response = $orderApi->updateOrder($order->getOystOrderId(), 'refunded');
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $helper->cancelAndRefund($order);

        //$this->_redirectReferer();
        Mage::app()->getResponse()->setRedirect($this->getRequest()->getServer('HTTP_REFERER'));
        Mage::app()->getResponse()->sendResponse();
    }
}

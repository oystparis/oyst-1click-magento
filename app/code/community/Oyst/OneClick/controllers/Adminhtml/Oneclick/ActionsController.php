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
     *
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
        $identifier = $this->getRequest()->getParam('identifier');
        $helper->setIsInitialized($identifier);
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

        $oystOrderId = $order->getOystOrderId();
        if (empty($oystOrderId)) {
            Mage::throwException('Order has no OystOrderId');
        }

        /** @var Oyst_OneClick_Model_Order_Data $helper */
        $model = Mage::getModel('oyst_oneclick/order');

        /** @var Oyst_OneClick_Model_Order_ApiWrapper $orderApi */
        $orderApi = Mage::getModel('oyst_oneclick/order_apiWrapper');

        try {
            $response = $orderApi->updateOrder($order->getOystOrderId(), 'refunded');
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $model->cancelAndRefund($order);

        //$this->_redirectReferer();
        Mage::app()->getResponse()->setRedirect($this->getRequest()->getServer('HTTP_REFERER'));
        Mage::app()->getResponse()->sendResponse();
    }

    /**
     * Magento method for init layout, menu and breadcrumbs
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_ActionsController
     */
    protected function _initAction()
    {
        $this->_activeMenu();

        return $this;
    }

    /**
     * Active menu
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_ActionsController
     */
    protected function _activeMenu()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $this->loadLayout()
            ->_setActiveMenu('oyst_oneclick/oneclick_actions')
            ->_title($oystHelper->__('Actions'))
            ->_addBreadcrumb($oystHelper->__('Actions'), $oystHelper->__('Actions'));

        return $this;
    }

    /**
     * Print action page
     *
     * @retun null
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }
}

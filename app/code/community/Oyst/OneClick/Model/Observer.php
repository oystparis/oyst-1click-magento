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
 * Observer Model
 */
class Oyst_OneClick_Model_Observer
{
    /**
     * Notifications system configuration path.
     */
    const NOTIFICATIONS_SECTION = 'oyst_notifications';

    const XML_PATH_ONECLICK_ENABLE = 'oyst/oneclick/enable';

    const XML_PATH_ONECLICK_IS_ENABLE = 'oyst/oneclick/is_enable';

    const PAYMENT_METHOD = 'oyst_oneclick';

    /**
     * Change order status after partial refund.
     *
     * @param Varien_Event_Observer $observer
     */
    public function setStatusAfterRefund(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();

        $status = Mage::getStoreConfig('payment/oyst_freepay/refund_partial_authorized');
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status);
    }

    /**
     * Update order status on cancel event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_OneClick_Model_Observer
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getPayment()->getOrder();

        if (!$oystHelper->isPaymentMethodOyst($order)) {
            return;
        }

        $orderStatusOnCancel = Mage::getStoreConfig('payment/oyst_freepay/payment_cancelled');

        if (Mage_Sales_Model_Order::STATE_HOLDED === $orderStatusOnCancel) {
            if ($order->canHold()) {
                $order
                    ->hold()
                    ->addStatusHistoryComment(
                        $oystHelper->__(
                            'Cancel Order asked. Waiting for FreePay notification. Payment Id: "%s".',
                            $order->getTxnId()
                        )
                    )
                    ->save();
            }
        }

        if (Mage_Sales_Model_Order::STATE_CANCELED === $orderStatusOnCancel) {
            if ($order->canCancel()) {
                $order
                    ->cancel()
                    ->addStatusHistoryComment(
                        $oystHelper->__('Success Cancel Order. Payment Id: "%s".', $order->getTxnId())
                    )
                    ->save();

                return $this;
            }
        }

        $oystHelper->log('Start send cancelOrRefund of order id : ' . $order->getId());

        /** @var Oyst_OneClick_Model_Payment_ApiWrapper $response */
        $response = Mage::getModel('oyst_oneclick/payment_apiWrapper');
        $response->cancelOrRefund($order->getPayment()->getLastTransId());

        $oystHelper->log('Waiting from cancelOrRefund notification of order id : ' . $order->getId());

        return $this;
    }

    /**
     * Invalidate config cache.
     *
     * @param Varien_Event_Observer $observer
     */
    public function invalidateCache(Varien_Event_Observer $observer)
    {
        Mage::app()->getCacheInstance()->invalidateType('config');
    }

    public function redirectToNotificationsGrid(Varien_Event_Observer $observer)
    {
        $controllerAction = $observer->getEvent()->getControllerAction();
        $section = $controllerAction->getRequest()->getParam('section');

        if ('oyst_notifications' === $section) {
            $controllerAction->getResponse()->clearHeaders()
                ->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/oyst_notifications/'))
                ->sendHeadersAndExit();
        }
    }

    public function appendOneClickButton(Varien_Event_Observer $observer)
    {
        if ('checkout_cart_index' === $observer->getAction()->getFullActionName()) {
            $layout = $observer->getLayout();
            $position = Mage::getStoreConfig('oyst/oneclick/checkout_cart_button_position');

            switch ($position) {
                case 'top':
                    $layout->getUpdate()->addHandle('oneclick_checkout_cart_top');
                    break;
                case 'bottom':
                    $layout->getUpdate()->addHandle('oneclick_checkout_cart_bottom');
                    break;
            }
        }
    }

    /**
     * Activate tabs when api key is entered.
     *
     * @param Varien_Event_Observer $observer
     */
    public function activateTabs(Varien_Event_Observer $observer)
    {
        $flag = Mage::getStoreConfigFlag('oyst/oneclick/api_login');
        $groups = $observer->getConfig()->getNode('sections/oyst_oneclick/groups');

        foreach ($groups->asArray() as $key => $group) {
            if (!$flag && 'informations' != $key && 'general' != $key) {
                $observer->getConfig()->setNode('sections/oyst_oneclick/groups/' . $key, '');
            }
        }
    }

    /**
     * Check if the module must be considered as enable
     *  - check module state
     *  - ip allowed
     *  - checkout onepage xpath filled
     *
     * @param Varien_Event_Observer $observer
     */
    public function isEnable(Varien_Event_Observer $observer)
    {
        $oneclickEnable = Mage::getStoreConfig(self::XML_PATH_ONECLICK_ENABLE);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        if ($oneclickEnable && $oystHelper->isIpAllowed()) {
            Mage::app()->getStore()->setConfig(self::XML_PATH_ONECLICK_IS_ENABLE, true);

            $xpath = Mage::getStoreConfig(Oyst_OneClick_Block_OneClick::XML_PATH_CHECKOUT_ONEPAGE_XPATH_TO_APPEND_ONECLICK_BUTTON);
            if ('/checkout/onepage/' === Mage::app()->getRequest()->getPathInfo() && empty($xpath)) {
                Mage::app()->getStore()->setConfig(self::XML_PATH_ONECLICK_IS_ENABLE, false);
            }
        }
    }

    /**
     * OneClick payment info.
     *
     * @param Varien_Event_Observer $observer
     */
    public function oneclickPaymentInfoSpecificInformation(Varien_Event_Observer $observer)
    {
        /* @var $payment Mage_Payment_Model_Info */
        $payment = $observer->getEvent()->getPayment();

        if (self::PAYMENT_METHOD != $payment->getMethodInstance()->getCode()
            && 'info' != $observer->getEvent()->getBlock()->getBlockAlias()
        ) {
            return;
        }

        $observer->getEvent()->getTransport()->setData(array(
            Mage::helper('oyst_oneclick')->__('Credit Card No Last 4') => $payment->getCcLast4(),
        ));

        return;
    }

    /**
     * Update notifications status.
     *
     * @return Oyst_OneClick_Model_Observer
     */
    public function updateNotificationsStatus()
    {
        try {
            /** @var Oyst_OneClick_Model_Notification $notification */
            $notification = Mage::getModel('oyst_oneclick/notification');
            $notification->updateNotificationsStatus();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}

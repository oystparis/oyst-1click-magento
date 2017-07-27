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
 * Adminhtml Observer Model
 */
class Oyst_OneClick_Model_Adminhtml_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Custom cancel and refund
     *
     * @return
     */
    public function cancelAndRefundButton(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $paymentMethod = $block->getOrder()->getPayment()->getMethodInstance();
            $order = Mage::registry('current_order');

            if ($paymentMethod instanceof Oyst_OneClick_Model_Payment_Method_Oneclick
                && Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/invoice')
                && !in_array($order->getStatus(), array('closed'))
            ) {
                $url = Mage::helper('adminhtml')->getUrl(
                    'adminhtml/oneclick_actions/cancelAndRefund',
                    array('order_id' => $order->getId())
                );

                $block->addButton('cancelAndRefund_submit', array(
                    'label' => Mage::helper('oyst_oneclick')->__('Cancel and Refund'),
                    'onclick' => "setLocation('" . $url . "')",
                    'class' => ''
                ));
            }
        }
    }
}

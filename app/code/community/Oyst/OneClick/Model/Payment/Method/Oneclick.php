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
 * Payment_Method_Oyst Model
 */
class Oyst_OneClick_Model_Payment_Method_Oneclick extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_METHOD_NAME = 'Oyst 1-Click';

    protected $_code = 'oyst_oneclick';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;
    protected $_canManageRecurringProfiles = false;
    protected $_canReviewPayment = true;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::PAYMENT_METHOD_NAME;
    }

    /**
     * Refund specified amount for payment.
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');

        if ($helper->getOpenRefundTransaction($payment)->getId()) {
            Mage::throwException($helper->__('There is already one credit memo in the queue.'));
        }

        try {
            /** @var Oyst_OneClick_Model_ApiWrapper_Type_Order $api */
            $api = Mage::getModel('oyst_oneclick/apiWrapper_type_order');
            $api->refund($payment->getOrder()->getOystOrderId(), $amount);
        } catch (Exception $e) {
            /** @var Oyst_OneClick_Helper_Data $oystHelper */
            $oystHelper = Mage::helper('oyst_oneclick');

            $message = sprintf(
                'Unable to refund Oyst order %s (%s - %s).',
                $payment->getOrder()->getOystOrderId(),
                $e->getMessage(),
                $e->getCode()
            );

            $oystHelper->log($message);

            Mage::throwException($helper->__($message));
        }

        return $this;
    }

    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);
        return true;
    }
}

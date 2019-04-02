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
class Oyst_OneClick_Model_Payment_Method_OneClick extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_METHOD_NAME = 'Oyst OneClick';

    protected $_code = 'oyst_oneclick';

    protected $_canRefund = true;

    protected $_canRefundInvoicePartial = true;

    protected $_canUseCheckout = false;

    /**
     * @return string
     */
    public function getName()
    {
        return self::PAYMENT_METHOD_NAME;
    }

    public function isAvailable($quote = null)
    {
        return Mage::getStoreConfig('oyst_oneclick/general/enabled');
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $creditmemo = $payment->getCreditmemo();
        $order = $payment->getOrder();

        Mage::getModel('oyst_oneclick/oystPaymentManagement')->handleMagentoOrdersToRefund(
            array($order->getId() => $creditmemo->getBaseGrandTotal()), true
        );

        return $this;
    }
}

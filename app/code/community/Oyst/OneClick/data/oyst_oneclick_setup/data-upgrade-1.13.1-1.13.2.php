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

/* @var Mage_Core_Model_Resource_Setup $this */
$installer = $this;

$data     = array();
$data[] = array(
    'status' => Oyst_OneClick_Helper_Data::STATUS_OYST_PAYMENT_ACCEPTED,
    'label'  => 'Oyst Payment Accepted',
);
$data[] = array(
    'status' => Oyst_OneClick_Helper_Data::STATUS_OYST_PAYMENT_FRAUD,
    'label'  => 'Oyst Payment Fraud',
);
$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status'),
    array('status', 'label'),
    $data
);

$data   = array();
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Data::STATUS_OYST_PAYMENT_ACCEPTED,
    'state'      => Mage_Sales_Model_Order::STATE_PROCESSING,
    'is_default' => 0
);
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Data::STATUS_OYST_PAYMENT_FRAUD,
    'state'      => Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
    'is_default' => 0
);

$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status_state'),
    array('status', 'state', 'is_default'),
    $data
);


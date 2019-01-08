<?php

// Must do with 'add attribute' from 'sales module' not '$this => Mage_Core_Model_Resource_Setup'
$sales = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$sales->startSetup();

// Add attribute to order and quote for synchronisation
$sales->addAttribute(
    'order',
    'oyst_id',
    array(
        'type' => 'varchar',
    )
);
$sales->addAttribute(
    'quote',
    'oyst_id',
    array(
        'type' => 'varchar',
    )
);
// Add attribute to order and quote for synchronisation
$sales->addAttribute(
    'order',
    'oyst_extra_data',
    array(
        'type' => 'text',
    )
);
$sales->addAttribute(
    'quote',
    'oyst_extra_data',
    array(
        'type' => 'text',
    )
);

$data     = array();
$data[] = array(
    'status' => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_CANCELED,
    'label'  => Mage::helper('oyst_oneclick')->__('Oyst OneClick Canceled'),
);
$data[] = array(
    'status' => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_CAPTURED,
    'label'  => Mage::helper('oyst_oneclick')->__('Oyst OneClick Payment Captured'),
);
$data[] = array(
    'status' => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_TO_CAPTURE,
    'label'  => Mage::helper('oyst_oneclick')->__('Oyst OneClick Payment to Capture'),
);
$data[] = array(
    'status' => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_WAITING_TO_CAPTURE,
    'label'  => Mage::helper('oyst_oneclick')->__('Oyst OneClick Payment Waiting to Capture'),
);
$data[] = array(
    'status' => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_WAITING_VALIDATION,
    'label'  => Mage::helper('oyst_oneclick')->__('Oyst OneClick Payment Waiting Validation'),
);
$sales->getConnection()->insertArray(
    $sales->getTable('sales/order_status'),
    array('status', 'label'),
    $data
);

$data   = array();
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_CAPTURED,
    'state'      => Mage_Sales_Model_Order::STATE_PROCESSING,
    'is_default' => 0
);
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_TO_CAPTURE,
    'state'      => Mage_Sales_Model_Order::STATE_PROCESSING,
    'is_default' => 0
);
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_WAITING_TO_CAPTURE,
    'state'      => Mage_Sales_Model_Order::STATE_PROCESSING,
    'is_default' => 0
);
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_PAYMENT_WAITING_VALIDATION,
    'state'      => Mage_Sales_Model_Order::STATE_NEW,
    'is_default' => 0
);
$data[] = array(
    'status'     => Oyst_OneClick_Helper_Constants::OYST_ORDER_STATUS_CANCELED,
    'state'      => Mage_Sales_Model_Order::STATE_CANCELED,
    'is_default' => 0
);

$sales->getConnection()->insertArray(
    $sales->getTable('sales/order_status_state'),
    array('status', 'state', 'is_default'),
    $data
);

$sales->endSetup();
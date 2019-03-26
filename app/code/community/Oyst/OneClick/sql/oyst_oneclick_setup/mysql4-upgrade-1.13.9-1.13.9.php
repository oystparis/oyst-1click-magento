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

/* @var $installer Mage_Checkout_Model_Mysql4_Setup */
$installer = $this;

/* @var $this Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$sales = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$sales->startSetup();

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

$installer->endSetup();

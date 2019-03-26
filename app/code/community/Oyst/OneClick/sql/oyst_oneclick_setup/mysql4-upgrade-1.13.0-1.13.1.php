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

$installer->getConnection()->dropForeignKey(
    $installer->getTable('oyst_oneclick/notification'),
    $installer->getFkName('oyst_oneclick/notification', 'order_id', 'sales/order', 'entity_id')
);

$installer->endSetup();

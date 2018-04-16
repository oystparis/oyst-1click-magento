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

$connection = $installer->getConnection();

$connection->update(
    $installer->getTable('core/config_data'),
    array('path' => new Zend_Db_Expr('REPLACE(path, "oyst/oneclick/button_before_addtocart", "oyst/oneclick/product_page_button_position")')),
    $connection->quoteInto('path LIKE ?', 'oyst/oneclick/button_before_addtocart')
);

$installer->endSetup();

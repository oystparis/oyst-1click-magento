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

$installer->startSetup();
$setup = $installer->getConnection();

$select = $setup->select()
    ->from($installer->getTable('core/config_data'), 'COUNT(*)')
    ->where('path=?', 'oyst/oneclick/mode')
    ->where('value=?', 'preprod');

if (0 < $setup->fetchOne($select)) {
    // Delete obsolete environment preprod
    $installer->getConnection()->delete(
        $installer->getTable('core_config_data'),
        "`path` = 'oyst/oneclick/mode' AND `value` = 'preprod'"
    );
}

$installer->endSetup();

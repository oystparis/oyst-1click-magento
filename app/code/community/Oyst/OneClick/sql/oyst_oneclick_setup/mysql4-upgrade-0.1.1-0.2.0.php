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

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$installer = $this;
$connection = $installer->getConnection();

$connection->modifyColumn(
    $this->getTable('oyst_oneclick/notification'),
    'oyst_data',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'size', null,
        'comment' => 'Oyst Data',
    )
);

$connection->modifyColumn(
    $this->getTable('oyst_oneclick/notification'),
    'mage_response',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'size', null,
        'comment' => 'Magento Response',
    )
);

$this->endSetup();

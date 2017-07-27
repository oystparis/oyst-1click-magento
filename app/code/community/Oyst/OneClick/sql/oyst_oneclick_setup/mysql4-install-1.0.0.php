<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// Create 'oyst_notification' table
$table = $installer->getConnection()
    ->newTable($installer->getTable('oyst_oneclick/notification'))
    ->addColumn(
        'notification_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true),
        'Unique identifier'
    )
    ->addColumn('event', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => false), 'Notification Type')
    ->addColumn('oyst_data', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => true), 'Oyst Data')
    ->addColumn('import_start', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => true), 'Import Start Number')
    ->addColumn('import_end', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => true), 'Import End Number')
    ->addColumn('import_qty', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => true), 'Import Number')
    ->addColumn('import_remaining', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => true), 'Import Remaining')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('nullable' => true), 'Magento Order Id')
    ->addColumn('products_id', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Products Id')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => true), 'Status of process')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => false), 'Created At')
    ->addColumn('executed_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => false), 'Updated At')
    ->addForeignKey(
        $installer->getFkName('oyst_oneclick/notification', 'order_id', 'sales/order', 'entity_id'),
        'order_id',
        $installer->getTable('sales/order'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    );

if ($installer->getConnection()->isTableExists($table->getName())) {
    $installer->getConnection()->dropTable($table->getName());
}

$installer->getConnection()->createTable($table);


// Must do with 'add attribute' from 'sales module' not '$this => Mage_Core_Model_Resource_Setup'
$sales = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$sales->startSetup();

// Add attribute to order and quote for synchronisation
$sales->addAttribute(
    'order', 'oyst_order_id', array(
        'type' => 'varchar'
    )
);
$sales->addAttribute(
    'quote', 'oyst_order_id', array(
        'type' => 'varchar'
    )
);

$installer->endSetup();
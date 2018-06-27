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

$installer->run("UPDATE `{$this->getTable('eav_attribute')}` SET `attribute_code` = 'is_oneclick_disable_on_product' WHERE `attribute_code` = 'is_oneclick_active_on_product';");

$installer->endSetup();

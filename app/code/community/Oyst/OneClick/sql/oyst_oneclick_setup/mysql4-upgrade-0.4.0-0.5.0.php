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
$installer = $this;

$attributeCode = 'is_oneclick_active_on_product';
$entityType    = 'catalog_product';
$groupName     = 'Button 1-Click';

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

// Create new attribute
$setup->addAttribute($entityType, $attributeCode, array(
    'input'            => 'select',
    'type'             => 'int',
    'label'            => 'Disable 1-Click button',
    'visible'          => false,
    'required'         => false,
    'user_defined'     => true,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'default'          => '0',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'source'           => 'eav/entity_attribute_source_boolean',
));

// Get new attribute id.
$attributeId = $setup->getAttributeId($entityType, $attributeCode);

// Get attribute set ids
$attributeSets = $setup->getAllAttributeSetIds();

// Add new group to attribute sets and assign new attribute to the group
foreach ($attributeSets as $attributeSet) {
    $setup->addAttributeGroup($entityType, $attributeSet, $groupName, 999);
    $groupId = $setup->getAttributeGroupId('catalog_product', $attributeSet, $groupName);
    $setup->addAttributeToGroup($entityType, $attributeSet, $groupId, $attributeId);
}

$installer->endSetup();

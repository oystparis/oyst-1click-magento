<?php
/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license All rights reserved, Oyst
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Mode Model
 */
class Oyst_OneClick_Model_System_Config_Source_Customattributes
{
    public function toOptionArray()
    {
        $type = Mage::getModel('eav/entity_type');
        $type->loadByCode('catalog_product');

        $attributeModel = Mage::getModel('eav/entity_attribute');
        $attrs = $attributeModel->getCollection()
            ->setEntityTypeFilter($type)
            ->addFieldToFilter('frontend_input', 'select');

        $array = array();
        /** @var $attr Mage_Eav_Model_Entity_Attribute */
        foreach ($attrs as $id => $attr) {
            $array[$id] = array(
                'value' => $attr->getId(),
                'label' => $attr->getFrontendLabel(),
            );
        }

        $attributes = $attributeModel->getAttributeCodesByFrontendType('weee');
        foreach ($attributes as $attribute) {
            $attr = $attributeModel->loadByCode('catalog_product', $attribute);
            $array[$attr->getId()] = array(
                'value' => $attr->getId(),
                'label' => $attr->getFrontendLabel(),
            );
        }

        return $array;
    }
}

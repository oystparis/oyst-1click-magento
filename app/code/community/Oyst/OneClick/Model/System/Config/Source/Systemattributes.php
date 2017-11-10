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
class Oyst_OneClick_Model_System_Config_Source_Systemattributes
{
    public function toOptionArray()
    {
        $type = Mage::getModel('eav/entity_type');
        $type->loadByCode('catalog_product');

        $attrs = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($type)
            ->addFieldToFilter('is_user_defined', false)
            ->addFieldToFilter('frontend_input', 'select');

        $array = array();
        /** @var $attr Mage_Eav_Model_Entity_Attribute */
        foreach ($attrs as $id => $attr) {
            $array[$id] = array(
                'value' => $attr->getId(),
                'label' => $attr->getFrontendLabel(),
            );
        }
        return $array;
    }
}

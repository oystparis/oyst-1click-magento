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

/**
 * OneClick ShipmentTypesList Model
 */
class Oyst_OneClick_Model_System_Config_Source_ShipmentTypesList extends Mage_Core_Model_Abstract
{
    /**
     * Return the shipment types list.
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var Oyst_OneClick_Model_OneClick_ApiWrapper $oneclickApi */
        $oneclickApi = Mage::getModel('oyst_oneclick/catalog_apiWrapper');
        $shipmentTypes = $oneclickApi->getShipmentTypes();

        $shipmentTypesList = array(
            array('value' => 0, 'label' => Mage::helper('oyst_oneclick')->__('Disabled')),
        );

        if (!isset($shipmentTypes) || !isset($shipmentTypes['types']) || !count($shipmentTypes['types'])) {
            return $shipmentTypesList;
        }

        foreach ($shipmentTypes['types'] as $key => $type) {
            $shipmentTypesList[] = array('value' => $key, 'label' => Mage::helper('oyst_oneclick')->__($type));
        }

        return $shipmentTypesList;
    }
}

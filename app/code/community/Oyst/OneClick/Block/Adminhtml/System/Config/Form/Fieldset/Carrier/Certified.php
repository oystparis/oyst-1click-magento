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
 * Adminhtml System Config Form Fieldset Carrier Certified Block
 */
class Oyst_OneClick_Block_Adminhtml_System_Config_Form_Fieldset_Carrier_Certified extends Oyst_OneClick_Block_Adminhtml_System_Config_Form_Fieldset_Carrier_Mapping
{
    public function getAllCarrierCode()
    {
        /** @var Mage_Shipping_Model_Config $methods */
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers($this->getStore());
        $methodOptions = array();
        $ignoredShipments = Mage::helper('oyst_oneclick/shipments')->getIgnoredShipments();

        foreach ($methods as $ccode => $carrier) {
            if (in_array($ccode, $this->certified) && !in_array($ccode, $ignoredShipments)) {
                if ($_methods = $carrier->getAllowedMethods()) {
                    foreach ($_methods as $mcode => $method) {
                        $code = $ccode . '_' . $mcode;
                        $methodOptions[] = array(
                            'value' => $code,
                            'label' => $method,
                        );
                    }
                }
            }
        }

        return $methodOptions;
    }
}

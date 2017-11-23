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
    public function _getAllCarrierCode()
    {
        /** @var Mage_Shipping_Model_Config $methods */
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers($this->_getStore());
        $options = array();
        $_methodOptions = array();
        foreach ($methods as $_ccode => $_carrier) {
            if (in_array($_ccode, $this->_certified)) {
                if ($_methods = $_carrier->getAllowedMethods()) {
                    foreach ($_methods as $_mcode => $_method) {
                        $_code = $_ccode . '_' . $_mcode;
                        $_methodOptions[] = array(
                            'value' => $_code,
                            'label' => $_method,
                        );
                    }
                }
            }
        }

        return $_methodOptions;
    }
}

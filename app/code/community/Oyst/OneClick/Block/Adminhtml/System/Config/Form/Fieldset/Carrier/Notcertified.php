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
 * Adminhtml System Config Form Fieldset Carrier Notcertified Block
 */
class Oyst_OneClick_Block_Adminhtml_System_Config_Form_Fieldset_Carrier_Notcertified extends Oyst_OneClick_Block_Adminhtml_System_Config_Form_Fieldset_Carrier_Mapping
{
    protected function isCarrierSupported($ccode, $ignoredShipments)
    {
        return !in_array($ccode, $this->certified) && !in_array($ccode, $ignoredShipments);
    }
}

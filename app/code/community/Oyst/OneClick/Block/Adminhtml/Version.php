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
 * Custom renderer for the Oyst section title
 *
 * Adminhtml Version Block
 */
class Oyst_OneClick_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        return (string)$oystHelper->getModuleVersion();
    }
}

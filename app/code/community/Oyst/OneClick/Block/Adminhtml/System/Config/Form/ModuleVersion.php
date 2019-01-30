<?php

class Oyst_OneClick_Block_Adminhtml_System_Config_Form_ModuleVersion extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string)Mage::getConfig()->getNode('modules/Oyst_OneClick/version');
    }
}


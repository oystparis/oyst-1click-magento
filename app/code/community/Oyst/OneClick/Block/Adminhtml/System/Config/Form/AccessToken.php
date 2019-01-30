<?php

class Oyst_OneClick_Block_Adminhtml_System_Config_Form_AccessToken extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return Mage::getStoreConfig('oyst_oneclick/general/access_token');
    }
}
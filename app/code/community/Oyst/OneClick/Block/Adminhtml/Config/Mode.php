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
 * Mode config
 *
 * Adminhtml_Config_Mode Block
 */
class Oyst_OneClick_Block_Adminhtml_Config_Mode extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = parent::_getElementHtml($element);

        $javascript = "
        <script type=\"text/javascript\">
            var element = $('" . $element->getHtmlId() . "');
            Event.observe(element, 'change', function(){
                if (element.selectedOptions[0].value != '" . $element->getValue() . "'){
                    $('oneclick-mode-comment').show();
                } else {
                    $('oneclick-mode-comment').hide();
                }
            });
        </script>";

        return $html . $javascript;
    }
}

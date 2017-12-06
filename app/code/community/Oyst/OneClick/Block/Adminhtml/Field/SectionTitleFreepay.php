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
 * Adminhtml_Field_SectionTitleFreepay Block
 */
class Oyst_OneClick_Block_Adminhtml_Field_SectionTitleFreepay extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div style="background-color: #D1DEDF; border: 1px solid #849BA3; height: 36px; padding-top: 5px;">';
        $html .= '<svg style="font-size: 8rem;" x="0px" y="0px" width="1em" height="0.25em" viewBox="0 0 119 32" style="enable-background:new 0 0 119 32;" xml:space="preserve">
    <g>
        <title>' . $element->getLabel() . '</title>
        <path fill="#212121" d="M34.7,23.3V8h11.1v2.8h-8.2V15h7.7v2.7h-7.7v5.7h-2.9V23.3z"></path>
        <path fill="#212121" d="M49.5,12.5l0.2,1.3c0.8-1.3,2-1.5,3.1-1.5s2.2,0.4,2.8,1l-1.2,2.3c-0.6-0.5-1.1-0.7-1.9-0.7c-1.4,0-2.7,0.7-2.7,2.8v5.7h-2.7V12.5H49.5z"></path>
        <path fill="#212121" d="M58.3,18.9c0.2,1.3,1.3,2.3,3.2,2.3c1,0,2.3-0.4,2.9-1l1.7,1.7c-1.1,1.2-3,1.8-4.7,1.8c-3.7,0-6-2.3-6-5.8c0-3.3,2.2-5.7,5.8-5.7s5.9,2.2,5.5,6.7C66.7,18.9,58.3,18.9,58.3,18.9z M64.2,16.6c-0.2-1.4-1.3-2.1-2.8-2.1s-2.6,0.7-3,2.1H64.2z"></path>
        <path fill="#212121" d="M70.6,18.9c0.2,1.3,1.3,2.3,3.2,2.3c1,0,2.3-0.4,2.9-1l1.7,1.7c-1.1,1.2-3,1.8-4.7,1.8c-3.7,0-6-2.3-6-5.8c0-3.3,2.2-5.7,5.8-5.7c3.6,0,5.9,2.2,5.5,6.7C79,18.9,70.6,18.9,70.6,18.9z M76.5,16.6c-0.2-1.4-1.3-2.1-2.8-2.1s-2.6,0.7-3,2.1H76.5z"></path>
        <path fill="#212121" d="M88,18.8h-4.4v4.5h-2.9V7.9c2.4,0,4.8,0,7.3,0C95.6,7.9,95.6,18.8,88,18.8z M83.7,16.1h4.4c3.7,0,3.7-5.5,0-5.5h-4.4V16.1z"></path>
        <path fill="#212121" d="M103.1,12.5h2.6v10.8h-2.6l-0.1-1.6c-0.6,1.3-2.3,1.9-3.5,1.9c-3.2,0-5.6-2-5.6-5.8c0-3.7,2.5-5.7,5.7-5.7c1.5,0,2.8,0.7,3.5,1.8V12.5z M96.5,17.9c0,2.1,1.4,3.3,3.2,3.3c4.2,0,4.2-6.6,0-6.6C98,14.6,96.5,15.8,96.5,17.9z"></path>
        <path fill="#212121" d="M118.9,12.5l-6.6,15.4h-2.9l2-4.7l-4.3-10.7h3.1l1.7,4.7l1,3.1l1.1-3l2-4.8C116,12.5,118.9,12.5,118.9,12.5z"></path>
    </g>
    <path fill="#00B0FF" d="M26,8.4c-0.7-1.7-1.7-3.2-3-4.4c-1.9-2-4.3-3.3-7.1-3.8c-1.5-0.3-3.1-0.3-4.7,0C8.4,0.7,6,2,4,4C2.8,5.2,1.8,6.7,1.1,8.4c-0.7,1.6-1,3.3-1,5.1s0.3,3.4,1,5.1s1.7,3.2,3,4.4l8.7,8.7c0.4,0.4,1.1,0.4,1.6,0l1.6-1.6c0.4-0.4,0.4-1.1,0-1.6l-7.1-7.1l6.2-6.4l0,0c1.2-1.2,0.8-3.4-1.3-3.8c-0.7-0.2-1.5,0.1-1.9,0.7l0,0L5.8,18c-0.2-0.4-0.4-0.8-0.6-1.2c-0.4-1.1-0.7-2.2-0.7-3.4s0.2-2.3,0.7-3.4c0.4-1.1,1.1-2.1,2-3c1.5-1.5,3.5-2.4,5.7-2.6c0.4,0,0.8,0,1.2,0c2.2,0.2,4.2,1.1,5.7,2.6c0.9,0.9,1.5,1.9,2,3c0.4,1.1,0.7,2.2,0.7,3.4s-0.2,2.3-0.7,3.4c-0.4,1.1-1.1,2.1-2,3l-2.7,2.8c-0.4,0.4-0.4,1.1,0,1.6l1.6,1.6c0.4,0.4,1.1,0.4,1.6,0l2.8-2.8c1.3-1.3,2.3-2.8,3-4.4s1-3.3,1-5.1S26.6,10,26,8.4z">
    </path>
</svg>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<tr style="height: 13px;"><td colspan="4"></td></tr>';
        $html .= '<tr>';
        $html .= '<td colspan="4">';
        $html .= $this->_getElementHtml($element);

        if ($element->getComment()) {
            $html .= '<p class="note" style="margin-left: 20px; margin-bottom: 15px;">';
            $html .= '<span>' . $element->getComment() . '</span>';
            $html .= '</p>';
        }

        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }
}

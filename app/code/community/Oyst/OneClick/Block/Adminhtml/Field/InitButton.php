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
 * Custom renderer for the Oyst init button
 *
 * Adminhtml_Field_InitButton Block
 */
class Oyst_OneClick_Block_Adminhtml_Field_InitButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('oyst/freepay/field/init_button.phtml');

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $fieldConfig = $element->getFieldConfig();
        $this->addData(
            array(
                'button_label' => $oystHelper->__((string)$fieldConfig->descend('button_label')),
                'button_url'   => $this->getUrl($fieldConfig->descend('button_url'), array('_secure' => true)),
                'html_id' => $element->getHtmlId(),
            )
        );

        return $this->_toHtml();
    }
}

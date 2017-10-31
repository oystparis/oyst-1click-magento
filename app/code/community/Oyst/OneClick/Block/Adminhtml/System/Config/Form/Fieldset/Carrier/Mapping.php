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
 * Adminhtml System Config Form Fieldset Carrier Mapping Block
 */
class Oyst_OneClick_Block_Adminhtml_System_Config_Form_Fieldset_Carrier_Mapping
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    //Default code : 'flatrate','freeshipping','fedex','ups','usps','dhlint'
    protected $_certified = array('flatrate', 'freeshipping', 'owebiashipping1', 'owebiashipping2', 'owebiashipping3');

    protected $_dummyElement;

    protected $_fieldRenderer;

    protected $_values;

    /**
     * Specific render for shipment mapping
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $val = $this->_getAllCarrierCode();
        if (isset($val) && !empty($val)) {
            foreach ($val as $v) {
                if (strpos($v['value'], '_about') !== false) {
                    continue;
                }
                $html .= $this->_getHeadingFieldHtml($element, $v);
                $html .= $this->_getMappingFieldHtml($element, $v);
                $html .= $this->_getDelayFieldHtml($element, $v);
                $html .= $this->_getNameFieldHtml($element, $v);
            }
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * Default value for element
     *
     * @return Varien_Object
     */
    protected function _getDummyElement()
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new Varien_Object(array(
                'show_in_default' => 1,
                'show_in_website' => 1,
            ));
        }

        return $this->_dummyElement;
    }

    /**
     * Generic field render for form field
     *
     * @return object
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }

        return $this->_fieldRenderer;
    }

    /**
     * Field heading
     *
     * @param $fieldset
     * @param $group
     *
     * @return string
     */
    protected function _getHeadingFieldHtml($fieldset, $group)
    {
        $field = $fieldset->addField('content_heading_' . $group['value'], 'text',
            array(
                'name' => 'content_heading',
                'label' => Mage::helper('oyst_oneclick')->__($group['label']) .
                    ' <span style="font-size:10px">(' . $group['value'] . ')</span>',
                'title' => Mage::helper('oyst_oneclick')->__($group['label']) . ' (' . $group['value'] . ')',
                'disabled' => false,
            ))
            ->setRenderer(Mage::getBlockSingleton('adminhtml/system_config_form_field_heading'));

        return $field->toHtml();
    }

    /**
     * Field mapping
     *
     * @param $fieldset
     * @param $group
     *
     * @return string
     */
    protected function _getMappingFieldHtml($fieldset, $group)
    {
        $configData = $this->getConfigData();
        $path = sprintf('oyst_oneclick/carrier_mapping/%s', $group['value']);

        $data = '0';
        $inherit = false;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        }

        $e = $this->_getDummyElement();

        $field = $fieldset->addField('oyst_oneclick_carrier_mapping_' . $group['value'], 'select',
            array(
                'name' => 'groups[carrier_mapping][fields][' . $group['value'] . '][value]',
                'label' => Mage::helper('oyst_oneclick')->__('Shipment type'),
                'default' => 0,
                'inherit' => $inherit,
                'value' => $data,
                'values' => Mage::getSingleton('oyst_oneclick/system_config_source_shipmentTypesList')
                    ->toOptionArray(),
                'comment' => Mage::helper('oyst_oneclick')->__('To remove switch to disabled'),
                'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
            ))
            ->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * Field delay
     *
     * @param $fieldset
     * @param $group
     *
     * @return string
     */
    protected function _getDelayFieldHtml($fieldset, $group)
    {
        $configData = $this->getConfigData();
        $path = sprintf('oyst_oneclick/carrier_delay/%s', $group['value']);

        $data = 48;
        $inherit = false;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        }

        $e = $this->_getDummyElement();

        $field = $fieldset
            ->addField('oyst_oneclick_carrier_delay_' . $group['value'], 'text',
                array(
                    'name' => 'groups[carrier_delay][fields][' . $group['value'] . '][value]',
                    'label' => Mage::helper('oyst_oneclick')->__('Shipment delay'),
                    'default' => 1,
                    'inherit' => $inherit,
                    'value' => $data,
                    'class' => 'validate-number',
                    'comment' => Mage::helper('oyst_oneclick')->__('Value in hours'),
                    'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                    'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
                )
            )
            ->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * Field name
     *
     * @param $fieldset
     * @param $group
     *
     * @return string
     */
    protected function _getNameFieldHtml($fieldset, $group)
    {
        $configData = $this->getConfigData();
        $path = sprintf('oyst_oneclick/carrier_name/%s', $group['value']);

        $data = $group['label'];
        $inherit = false;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        }

        $e = $this->_getDummyElement();

        $field = $fieldset
            ->addField('oyst_oneclick_carrier_name_' . $group['value'], 'text',
                array(
                    'name' => 'groups[carrier_name][fields][' . $group['value'] . '][value]',
                    'label' => Mage::helper('oyst_oneclick')->__('Shipment name'),
                    'default' => '',
                    'inherit' => $inherit,
                    'value' => $data,
                    'comment' => Mage::helper('oyst_oneclick')->__('Title displayed for customer'),
                    'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                    'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
                )
            )
            ->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * Get all carrier code
     *
     * @return array
     */
    protected function _getAllCarrierCode()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        $options = array();
        $_methodOptions = array();
        foreach ($methods as $_ccode => $_carrier) {
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

        return $_methodOptions;
    }
}

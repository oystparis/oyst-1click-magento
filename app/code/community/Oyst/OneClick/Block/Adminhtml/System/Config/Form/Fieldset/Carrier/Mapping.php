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
    protected $certified = array('flatrate', 'freeshipping', 'owebiashipping1', 'owebiashipping2', 'owebiashipping3');

    protected $dummyElement;

    protected $fieldRenderer;

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
        $val = $this->getAllCarrierCode();
        if (isset($val) && !empty($val)) {
            foreach ($val as $v) {
                if (false !== strpos($v['value'], '_about')) {
                    continue;
                }

                $html .= $this->getHeadingFieldHtml($element, $v);
                $html .= $this->getMappingFieldHtml($element, $v);
                $html .= $this->getDelayFieldHtml($element, $v);
                $html .= $this->getNameFieldHtml($element, $v);
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
    protected function getDummyElement()
    {
        if (empty($this->dummyElement)) {
            $this->dummyElement = new Varien_Object(
                array(
                    'show_in_default' => 1,
                    'show_in_website' => 1,
                )
            );
        }

        return $this->dummyElement;
    }

    /**
     * Generic field render for form field
     *
     * @return object
     */
    protected function getFieldRenderer()
    {
        if (empty($this->fieldRenderer)) {
            $this->fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }

        return $this->fieldRenderer;
    }

    /**
     * Field heading
     *
     * @param $fieldset Varien_Data_Form_Element_Fieldset
     * @param $group array
     *
     * @return string
     */
    protected function getHeadingFieldHtml($fieldset, $group)
    {
        $field = $fieldset->addField(
            'content_heading_' . $group['value'],
            'text',
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
     * @param $fieldset Varien_Data_Form_Element_Fieldset
     * @param $group array
     *
     * @return string
     */
    protected function getMappingFieldHtml($fieldset, $group)
    {
        $configData = $this->getConfigData();
        $path = sprintf('oyst_oneclick/carrier_mapping/%s', $group['value']);

        $data = Mage::getStoreConfig($path);
        $inherit = true;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        }

        $dummyElement = $this->getDummyElement();

        $field = $fieldset->addField('oyst_oneclick_carrier_mapping_' . $group['value'], 'select',
            array(
                'name' => 'groups[carrier_mapping][fields][' . $group['value'] . '][value]',
                'label' => Mage::helper('oyst_oneclick')->__('Shipment type'),
                'default' => 0,
                'inherit' => $inherit,
                'value' => $data,
                'values' => Mage::getSingleton('oyst_oneclick/system_config_source_shipmentTypesList')
                    ->toOptionArray(),
                'class' => 'validate-oyst-shipment-type',
                'comment' => Mage::helper('oyst_oneclick')->__('To remove switch to disabled'),
                'can_use_default_value' => $this->getForm()->canUseDefaultValue($dummyElement),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue($dummyElement),
            ))
            ->setRenderer($this->getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * Field delay
     *
     * @param $fieldset Varien_Data_Form_Element_Fieldset
     * @param $group array
     *
     * @return string
     */
    protected function getDelayFieldHtml($fieldset, $group)
    {
        $configData = $this->getConfigData();
        $path = sprintf('oyst_oneclick/carrier_delay/%s', $group['value']);

        $data = Mage::getStoreConfig($path);
        $inherit = true;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        }

        $e = $this->getDummyElement();

        $field = $fieldset
            ->addField('oyst_oneclick_carrier_delay_' . $group['value'], 'text',
                array(
                    'name' => 'groups[carrier_delay][fields][' . $group['value'] . '][value]',
                    'label' => Mage::helper('oyst_oneclick')->__('Shipment delay'),
                    'default' => 1,
                    'inherit' => $inherit,
                    'value' => $data,
                    'class' => 'validate-number validate-oyst-shipment-delay',
                    'comment' => Mage::helper('oyst_oneclick')->__('Value in hours'),
                    'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                    'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
                )
            )
            ->setRenderer($this->getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * Field name
     *
     * @param $fieldset Varien_Data_Form_Element_Fieldset
     * @param $group array
     *
     * @return string
     */
    protected function getNameFieldHtml($fieldset, $group)
    {
        $configData = $this->getConfigData();
        $path = sprintf('oyst_oneclick/carrier_name/%s', $group['value']);

        $data = Mage::getStoreConfig($path);
        $inherit = true;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        }

        $e = $this->getDummyElement();

        $field = $fieldset
            ->addField('oyst_oneclick_carrier_name_' . $group['value'], 'text',
                array(
                    'name' => 'groups[carrier_name][fields][' . $group['value'] . '][value]',
                    'label' => Mage::helper('oyst_oneclick')->__('Shipment name'),
                    'default' => '',
                    'inherit' => $inherit,
                    'value' => $data,
                    'class' => 'validate-oyst-shipment-name',
                    'comment' => Mage::helper('oyst_oneclick')->__('Title displayed for customer'),
                    'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                    'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
                )
            )
            ->setRenderer($this->getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * Get all carrier code
     *
     * @return array
     */
    protected function getAllCarrierCode()
    {
        /** @var Mage_Shipping_Model_Config $methods */
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers($this->getStore());
        $methodOptions = array();
        foreach ($methods as $ccode => $carrier) {
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

        return $methodOptions;
    }

    /**
     * Get store
     *
     * @return Mage_Core_Model_Store|null
     */
    protected function getStore()
    {
        $store = null;
        $websiteCode = Mage::app()->getRequest()->getParam('website', false);
        if ($websiteCode) {
            /** @var Mage_Core_Model_Website $website */
            $website = Mage::getModel('core/website')->load($websiteCode);
            $store = $website->getDefaultStore();
        }

        return $store;
    }
}

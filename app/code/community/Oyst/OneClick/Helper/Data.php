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
 * Data Helper
 */
class Oyst_OneClick_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MODULE_NAME = 'Oyst_OneClick';

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/oneclick/$code");
    }

    /**
     * Get the module version
     *
     * @return string Extension version
     */
    public function getModuleVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->{self::MODULE_NAME}->version;
    }

    /**
     * Global function for log if enabled
     *
     * @param string $message
     *
     * @return null
     */
    public function log($message)
    {
        if ($this->_getConfig('log_enable')) {
            Mage::log($message, null, $this->getModuleName() . '.log', true);
        }
    }

    /**
     * Return the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Set initialization status config flag and refresh config cache.
     *
     * @param bool $isInitialized Flag for initialization
     */
    public function setIsInitialized($isInitialized = true)
    {
        $isInitialized = (bool)$isInitialized ? '1' : '0';
        Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData('oyst/oneclick/is_initialized', $isInitialized);
        Mage::app()->getCacheInstance()->cleanType('config');
        Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => 'config'));
    }

    /**
     * Test Return the module name
     *
     * @return string
     */
    public function isApiKeyValid()
    {
        try {
            /** @var Oyst_OneClick_Model_Api $api */
            $api = Mage::getModel('oyst_oneclick/api');
            $api->isApiKeyValid();
        } catch (Exception $e) {
            /** @var Oyst_OneClick_Helper_Data $oystHelper */
            $oystHelper = Mage::helper('oyst_oneclick');

            $url = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/oyst_oneclick');
            Mage::getSingleton('core/session')->addWarning(
                $oystHelper->__('Click <a href="%s">here</a> to setup your API key.', $url)
            );

            return false;
        }
        return true;
    }

    /**
     * Get OneClick javascript CDN URL
     *
     * @return string
     */
    public function getOneClickJs()
    {
        if ($this->_getConfig('enable')) {
            $mode = $this->_getConfig('mode');
            $oneclickjs = $this->_getConfig('oneclickjs_' . $mode . '_url') . '1click/script/script.min.js';

            if (Oyst_OneClick_Model_Source_Mode::CUSTOM === $mode) {
                $oneclickjs = $this->_getConfig('oneclickjs_url');
            }

            return '<script src="' . $oneclickjs . '"></script>';
        }
    }

    /**
     * Test and set default value
     *
     * @param string $var
     * @param string $value
     */
    function defaultValue(&$var, $value) {
        $var =  !isset($var) ? $value : $var;
    }
}

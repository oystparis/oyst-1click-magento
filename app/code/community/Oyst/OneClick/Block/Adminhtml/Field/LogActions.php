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
 * Adminhtml_Field_LogActions Block
 */
class Oyst_OneClick_Block_Adminhtml_Field_LogActions extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @var string
     */
    protected $_template = 'oyst/oneclick/logactions.phtml';

    /**
     * Allowed log files.
     *
     * @var array
     */
    protected $_logFiles = array();

    /**
     * Oyst_OneClick_Block_Adminhtml_Field_LogActions constructor.
     *
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        $this->_logFiles = array('system.log', 'exception.log', Oyst_OneClick_Helper_Data::MODULE_NAME . '.log', 'error_oyst.log');
        parent::__construct($args);
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = sprintf(
            '<tr id="%s"><td class="label">%s</td><td class="value">%s</td></tr>',
            $element->getHtmlId(),
            $element->getLabelHtml(),
            $this->toHtml()
        );

        return $html;
    }

    /**
     * Get allowed log files.
     *
     * @return array
     */
    public function getLogFiles()
    {
        $varDirectory = array(Zend_Cloud_StorageService_Adapter_FileSystem::LOCAL_DIRECTORY => Mage::getBaseDir('var'));
        $fileSystem = new Zend_Cloud_StorageService_Adapter_FileSystem($varDirectory);
        $logFiles = $fileSystem->listItems('log');
        $logFiles = array_intersect($this->_logFiles, $logFiles);

        return $logFiles;
    }

    public function getButtonUrl($action)
    {
        return $this->getUrl('adminhtml/oneclick_actions/' . $action, array('_secure' => true));
    }
}

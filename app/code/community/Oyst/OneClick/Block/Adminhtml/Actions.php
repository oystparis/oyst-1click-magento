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
 * Adminhtml Actions Block
 */
class Oyst_OneClick_Block_Adminhtml_Actions extends Mage_Adminhtml_Block_Template
{
    /**
     * Skip setup by setting the config flag accordingly
     */
    public function skipAction()
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');
        $helper->setIsInitialized('oneclick');
        $this->_redirectReferer();
    }
}

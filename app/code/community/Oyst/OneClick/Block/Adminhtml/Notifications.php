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
 * OneClick install notification
 *
 * Adminhtml_Notifications Block
 */
class Oyst_OneClick_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    /**
     * Disable the block caching for this block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addData(array('cache_lifetime' => null));
    }

    /**
     * Returns a value that indicates if some of the 1-Click settings have already been initialized.
     *
     * @return bool Flag if 1-Click is already initialized
     */
    public function isInitialized()
    {
        return Mage::getStoreConfigFlag('oyst/oneclick/is_initialized');
    }

    /**
     * Get 1-Click management url
     *
     * @return string URL for 1-Click form in payment method
     */
    public function getManageUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/oyst_oneclick');
    }

    /**
     * Get 1-Click installation skip action
     *
     * @return string URL for skip action
     */
    public function getSkipUrl()
    {
        return $this->getUrl('adminhtml/oneclick_actions/skip');
    }

    /**
     * ACL validation before html generation
     *
     * @return string Notification content
     */
    protected function _toHtml()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('system/oyst_oneclick')) {
            return parent::_toHtml();
        }

        return '';
    }
}

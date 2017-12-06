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
     * Returns a value that indicates if some of the payment method settings have already been initialized.
     *
     * @param string $identifier Payment method identifier
     * @return bool Flag if payment method is already initialized
     */
    public function isInitialized($identifier)
    {
        return Mage::getStoreConfigFlag('oyst/' . $identifier . '/is_initialized');
    }

    /**
     * Get payment method management url
     *
     * @param string $identifier Payment method identifier
     * @return string URL for payment method form
     */
    public function getManageUrl($identifier)
    {
        return $this->getUrl('adminhtml/system_config/edit/section/' . $identifier);
    }

    /**
     * Get payment method installation skip action
     *
     * @param string $identifier Payment method identifier
     * @return string URL for skip action
     */
    public function getSkipUrl($identifier)
    {
        return $this->getUrl('adminhtml/oneclick_actions/skip', ['identifier' => $identifier]);
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

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
 * Adminhtml Notifications Controller
 */
class Oyst_OneClick_Adminhtml_Oyst_NotificationsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test if user can access to this sections
     *
     * @return bool
     *
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/notifications');
    }

    /**
     * Initialize grid container.
     */
    public function indexAction()
    {
        $this->_title($this->__('Oyst Notifications'));
        $this->loadLayout();
        $this->_setActiveMenu('sales/sales');
        $this->_addContent($this->getLayout()->createBlock('oyst_oneclick/adminhtml_table_notifications'));
        $this->renderLayout();
    }

    /**
     * Initialize grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('oyst_oneclick/adminhtml_table_notifications_grid')->toHtml()
        );
    }
}

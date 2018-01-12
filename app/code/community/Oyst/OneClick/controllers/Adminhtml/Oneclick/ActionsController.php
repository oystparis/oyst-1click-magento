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
 * Adminhtml Actions Controller
 */
class Oyst_OneClick_Adminhtml_OneClick_ActionsController extends Mage_Adminhtml_Controller_Action
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
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/actions');
    }

    /**
     * Skip setup by setting the config flag accordingly
     */
    public function skipAction()
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');
        $identifier = $this->getRequest()->getParam('identifier');
        $helper->setIsInitialized($identifier);
        $this->_redirectReferer();
    }

    /**
     * Magento method for init layout, menu and breadcrumbs
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_ActionsController
     */
    protected function _initAction()
    {
        $this->_activeMenu();

        return $this;
    }

    /**
     * Active menu
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_ActionsController
     */
    protected function _activeMenu()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $this->loadLayout()
            ->_setActiveMenu('oyst_oneclick/oneclick_actions')
            ->_title($oystHelper->__('Actions'))
            ->_addBreadcrumb($oystHelper->__('Actions'), $oystHelper->__('Actions'));

        return $this;
    }

    /**
     * Print action page
     *
     * @retun null
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }
}

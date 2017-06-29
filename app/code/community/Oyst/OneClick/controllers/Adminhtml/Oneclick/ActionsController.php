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
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/actions');
    }

    /**
     * Magento method for init layout, menu and breadcrumbs
     *
     * @return Oyst_OneClick_Adminhtml_Oneclick_ActionsController
     */
    protected function _initAction()
    {
        $this->_activeMenu();

        return $this;
    }

    /**
     * Active menu
     *
     * @return Oyst_OneClick_Adminhtml_Oneclick_ActionsController
     */
    protected function _activeMenu()
    {
        $title = Mage::helper('oyst_oneclick')->__('1-Click Catalog Synchronization');
        $this->loadLayout()
            ->_setActiveMenu('oyst_oneclick/oyst_actions')
            ->_title($title)
            ->_addBreadcrumb(
                $title,
                $title
            );

        return $this;
    }

    /**
     * Print action page
     *
     * @return null
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * Skip setup by setting the config flag accordingly
     */
    public function skipAction()
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');
        $helper->setIsInitialized();
        $this->_redirectReferer();
    }

    /**
     * Return Json data of the last/current import catalog batch
     *
     * @return mixed|string|void
     */
    public function jsonAction()
    {
        /** @var Oyst_OneClick_Model_Notification $notification */
        $notification = Mage::getModel('oyst_oneclick/notification');
        $lastNotification = $notification->getLastFinishedNotification('import');

        $oystData = json_decode($lastNotification->getOystData(), true);

        $firstNotification = $notification->getFirstNotificationByImportId('import', false, $oystData['import_id']);

        $totalCount = (int)$firstNotification->getImportRemaining() + count($firstNotification->getProductsId());

        $jsonData = json_encode(array(
            'remaining' => (int)$lastNotification->getImportRemaining(),
            'totalCount' => $totalCount,
            'import_id' => $oystData['import_id'],
        ));

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}

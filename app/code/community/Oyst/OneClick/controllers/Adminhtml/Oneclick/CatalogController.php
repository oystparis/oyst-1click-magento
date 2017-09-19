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
 * Adminhtml OneClick Catalog Controller
 */
class Oyst_OneClick_Adminhtml_OneClick_CatalogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test if user can access to this sections
     *
     * @return bool
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/catalog');
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
        $this->loadLayout()
            ->_setActiveMenu('oyst/oyst_catalog')
            ->_title(Mage::helper('oyst_oneclick')->__('Catalog'))
            ->_addBreadcrumb(
                Mage::helper('oyst_oneclick')->__('Catalog'),
                Mage::helper('oyst_oneclick')->__('Catalog')
            );

        return $this;
    }

    /**
     * Return Json data of the shipment export
     *
     * @return mixed|string|void
     */
    public function postShipmentsAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');

        if (!$isAjax = Mage::app()->getRequest()->isAjax()) {
            $this->getResponse()->setHttpResponseCode(401);
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode(array('error' => array('message' => 'not ajax request')))
            );

            return $this;
        }

        /** @var Oyst_OneClick_Model_Catalog_ApiWrapper $catalogApi */
        $catalogApi = Mage::getModel('oyst_oneclick/catalog_apiWrapper');

        $response = $catalogApi->postShipments();

        $this->getResponse()->setHttpResponseCode(200);

        /** @var OystCatalogApi $response */
        if (200 !== $response->getLastHttpCode()) {
            $this->getResponse()->setHttpResponseCode($response->getLastHttpCode());
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode(
                    array('error' => array('code' => $response->getLastHttpCode(), 'message' => $response->getLastError()))
                )
            );
        }

        Mage::helper('oyst_oneclick')->log($response);

        return $this;
    }
}

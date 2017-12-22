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
     *
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/catalog');
    }

    /**
     * Magento method for init layout, menu and breadcrumbs
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_CatalogController
     */
    protected function _initAction()
    {
        $this->_activeMenu();

        return $this;
    }

    /**
     * Active menu
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_CatalogController
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
     * Synchronize product from Magento to Oyst
     *
     * @return Oyst_OneClick_Adminhtml_OneClick_CatalogController
     */
    public function syncAction()
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        //get list of product
        $product = Mage::app()->getRequest()->getParam('product');
        $params = array('product_id_include_filter' => $product);
        $oystHelper->log('Start of sending product id : ' . var_export($product, true));

        //sync product to Oyst
        $result = Mage::helper('oyst_oneclick/catalog_data')->sync($params);
        $oystHelper->log('End of sending product id : ' . var_export($product, true));

        //if api response is success
        if ($result && array_key_exists('success', $result) && $result['success'] == true) {
            $this->_getSession()->addSuccess($oystHelper->__('The sync was successfully done'));
        } else {
            $this->_getSession()->addError($oystHelper->__('An error was occured'));
        }

        $this->getResponse()->setRedirect($this->getRequest()->getServer('HTTP_REFERER'));

        return $this;
    }
}

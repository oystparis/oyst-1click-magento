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
    const CATALOG_SYNC_MODE_NOTIFICATION = 'notification';
    const CATALOG_SYNC_MODE_DIRECT = 'direct';


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
     * Synchronize product from Magento to Oyst
     *
     * @return Oyst_OneClick_Adminhtml_Oyst_CatalogController
     */
    public function syncAction()
    {
        // Mode to sync with notification
        if (self::CATALOG_SYNC_MODE_NOTIFICATION === Mage::getStoreConfig('oyst/oneclick/catalog_sync_mode')) {
            try {
                /** @var Oyst_OneClick_Model_Catalog_ApiWrapper $catalogApi */
                $catalogApi = Mage::getModel('oyst_oneclick/catalog_apiWrapper');

                $response = $catalogApi->notifyImport();
                Mage::helper('oyst_oneclick')->log($response);

                if (isset($response['import_id'])) {
                    $this->_getSession()->addNotice(
                        Mage::helper('oyst_oneclick')->__(
                            'Syncing request in progress (import id: %s). <a href="#" onclick="goToThis(\'%s\');">Refresh</a>.',
                            $response['import_id'],
                            Mage::helper('adminhtml')->getUrl('adminhtml/oneclick_actions/index'))
                    );
                } else {
                    $this->_getSession()->addError(Mage::helper('oyst_oneclick')->__('An error has occurred.'));
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        // Mode to sync product without notification
        if (self::CATALOG_SYNC_MODE_DIRECT === Mage::getStoreConfig('oyst/oneclick/catalog_sync_mode')) {
            try {
                // Get list of product
                $product = Mage::app()->getRequest()->getParam('product');
                Mage::helper('oyst_oneclick')->log('Product passed as param:' . $product);

                $params = array('product_id_include_filter' => $product);
                Mage::helper('oyst_oneclick')->log('Start of sending product id: ' . var_export($product, true));

                // Sync product to Oyst
                /** @var Oyst_OneClick_Helper_Catalog_Data $catalogHelper */
                $catalogHelper = Mage::helper('oyst_oneclick/catalog_data');
                list($result, $importedProductIds) = $catalogHelper->sync($params);

                $result = $catalogHelper->sync($params);
                Mage::helper('oyst_oneclick')->log('End of sending product id: ' . var_export($product, true));

                // For direct push product
                if (isset($result['imported']) && $result['imported'] >= 1) {
                    $this->_getSession()->addSuccess(
                        Mage::helper('oyst_oneclick')->__('1-Click synchronization was successfully done.')
                    );
                } else {
                    $this->_getSession()->addError(Mage::helper('oyst_oneclick')->__('An error occurred while synchronizing the catalog with 1-Click.'));
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->getResponse()->setRedirect($this->getRequest()->getServer('HTTP_REFERER'));

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

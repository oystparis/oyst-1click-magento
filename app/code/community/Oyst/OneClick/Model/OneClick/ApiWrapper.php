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

use Oyst\Api\OystApiClientFactory;
use Oyst\Api\OystOneClickApi;
use Oyst\Classes\OneClickCustomization;
use Oyst\Classes\OneClickNotifications;
use Oyst\Classes\OneClickOrderParams;

/**
 * OneClick ApiWrapper Model
 */
class Oyst_OneClick_Model_OneClick_ApiWrapper extends Oyst_OneClick_Model_Api
{
    /** @var Oyst_OneClick_Model_Api $oystClient */
    protected $oystClient;

    /** @var OystOneClickApi $oneClickApi */
    protected $oneClickApi;

    protected $type = OystApiClientFactory::ENTITY_ONECLICK;

    /* @var int $quoteId */
    private $quoteId;

    public function __construct()
    {
        $this->oystClient = Mage::getModel('oyst_oneclick/api');
        $this->oneClickApi = $this->oystClient->getClient($this->type);
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function getConfig($code)
    {
        return Mage::getStoreConfig("oyst/oneclick/$code");
    }

    /**
     * API send
     *
     * @param $dataFormated
     *
     * @return mixed
     *
     * @throws Mage_Core_Exception
     */
    public function authorizeOrder($dataFormated)
    {
        Mage::helper('oyst_oneclick')->log('$dataFormated');
        Mage::helper('oyst_oneclick')->log($dataFormated);

        /** @var Oyst_OneClick_Model_Catalog $oystCatalog */
        $oystCatalog = Mage::getModel('oyst_oneclick/catalog');
        $oystProducts = $oystCatalog->getOystProducts($dataFormated);

        if (isset($oystProducts['has_error'])) {
            return $oystProducts;
        }

        $notifications = $this->getOneClickNotifications();
        $dataFormated['preload'] = filter_var($dataFormated['preload'], FILTER_VALIDATE_BOOLEAN);

        // Book initial quantity
        if (!$dataFormated['preload'] && $this->getConfig('should_ask_stock')) {
            $notifications->addEvent('order.stock.released');
        }

        $this->quoteId = Mage::getSingleton('checkout/session')->getQuote()->getId();

        $orderParams = $this->getOneClickOrderParams($dataFormated);

        $context = $this->getContext();

        $customization = $this->getOneClickCustomization($dataFormated);

        try {
            $response = $this->oneClickApi->authorizeOrderV2(
                $oystProducts,
                $notifications,
                null,
                $orderParams,
                $context,
                $customization
            );
            $this->oystClient->validateResult($this->oneClickApi);

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }

    /**
     * Generate unique identifier.
     *
     * Identifier is built as [custom string][current datetime][random part]
     *
     * @return string
     */
    public function generateId($string = null)
    {
        $randomPart = rand(10, 99);

        list($usec, $sec) = explode(' ', microtime());
        unset($sec);

        $microtime = explode('.', $usec);
        $datetime = new DateTime();
        $datetime = $datetime->format('YmdHis');

        return $string . $datetime . $microtime[1] . $randomPart;
    }

    /**
     * Get OneClickNotifications configuration
     *
     * @return OneClickNotifications
     */
    private function getOneClickNotifications()
    {
        $notifications = new OneClickNotifications();
        $notifications->setShouldAskShipments(true);
        $notifications->setShouldAskStock($this->getConfig('should_ask_stock'));
        $notifications->setUrl($this->getConfig('notification_url'));

        if ($notifications->isShouldAskShipments()) {
            $notifications->addEvent('order.cart.estimate');
        }

        return $notifications;
    }

    /**
     * Get OneClickOrderParams configuration.
     *
     * @param array $dataFormated
     *
     * @return OneClickOrderParams
     */
    private function getOneClickOrderParams($dataFormated)
    {
        $orderParams = new OneClickOrderParams();
        $orderParams->setManageQuantity($this->getConfig('allow_quantity_change'));

        if ($delay = Mage::getStoreConfig('oyst/oneclick/order_delay')) {
            $orderParams->setDelay($delay);
        }

        if ($reinitialize = Mage::getStoreConfig('oyst/oneclick/reinitialize_buffer')) {
            $orderParams->setShouldReinitBuffer($reinitialize);
        }

        if (isset($dataFormated['isCheckoutCart'])) {
            $orderParams->setIsCheckoutCart(true);
        }

        return $orderParams;
    }

    /**
     * Get context configuration
     *
     * @return array
     */
    private function getContext()
    {
        $context = array(
            'id' => $this->generateId(),
            'remote_addr' => Mage::helper('core/http')->getRemoteAddr(),
            'store_id' => Mage::app()->getStore()->getStoreId(),
            'quote_id' => $this->quoteId,
        );

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $context['user_id'] = (string)Mage::getSingleton('customer/session')->getCustomer()->getId();
        }

        return $context;
    }

    /**
     * Get one click customization.
     *
     * @param array $dataFormated
     *
     * @return OneClickCustomization
     */
    private function getOneClickCustomization($dataFormated)
    {
        $customization = new OneClickCustomization();

        if (isset($dataFormated['isCheckoutCart'])) {
            $customization->setCta(
                Mage::getStoreConfig('oyst/oneclick/checkout_cart_cta_label'),
                Mage::helper('oyst_oneclick')->getRedirectUrl($this->quoteId)
            );
        }

        return $customization;
    }
}

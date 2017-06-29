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
 * Notifications Controller
 */
class Oyst_OneClick_NotificationsController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get notification for catalog, order and payment callback
     *
     * @return null
     */
    public function indexAction()
    {
        $event = $this->getRequest()->getPost('event');
        $data = $this->getRequest()->getPost('data');
        // @codingStandardsIgnoreLine
        $post = (array)Zend_Json::decode(str_replace("\n", '', file_get_contents('php://input')));
        //set the type and data from notification url
        if (empty($event) && empty($data) && !empty($post)) {
            if (array_key_exists('event', $post)) {
                $event = $post['event'];
            }

            if (array_key_exists('data', $post)) {
                $data = (array)$post['data'];
            }

            if (array_key_exists('notification', $post)) {
                $event = 'payment';
                $data = (array)$post['notification'];
                $data['order_increment_id'] = Mage::app()->getRequest()->getParam('order_increment_id');
            }
        }

        if (empty($post) || empty($data) || empty($event)) {
            return $this->badRequest();
        }

        if ($event == 'catalog.import') {
            $helperName = 'oyst_oneclick/catalog_data';
        } elseif ($event == 'notification.newOrder') {
            $helperName = 'oyst_oneclick/order_data';
        }

        $helper = Mage::helper($helperName);
        if (!$helper) {
            return $this->badRequest();
        }

        /**
         * @var Oyst_OneClick_Helper_Catalog_Data $result
         * @var Oyst_OneClick_Helper_Order_Data $result
         */
        $result = $helper->syncFromNotification($event, $data);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    private function badRequest()
    {
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('HTTP/1.1', '400 Bad Request')
            ->setBody('400 Bad Request');
    }
}

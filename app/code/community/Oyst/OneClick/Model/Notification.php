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
 * Notification Model
 */
class Oyst_OneClick_Model_Notification extends Mage_Core_Model_Abstract
{
    const NOTIFICATION_STATUS_START = 'start';

    const NOTIFICATION_STATUS_FINISHED = 'finished';

    const NOTIFICATION_STATUS_ZOMBIE = 'zombie';

    const XML_PATH_NOTIFICATIONS_UPDATE_MAX_DELAY = 'oyst/oneclick/notifications_update_max_delay';

    /**
     * Object construct
     *
     * @return null
     */
    protected function _construct()
    {
        $this->_init('oyst_oneclick/notification');
    }

    /**
     * Get last notification filter by type AND/OR id
     *
     * @param string $type
     * @param Int $dataId
     *
     * @return Oyst_OneClick_Model_Notification
     */
    public function getLastNotification($type = false, $dataId = false)
    {
        $collection = $this->getCollection()
            ->addDataIdToFilter($type, $dataId)
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        return $collection->getFirstItem();
    }

    /**
     * Get last finished notification filter by type AND/OR id
     *
     * @param string $type
     * @param Int $dataId
     *
     * @return Oyst_OneClick_Model_Notification
     */
    public function getLastFinishedNotification($type = false, $dataId = false)
    {
        $collection = $this->getCollection()
            ->addDataIdToFilter($type, $dataId)
            ->addFieldToFilter('status', array('like' => self::NOTIFICATION_STATUS_FINISHED))
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        return $collection->getFirstItem();
    }

    /**
     * Check if the order notification has been already processed
     *
     * @param string $dataId
     *
     * @return int|null Magento order id or null
     */
    public function isOrderProcessed($oystOrderId)
    {
        $collection = $this->getCollection()
            ->addDataIdToFilter('order', $oystOrderId)
            ->addFieldToFilter('status', array('like' => self::NOTIFICATION_STATUS_FINISHED))
            ->addFieldToFilter('order_id', array('notnull' => true))
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        return $collection->getFirstItem()->getOrderId();
    }

    /**
     * Update notifications status.
     *
     * @return Oyst_OneClick_Model_Observer
     */
    public function updateNotificationsStatus()
    {
        $notificationUpdateMaxDelay = Mage::getStoreConfig(self::XML_PATH_NOTIFICATIONS_UPDATE_MAX_DELAY);

        /** @var Oyst_OneClick_Model_Notification $notifications */
        $notifications = $this->getCollection()
            ->addFieldToFilter('status', array('like' => self::NOTIFICATION_STATUS_START))
            ->addFieldToFilter(
                'created_at',
                array(
                    'to' => strtotime($notificationUpdateMaxDelay, time()),
                    'datetime' => true,
                )
            )
            ->load();

        foreach ($notifications as $notification) {
            try {
                $notification->setStatus(self::NOTIFICATION_STATUS_ZOMBIE);
                // @codingStandardsIgnoreLine
                $notification->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }
}

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

        // @codingStandardsIgnoreLine
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
            ->addFieldToFilter('status', array('like' => 'finished'))
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        // @codingStandardsIgnoreLine
        return $collection->getFirstItem();
    }

    /**
     * Get first notification by importId filter by type AND/OR id
     *
     * @param bool $type
     * @param bool $dataId
     * @param bool $importId
     *
     * @return Oyst_OneClick_Model_Notification
     */
    public function getFirstNotificationByImportId($type = false, $dataId = false, $importId = false)
    {
        $collection = $this->getCollection()
            ->addDataIdToFilter($type, $dataId)
            ->addFieldToFilter('status', array('like' => 'finished'))
            ->addFieldToFilter('oyst_data', array('like' => "%$importId%"))
            ->addOrder('notification_id', 'ASC')
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        // @codingStandardsIgnoreLine
        return $collection->getFirstItem();
    }
}

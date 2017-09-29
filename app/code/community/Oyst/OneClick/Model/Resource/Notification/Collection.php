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
 * Resource_Notification_Collection Model
 */
class Oyst_OneClick_Model_Resource_Notification_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
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
     * Add type filter to notifications list in db
     *
     * @param string $type
     * @param Int $dataId
     *
     * @return Oyst_OneClick_Model_Resource_Notification_Collection
     */
    public function addDataIdToFilter($type, $dataId)
    {
        if ($dataId) {
            if ($type == 'catalog') {
                $this->addFieldToFilter(
                    'oyst_data',
                    array(
                        'like' => '%import_id":"' . $dataId . '"%'
                    )
                );
            }
            if ($type == 'order') {
                $this->addFieldToFilter(
                    'oyst_data',
                    array(
                        'like' => '%order_id":"' . $dataId . '"%'
                    )
                );
            }
        }

        $this->setOrder('notification_id');

        return $this;
    }
}

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
 * Custom grid for the Oyst notifications table
 *
 * Adminhtml_Table_Notifications_Grid Block
 */
class Oyst_OneClick_Block_Adminhtml_Table_Notifications_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Oyst_OneClick_Block_Adminhtml_Table_Notifications_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('oyst_oneclick_grid');
        $this->setDefaultSort('notification_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare notifications collection.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection Oyst_OneClick_Model_Resource_Notification_Collection */
        $collection = Mage::getResourceModel('oyst_oneclick/notification_collection')
            ->setOrder('notification_id', 'desc');

        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Prepare grid columns.
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $helper = Mage::helper('oyst_oneclick');

        $this->addColumn('notification_id', array(
            'header' => $helper->__('Notification #'),
            'index' => 'notification_id',
        ));

        $this->addColumn('event', array(
            'header' => $helper->__('Event'),
            'type' => 'options',
            'index' => 'event',
            'options' => $this->getColumnFilter('event'),
        ));

        $this->addColumn('oyst_data', array(
            'header' => $helper->__('Oyst Data'),
            'index' => 'oyst_data',
        ));

        $this->addColumn('mage_response', array(
            'header' => $helper->__('Mage Response'),
            'index' => 'mage_response',
        ));

        $this->addColumn('order_id', array(
            'header' => $helper->__('Order ID'),
            'index' => 'order_id',
            'renderer' => 'Oyst_OneClick_Block_Adminhtml_Table_Notifications_Renderer',
        ));

        $this->addColumn('status', array(
            'header' => $helper->__('Status'),
            'type' => 'options',
            'index' => 'status',
            'options' => $this->getColumnFilter('status'),
        ));

        $this->addColumn('created_at', array(
            'header' => $helper->__('Customer Group'),
            'type' => 'datetime',
            'index' => 'created_at',
        ));

        $this->addColumn('executed_at', array(
            'header' => $helper->__('Grand Total'),
            'type' => 'datetime',
            'index' => 'executed_at',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Grid url.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    /**
     * Return row url for js event handlers
     *
     * @param Mage_Catalog_Model_Product|Varien_Object
     *
     * @return null
     */
    public function getRowUrl($item)
    {
        return null;
    }

    /**
     * Get column filter.
     *
     * @param $column
     *
     * @return array
     */
    private function getColumnFilter($column)
    {
        $collection = Mage::getResourceModel('oyst_oneclick/notification_collection')
            ->distinct(true)
            ->addFieldToSelect($column);

        $filter = array();

        foreach ($collection as $item) {
            $filter[$item->getData($column)] = $item->getData($column);
        }

        return $filter;
    }
}

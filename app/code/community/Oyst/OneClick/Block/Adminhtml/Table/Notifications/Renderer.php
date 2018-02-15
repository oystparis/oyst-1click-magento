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
 * Custom column rendered for the Oyst notifications grid
 *
 * Adminhtml_Table_Notifications_Renderer Block
 */
class Oyst_OneClick_Block_Adminhtml_Table_Notifications_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Add link to grid column.
     *
     * @param Varien_Object $row
     *
     * @return mixed|string
     */
    public function render(Varien_Object $row)
    {
        if ($value = $row->getData($this->getColumn()->getIndex())) {
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $value));
            $value = '<a href="' .$url . '">' . $value . '</a>';
        }

        return $value;
    }
}

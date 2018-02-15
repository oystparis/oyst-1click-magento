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
 * Custom grid container for the Oyst notifications table
 *
 * Adminhtml_Table_Notifications Block
 */
class Oyst_OneClick_Block_Adminhtml_Table_Notifications extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Oyst_OneClick_Block_Adminhtml_Table_Notifications constructor.
     */
    public function __construct()
    {
        $this->_blockGroup = 'oyst_oneclick';
        $this->_controller = 'adminhtml_table_notifications';
        $this->_headerText = Mage::helper('oyst_oneclick')->__('Oyst Notifications');

        parent::__construct();
        $this->_removeButton('add');
    }
}

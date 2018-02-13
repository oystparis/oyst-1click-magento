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
 * Adminhtml Observer Model
 */
class Oyst_OneClick_Model_Adminhtml_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Show/Hide the Button 1-Click Tab
     *
     * @param Varien_Event_Observer $observer
     */
    public function removeTab(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('oyst/oneclick/enable')) {
            $block = $observer->getEvent()->getBlock();

            if ($block instanceof Mage_Adminhtml_Block_Widget_Tabs) {
                $groups = Mage::getResourceModel('eav/entity_attribute_group_collection')
                    ->addFieldToFilter('attribute_group_name', array('eq' => 'Button 1-Click'))
                    ->addFieldToSelect('attribute_group_id');
                foreach ($groups as $group) {
                    $block->removeTab('group_' . $group->getAttributeGroupId());
                }
            }
        }
    }
}

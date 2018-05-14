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
 * Resource_Helper for query built queries
 */
class Oyst_OneClick_Model_Resource_Helper
{
    public function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    public function getWriteConnection()
    {
        return $this->getResource()->getConnection('core_write');
    }

    public function inactiveAllCustomerQuotes($customerId)
    {
        if(empty($customerId)) {
            return;
        }

        $resource = $this->getResource();
        $writeConn = $this->getWriteConnection();

        $data = array(
            'is_active'      => '0',
        );

        $writeConn->update(
            $resource->getTableName('sales_flat_quote'),
            $data,
            array('customer_id = ?' => $customerId)
        );
    }
    
    public function inactiveAllOystOrderRelatedQuotes($oystOrderId)
    {
        if(empty($oystOrderId)) {
            return;
        }
            
        $resource = $this->getResource();
        $writeConn = $this->getWriteConnection();
        
        $data = array(
            'is_active'      => '0',
        );
        
        $writeConn->update(
            $resource->getTableName('sales_flat_quote'), 
            $data, 
            array('oyst_order_id = ?' => $oystOrderId)
        );
    }
}


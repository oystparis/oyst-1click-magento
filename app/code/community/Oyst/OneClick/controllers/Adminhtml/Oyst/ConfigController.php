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
 * Adminhtml Config Controller
 */
class Oyst_OneClick_Adminhtml_Oyst_ConfigController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test if user can access to this sections
     *
     * @return bool
     *
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/reset');
    }

    /**
     * Reset module action.
     */
    public function resetAction()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        $tableName = Mage::getResourceModel('oyst_oneclick/notification')->getMainTable();

        try {
            $writeConnection->dropTable($tableName);
            $writeConnection->delete('core_config_data',
                $writeConnection->quoteInto('path LIKE ?', 'payment/oyst_%') .
                $writeConnection->quoteInto(' OR path LIKE ?', 'oyst/oneclick%') .
                $writeConnection->quoteInto(' OR path LIKE ?', 'oyst_oneclick/%')
            );

            $writeConnection->delete('core_resource', $writeConnection->quoteInto('code =?', 'oyst_oneclick_setup'));
            Mage::getSingleton('core/session')->addSuccess(
                Mage::helper('oyst_oneclick')->__('OneClick module was reinstalled successfully.')
            );
            Mage::app()->cleanCache();
            $this->_redirectReferer();
        } catch (Exception $exception) {
            Mage::getSingleton('core/session')->addError(
                Mage::helper('oyst_oneclick')->__('OneClick module was unable to reinstall successfully.')
            );
        }
    }
}

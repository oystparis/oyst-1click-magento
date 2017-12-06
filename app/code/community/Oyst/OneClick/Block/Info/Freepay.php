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
 * Info Freepay block manage info in order
 * Oyst_OneClick_Block_Info Block
 */
class Oyst_OneClick_Block_Info_Freepay extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('oyst/freepay/info_freepay.phtml');
    }

    /**
     * Prepare specific information for the payment block in order
     *
     * @param null $transport
     *
     * @return null|Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $info = $this->getInfo();

        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(
            array(
                $oystHelper->__('Transaction Number') => $info->getLastTransId(),
                $oystHelper->__('Payment Mean') => $info->getCcType(),
                $oystHelper->__('Credit Card No Last 4') => $info->getCcLast4(),
                $oystHelper->__('Expiration Date') => $info->getCcExpMonth() . ' / ' . $info->getCcExpYear(),
            )
        );
        return $transport;
    }
}

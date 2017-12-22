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
 * Form Freepay block manage info in checkout
 * Oyst_OneClick_Block_Form_Freepay Block
 */
class Oyst_OneClick_Block_Form_Freepay extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('oyst/form/freepay.phtml');
    }

    public function getPaymentMethodLabel()
    {
        return Oyst_OneClick_Model_Payment_Method_Freepay::PAYMENT_METHOD_NAME;
    }
}

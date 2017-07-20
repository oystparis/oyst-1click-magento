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
 * Payment_Method_Oyst Model
 */
class Oyst_OneClick_Model_Payment_Method_Oneclick extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_METHOD_NAME = 'Oyst 1-Click';

    protected $_code = 'oyst_oneclick';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_canUseInternal = false;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;
    protected $_canManageRecurringProfiles = false;
}

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

class Oyst_OneClick_Model_ApiWrapper_AbstractType extends Mage_Core_Model_Abstract
{
    /** @var Oyst_OneClick_Model_ApiWrapper_Client $_oystClient */
    protected $_oystClient;

    public function __construct()
    {
        $this->_oystClient = Mage::getModel('oyst_oneclick/apiWrapper_client');
    }
}

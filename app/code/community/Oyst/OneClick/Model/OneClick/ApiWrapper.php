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

use Oyst\Api\OystApiClientFactory;
use Oyst\Api\OystOneClickApi;

/**
 * API Model
 */
class Oyst_OneClick_Model_OneClick_ApiWrapper extends Mage_Core_Model_Abstract
{
    protected $_type = OystApiClientFactory::ENTITY_ONECLICK;

    /**
     * API send
     *
     * @param $dataFormated
     *
     * @return mixed
     */
    public function send($dataFormated)
    {
        /** @var Oyst_OneClick_Model_Api $oystClient */
        $oystClient = Mage::getModel('oyst_oneclick/api');

        /** @var OystOneClickApi $oneclickApi */
        $oneclickApi = $oystClient->getClient($this->_type);

        extract($dataFormated);

        /** @var Oyst_OneClick_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oneclick');

        $oystHelper->defaultValue($productRef);
        $oystHelper->defaultValue($quantity, 1);
        $oystHelper->defaultValue($variationRef, null);
        $oystHelper->defaultValue($user, null);
        $oystHelper->defaultValue($version, 1);

        $result = $oneclickApi->authorizeOrder($productRef, $quantity, $variationRef, $user, $version);

        try {
            $oystClient->validateResult($oneclickApi);
        } catch (Exception $e) {
            return $result;
        }

        return $result;
    }
}

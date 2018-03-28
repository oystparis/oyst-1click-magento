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
 * Shipments Helper
 */
class Oyst_OneClick_Helper_Shipments extends Mage_Core_Helper_Abstract
{
    /**
     * Ignored shipments
     *
     * @var array
     */
    private $ignoredShipments = array(
        'socloz_socloz'
    );

    /**
     * Get ignored shipments
     *
     * @return array
     */
    public function getIgnoredShipments()
    {
        return $this->ignoredShipments;
    }
}

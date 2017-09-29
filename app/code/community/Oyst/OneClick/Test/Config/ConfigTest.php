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
 * Test that the module is configured correctly
 * Config_ConfigTest Model
 */
class Oyst_OneClick_Test_Config_ConfigTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Ensure the module is in the right code pool
     */
    public function testShouldBeInCommunityPool()
    {
        $this->assertModuleCodePool('community');
    }
}

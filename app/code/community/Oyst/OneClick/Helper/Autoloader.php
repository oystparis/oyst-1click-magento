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
 * Autoloader Helper
 */
class Oyst_OneClick_Helper_Autoloader
{
    /*
     * Validate the use of autoload
     */
    public static function createAndRegister()
    {
        $libBaseDir = Mage::getBaseDir() . DS . 'lib/Oyst/oyst-php';
        self::loadComposerAutoLoad($libBaseDir);
    }

    /**
     * Load Composer autoload from lib oyst vendor folder
     *
     * @param string $libBaseDir Path of the Magento lib folder
     */
    public static function loadComposerAutoLoad($libBaseDir)
    {
        static $registered = false;
        if (!$registered) {
            $autoload = $libBaseDir . DS . 'vendor' . DS . 'autoload.php';
            require_once $autoload;
            $registered = true;
        }
    }
}

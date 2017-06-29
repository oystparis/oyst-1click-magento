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

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Pattern to get url. Sometime url like xxxxx/downloader. we can't use baseurl
// Warning : url type http(s)://user:password@test.com/ not autorize.
$pattern = '/(http|ftp|https):\/\/([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])?/';
$url = Mage::getBaseUrl();
$count = preg_match($pattern, $url, $params);

// if result, we set default config in core_config_data, else, user must set manually in backoffice
if ($count) {
    Mage::getConfig()->saveConfig('oyst/oneclick/notification_url', $url . 'oyst_oneclick/notifications/index/');
}

$installer->endSetup();

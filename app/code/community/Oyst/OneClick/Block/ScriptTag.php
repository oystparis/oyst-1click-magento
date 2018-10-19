<?php

class Oyst_OneClick_Block_ScriptTag extends Mage_Core_Block_Template
{
    public function getScriptTag()
    {
        $scriptTag = Mage::getStoreConfig('oyst_oneclick/general/script_tag');
        return str_replace(
            Oyst_OneClick_Helper_Constants::MERCHANT_ID_PLACEHOLDER,
            Mage::getStoreConfig('oyst_oneclick/general/merchant_id'),
            $scriptTag
        );
    }
}

<?php

class Oyst_OneClick_Api_OystConfigController extends Oyst_OneClick_Controller_Api_AbstractController
{
    public function getEcommerceConfigAction()
    {
        $result = Mage::getModel('oyst_oneclick/oystConfigManagement')->getEcommerceConfig();

        $this->getResponse()->setBody(json_encode($result));
    }

    public function saveOystConfigAction()
    {
        $oystConfig = json_decode($this->getRequest()->getParam('oyst_config'), true);

        if ($oystConfig === null) {
            // TODO
        }

        $result = Mage::getModel('oyst_oneclick/oystConfigManagement')->saveOystConfig($oystConfig['oystConfig']);

        $this->getResponse()->setBody(json_encode($result));
    }
}
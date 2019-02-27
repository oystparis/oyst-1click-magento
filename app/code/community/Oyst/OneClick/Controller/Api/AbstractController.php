<?php

class Oyst_OneClick_Controller_Api_AbstractController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        $authorizationHeader = str_replace('Bearer ', '', $this->getRequest()->getHeader('Authorization'));
        if (Mage::getStoreConfig('oyst_oneclick/general/access_token') != $authorizationHeader) {
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', '401', true)
                ->setHeader('Content-Type', 'text/html')
                ->setBody('Unauthorized')
                ->sendResponse();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }

        return $this;
    }

    protected function handleException($e)
    {
        Mage::log($e->__toString(), null, 'error_oyst.log', true);

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('HTTP/1.1', '400 Bad Request')
            ->setBody(json_encode(array(
                'type' => 'M1-Oyst-Error', 
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            )));
    }
}
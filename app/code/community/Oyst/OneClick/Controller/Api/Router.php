<?php

class Oyst_OneClick_Controller_Api_Router  extends Mage_Core_Controller_Varien_Router_Abstract
{
    /**
     * Initialize Controller Router
     *
     * @param Varien_Event_Observer $observer
     */
    public function initControllerRouters($observer)
    {
        /* @var $front Mage_Core_Controller_Varien_Front */
        $front = $observer->getEvent()->getFront();

        $front->addRouter('oyst_oneclick', $this);
    }

    /**
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        if(strpos(strtolower($identifier), 'oyst-oneclick/v1') === false) {
            return false;
        }

        $pathInfoParts = explode('/', $identifier);
        $controller = isset($pathInfoParts[2]) ? $pathInfoParts[2] : '';
        $action = '';
        $params = array();

        if ($controller == 'checkout') {
            if ($request->getMethod() == 'GET') {
                $action = 'getOystCheckoutFromMagentoQuote';
                $params['quote_id'] = $pathInfoParts[3];
            } elseif ($request->getMethod() == 'PUT') {
                $action = 'syncMagentoQuoteWithOystCheckout';
                $params['oyst_checkout'] = $request->getRawBody();
            }
        }
        if ($controller == 'order') {
            if (isset($pathInfoParts[4]) 
             && $pathInfoParts[4] == 'status' 
             && $request->getMethod() == 'PUT') {
                $action = 'syncMagentoOrderWithOystOrderStatus';
                $params['oyst_order_id'] = $pathInfoParts[3];
                $params['oyst_order'] = $request->getRawBody();
            } if (isset($pathInfoParts[4]) 
             && $pathInfoParts[4] == 'refund' 
             && $request->getMethod() == 'POSTT') {
                $controller = 'refund';
                $action = 'createMagentoCreditmemo';
                $params['oyst_order_id'] = $pathInfoParts[3];
                $params['oyst_refund'] = $request->getRawBody();
            } elseif ($request->getMethod() == 'GET') {
                $action = 'getOystOrderFromMagentoOrder';
                $params['oyst_order_id'] = $pathInfoParts[3];
            } elseif ($request->getMethod() == 'POST') {
                $action = 'createOrderFromOystOrder';
                $params['oyst_order'] = $request->getRawBody();
            }
        }
        if ($controller == 'config') {
            if ($request->getMethod() == 'GET') {
                $action = 'getEcommerceConfig';
            } elseif ($request->getMethod() == 'PUT') {
                $action = 'saveOystConfig';
                $params['oyst_config'] = $request->getRawBody();
            }
        }

        if (empty($controller) || empty($action)) {
            Mage::app()->getFrontController()->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', '400', true)
                ->setHeader('Content-Type', 'text/html')
                ->setBody('Bad Request')
                ->sendResponse();
            exit;
        }

        $request->setModuleName('oyst_oneclick')
            ->setControllerName('api_oyst'.ucfirst($controller))
            ->setActionName($action)
            ->setParams($params);

        return true;
    }
}


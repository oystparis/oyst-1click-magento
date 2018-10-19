<?php

/**
 * This class has for purpose to expose callback API :
 * 2 services are currently handled :
 *  - capture (this is supposed to be called only if merchant config is on manual capture)
 *  - refund
 */

class Oyst_OneClick_Gateway_CallbackClient
{
    public function callGatewayCallbackApi($endpointType, array $oystOrderIds)
    {
        $endpoint = $this->getEndpoint($endpointType);

        $client = new Zend_Http_Client();
        $client->setHeaders('Authorization', 'Bearer ' . $endpoint['api_key']);
        $client->setHeaders('Accept', 'application/json');
        $client->setHeaders('Content-Type', 'application/json');
        $client->setRawData(json_encode([
            'orderIds' => $oystOrderIds
        ]))->setEncType('application/json');
        $client->setUri($endpoint['url']);

        $response = $client->request('POST');

        return $response->getBody();
    }

    protected function getEndpoint($endpointType)
    {
        $endpoints = json_decode(Mage::getStoreConfig('oyst_oneclick/general/endpoints'), true);
        foreach ($endpoints as $endpoint) {
            if ($endpoint['type'] == $endpointType) {
                return $endpoint;
            }
        }

        throw new Exception('Invalid endpoint type : '.$endpointType);
    }
}
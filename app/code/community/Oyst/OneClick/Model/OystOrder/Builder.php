<?php

class Oyst_OneClick_Model_OystOrder_Builder
{
    protected $oystOrderFactory;

    public function buildOystOrder(
        Mage_Sales_Model_Order $order
    )
    {
        $oystOrder = array();

        $oystOrder['oyst_id'] = $order->getOystId();
        $oystOrder['internal_id'] = $order->getIncrementId();

        // TODO : All members
        Mage::dispatchEvent(
            'oyst_oneclick_model_oyst_order_builder_build_oyst_order',
            array('oyst_order' => $oystOrder, 'order' => $order)
        );

        return $oystOrder;
    }
}
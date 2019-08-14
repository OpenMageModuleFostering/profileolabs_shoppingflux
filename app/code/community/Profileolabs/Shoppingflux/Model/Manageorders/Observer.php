<?php

class Profileolabs_Shoppingflux_Model_Manageorders_Observer {

    public function setCustomerTaxClassId($observer) {
        if (!$this->getConfig()->applyTax() && Mage::getSingleton('checkout/session')->getIsShoppingFlux()) {
            $customerGroup = $observer->getEvent()->getObject();
            $customerGroup->setData('tax_class_id', 999);
        }
    }

    public function manageOrders($observer) {
        try {
            set_time_limit(0);
            Mage::getModel('profileolabs_shoppingflux/manageorders_order')->manageOrders();
        } catch (Exception $e) {
            Mage::throwException($e);
        }

        return $this;
    }

    public function getShipmentTrackingNumber($shipment) {
        $result = false;
        $tracks = $shipment->getAllTracks();
        $trackUrl = '';
        if (is_array($tracks) && !empty($tracks)) {
            $firstTrack = array_shift($tracks);
            if (preg_match('%^owebia%i', $firstTrack->getCarrierCode())) {
                $carrierInstance = Mage::getSingleton('shipping/config')->getCarrierInstance($firstTrack->getCarrierCode());
                if ($carrierInstance) {
                    // Gestion du cas Owebia Shipping
                    $trackingInfo = $carrierInstance->getTrackingInfo($firstTrack->getData('number'));
                    $status = $trackingInfo->getStatus();
                    if (preg_match('%href="(.*)"%i', $status, $regs)) {
                        $trackUrl = $regs[1];
                    }
                }
            }
            if (trim($firstTrack->getData('number'))) {
                $result = array('trackUrl' => $trackUrl, 'trackId' => $firstTrack->getData('number'), 'trackTitle' => $firstTrack->getData('title'));
            }
        }
        $dataObj = new Varien_Object(array('result' => $result, 'shipment'=>$shipment));
        Mage::dispatchEvent('shoppingflux_get_shipment_tracking', array('data_obj' => $dataObj));
        $result = $dataObj->getEntry();
        
        return $result;
    }
    
    public function sendStatusCanceled($observer) {
        $order = $observer->getEvent()->getOrder();
        if (!$order->getFromShoppingflux())
            return $this;
        
        
        $storeId = $order->getStoreId();

        $apiKey = $this->getConfig()->getApiKey($storeId);
        $wsUri = $this->getConfig()->getWsUri();
        
        
        $orderIdShoppingflux = $order->getOrderIdShoppingflux();
        $marketPlace = $order->getMarketplaceShoppingflux();

        $service = new Profileolabs_Shoppingflux_Model_Service($apiKey, $wsUri);

        try {
            $service->updateCanceledOrder(
                    $orderIdShoppingflux, $marketPlace, Profileolabs_Shoppingflux_Model_Service::ORDER_STATUS_CANCELED
            );
        } catch(Exception $e) {
            
        }
        
        Mage::helper('profileolabs_shoppingflux')->log('Order ' . $orderIdShoppingflux . ' has been canceled. Information sent to ShoppingFlux.');

        
    }
    
    
    public function scheduleShipmentUpdate($observer) {
        $shipment = $observer->getShipment();
        Mage::getModel('profileolabs_shoppingflux/manageorders_export_shipments')->scheduleShipmentExport($shipment->getId());
    }

    public function sendScheduledShipments() {
        $collection = Mage::getModel('profileolabs_shoppingflux/manageorders_export_shipments')->getCollection();
        $collection->addFieldToFilter('updated_at', array('lt'=>new Zend_Db_Expr("DATE_SUB('".date('Y-m-d H:i:s')."', INTERVAL 60 MINUTE)")));
        foreach($collection as $item) {
            try {
                $this->sendStatusShipped($item->getShipmentId());
                $item->delete();
            } catch(Exception $e) {
                $shipment = Mage::getModel('sales/order_shipment')->load($item->getShipmentId());
                $message = 'Erreur de mise à jour de l\'expédition #'.$shipment->getIncrementId().' (commande #'.$shipment->getOrder()->getIncrementId().') : <br/>' . $e->getMessage();
                $message .= "<br/><br/> Merci de vérifier les infos de votre commandes ou de contacter le support Shopping Flux ou celui de la place de marché";
                $this->getHelper()->notifyError($message);
                if($item->getId() && !preg_match('%Error in cURL request: connect.. timed out%', $message)) {
                    try {
                        $item->delete();
                    } catch(Exception $e) {}
                }
            }
        }
        return $this;
    }
    
    public function sendStatusShipped($shipmentId) {
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        if(!$shipment->getId())
            return $this;
        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();

        $apiKey = $this->getConfig()->getApiKey($storeId);
        $wsUri = $this->getConfig()->getWsUri();

        //Mage::log("order = ".print_r($order->debug(),true),null,'debug_update_status_sf.log');

        if (!$order->getFromShoppingflux())
            return $this;
        
        if($order->getShoppingfluxShipmentFlag()) { 
            return $this;
        }

        $trakingInfos = $this->getShipmentTrackingNumber($shipment);



        $orderIdShoppingflux = $order->getOrderIdShoppingflux();
        $marketPlace = $order->getMarketplaceShoppingflux();


        //Mage::log("OrderIdSF = ".$orderIdShoppingflux." MP: ".$marketPlace,null,'debug_update_status_sf.log');

        /* @var $service Profileolabs_Shoppingflux_Model_Service */
        $service = new Profileolabs_Shoppingflux_Model_Service($apiKey, $wsUri);
        $result = $service->updateShippedOrder(
                $orderIdShoppingflux, $marketPlace, Profileolabs_Shoppingflux_Model_Service::ORDER_STATUS_SHIPPED, $trakingInfos ? $trakingInfos['trackId'] : '', $trakingInfos ? $trakingInfos['trackTitle'] : '', $trakingInfos ? $trakingInfos['trackUrl'] : ''
        );


        if ($result) {
            if ($result->Response->Orders->Order->StatusUpdated == 'False') {
                Mage::throwException('Error in update status shipped to shopping flux');
            } else {
                $status = $result->Response->Orders->Order->StatusUpdated;
                //Mage::log("status = ".$status,null,'debug_update_status_sf.log');

                $order->setShoppingfluxShipmentFlag(1);
                $order->save();
                $this->getHelper()->log($this->getHelper()->__("Order %s updated to shopping flux.Status returned %s", $orderIdShoppingflux, $status));
            }
        } else {
            $this->getHelper()->log($this->getHelper()->__("Error in update status shipped to shopping flux"));
            Mage::throwException($this->getHelper()->__("Error in update status shipped to shopping flux"));
        }

        return $this;
    }

    /**
     * Retrieve config
     * @return Profileolabs_Shoppingflux_Model_Manageorders_Config
     */
    public function getConfig() {
        return Mage::getSingleton('profileolabs_shoppingflux/config');
    }

    /**
     * Get Helper
     * @return Profileolabs_Shoppingflux_Model_Manageorders_Helper_Data
     */
    public function getHelper() {
        return Mage::helper('profileolabs_shoppingflux');
    }

}
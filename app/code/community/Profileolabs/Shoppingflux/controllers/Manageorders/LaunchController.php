<?php

class Profileolabs_Shoppingflux_Manageorders_LaunchController extends Mage_Core_Controller_Front_Action {

  

    public function getordersAction() {
    	Mage::getModel('profileolabs_shoppingflux/manageorders_order')->manageOrders();
    }

    public function updateordersAction() {
    	Mage::getModel('profileolabs_shoppingflux/manageorders_observer')->sendScheduledShipments();
    }
    
    public function getupdateorderscountAction() {
    	die('Count : ' . Mage::getModel('profileolabs_shoppingflux/manageorders_export_shipments')->getCollection()->count());
    }
}

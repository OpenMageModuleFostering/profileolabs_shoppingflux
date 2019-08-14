<?php

/**
 * Shopping Flux
 * @category   ShoppingFlux
 * @package    Profileolabs_Shoppingflux_ManageOrders
 * @author kassim belghait
 */
class Profileolabs_Shoppingflux_Manageorders_Adminhtml_ImportController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('shoppingflux/manageorders/import')
                ->_addBreadcrumb(Mage::helper('profileolabs_shoppingflux')->__('ShoppingFlux order import'), Mage::helper('profileolabs_shoppingflux')->__('ShoppingFlux order import'));

        return $this;
    }

    public function indexAction() {
        $this->_initAction()
                ->renderLayout();

        return $this;
    }

    public function importOrdersAction() {
        try {

            if (!Mage::getSingleton('profileolabs_shoppingflux/config')->isOrdersEnabled())
                Mage::throwException(Mage::helper('profileolabs_shoppingflux')->__("Order import is disabled in ShoppingFlux configuration. You must enable it to use this functionnality."));


            error_reporting(E_ALL | E_STRICT);
            ini_set("display_errors", 1);

            /* @var $model Profileolabs_Shoppingflux_ManageOrders_Model_Order */


            $model = Mage::getModel('profileolabs_shoppingflux/manageorders_order')->manageOrders();

            $this->_getSession()->addSuccess(Mage::helper('profileolabs_shoppingflux')->__("%d orders have been imported", $model->getNbOrdersImported()));

            if ($model->getResultSendOrder() != "") {
                $this->_getSession()->addSuccess(Mage::helper('profileolabs_shoppingflux')->__("Orders sent result : %s", $model->getResultSendOrder()));
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        $this->_redirect("*/*/index");
    }
    
    

}
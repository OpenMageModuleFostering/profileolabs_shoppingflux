<?php

/**
 * Shopping Flux
 * @category   ShoppingFlux
 * @package    Profileolabs_Shoppingflux
 * @author vincent enjalbert
 */
include_once "Mage/Adminhtml/controllers/Catalog/CategoryController.php";

class Profileolabs_Shoppingflux_Export_Adminhtml_CategoryController extends Mage_Adminhtml_Catalog_CategoryController {

    /**
     * Grid Action
     * Display list of products related to current category
     *
     * @return void
     */
    public function gridAction() {
        if (!$category = $this->_initCategory(true)) {
            return;
        }
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('profileolabs_shoppingflux/export_adminhtml_catalog_category_tab_default', 'shoppingflux.product.grid')
                        ->toHtml()
        );
    }

}
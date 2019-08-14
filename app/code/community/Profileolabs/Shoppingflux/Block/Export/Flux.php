<?php

class Profileolabs_Shoppingflux_Block_Export_Flux extends Mage_Core_Block_Template {

    protected function _toHtml() {
        $useAllStores = $this->getForceMultiStores() || $this->getConfig()->getUseAllStoreProducts();
        if ($this->getProductSku() && $this->getRequest()->getParam('update') == 1) {
            if($this->getConfig()->getUseAllStoreProducts()) {
                Mage::getModel('profileolabs_shoppingflux/export_flux')->updateProductInFlux($this->getProductSku(), Mage::app()->getStore()->getId());
            } else {
                Mage::getModel('profileolabs_shoppingflux/export_flux')->updateProductInFluxForAllStores($this->getProductSku());
            }
        }
        
        Mage::getModel('profileolabs_shoppingflux/export_flux')->updateFlux($useAllStores?false:Mage::app()->getStore()->getId(), $this->getLimit() ? $this->getLimit() : 1000000);
        $collection = Mage::getModel('profileolabs_shoppingflux/export_flux')->getCollection();
        $collection->addFieldToFilter('should_export', 1);
        if($useAllStores) {
            $collection->getSelect()->group(array('sku'));
        } else {
            $collection->addFieldToFilter('store_id', Mage::app()->getStore()->getId());
        }
        $sizeTotal = $collection->count();
        $collection->clear();

        if (!$this->getConfig()->isExportSoldout()) {
            $collection->addFieldToFilter('is_in_stock', 1);
        }
        if ($this->getConfig()->isExportFilteredByAttribute()) {
            $collection->addFieldToFilter('is_in_flux', 1);
        }
        $visibilities = $this->getConfig()->getVisibilitiesToExport();
        $collection->getSelect()->where("find_in_set(visibility, '" . implode(',', $visibilities) . "')");


        $xmlObj = Mage::getModel('profileolabs_shoppingflux/export_xml');
        echo $xmlObj->startXml(array('size-exportable' => $sizeTotal, 'size-xml' => $collection->count(), 'with-out-of-stock' => intval($this->getConfig()->isExportSoldout()), 'selected-only' => intval($this->getConfig()->isExportFilteredByAttribute()), 'visibilities' => implode(',', $visibilities)));


        if ($this->getProductSku()) {
            $collection->addFieldToFilter('sku', $this->getProductSku());
        }
        if ($this->getLimit()) {
            $collection->getSelect()->limit($this->getLimit());
        }


        Mage::getSingleton('core/resource_iterator')
                ->walk($collection->getSelect(), array(array($this, 'displayProductXml')), array());
        echo $xmlObj->endXml();
        return;
    }

    public function displayProductXml($args) {
        if (Mage::app()->getRequest()->getActionName() == 'profile') {
            Mage::getModel('profileolabs_shoppingflux/export_flux')->updateProductInFlux($args['row']['sku'], Mage::app()->getStore()->getId());
        }
        echo $args['row']['xml'];
    }

    public function getConfig() {
        return Mage::getSingleton('profileolabs_shoppingflux/config');
    }

}

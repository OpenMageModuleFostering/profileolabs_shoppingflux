<?php

class Profileolabs_Shoppingflux_Export_FluxController extends Mage_Core_Controller_Front_Action {

    public function phpinfoAction() {
        //phpinfo();die();
    }

    public function testAction() {
        ini_set('display_errors', 1);
        //Mage::app()->cleanCache();
       /* $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $writeConnection = $resource->getConnection('core_write');
        $installer = Mage::getResourceModel('catalog/setup','profileolabs_shoppingflux_setup');
        $installer->run("
    UPDATE `{$installer->getTable('shoppingflux_export_flux')}`  SET update_needed = 1, should_export = 1;
    ALTER TABLE  `{$installer->getTable('shoppingflux_export_flux')}` ADD  `price_value` decimal( 12, 4 ) NOT NULL AFTER  `stock_value`;
    ALTER TABLE  `{$installer->getTable('shoppingflux_export_flux')}` ADD  `salable` tinyint( 1 ) NOT NULL AFTER  `is_in_stock`;
        ");
        $results = $readConnection->fetchAll('SHOW COLUMNS FROM '.$installer->getTable('shoppingflux_export_flux'));*/
        var_dump($results);die();
        die('TESTS_END');
    }

    public function refreshAllAction() {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $storeId = Mage::app()->getStore()->getId();
        $write->beginTransaction();
        try {
            $query = "update " . Mage::getSingleton('core/resource')->getTableName('profileolabs_shoppingflux/export_flux') . " set update_needed = 1 where store_id = '" . $storeId . "' and should_export = 1";
            $write->query($query);
            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
        }
        die('Le flux shopping flux sera mis a jour pour ce flux');
    }

    public function refreshAllAllStoresAction() {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $storeId = Mage::app()->getStore()->getId();
        $write->beginTransaction();
        try {
            $query = "update " . Mage::getSingleton('core/resource')->getTableName('profileolabs_shoppingflux/export_flux') . " set update_needed = 1 where should_export = 1";
            $write->query($query);
            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
        }
        die('Le flux shopping flux sera mis a jour pour ce flux');
    }

    public function refreshEverythingAction() {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $storeId = Mage::app()->getStore()->getId();
        $write->beginTransaction();
        try {
            $query = "update " . Mage::getSingleton('core/resource')->getTableName('profileolabs_shoppingflux/export_flux') . " set update_needed = 1, should_export = 1 where store_id = '" . $storeId . "'";
            $write->query($query);
            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
        }
        die('Le flux shopping flux sera mis a jour pour ce flux');
    }

    public function refreshEverythingAllStoresAction() {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $storeId = Mage::app()->getStore()->getId();
        $write->beginTransaction();
        try {
            $query = "update " . Mage::getSingleton('core/resource')->getTableName('profileolabs_shoppingflux/export_flux') . " set update_needed = 1, should_export = 1";
            $write->query($query);
            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
        }
        die('Le flux shopping flux sera mis a jour pour ce flux');
    }

    public function statusAction() {
        $storeId = Mage::app()->getStore()->getId();
        $productCollection = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->setStoreId($storeId);
        $productCount = $productCollection->count();
        $collection = Mage::getModel('profileolabs_shoppingflux/export_flux')->getCollection();
        $feedCount = $collection->count();
        $collection->clear();
        $collection->addFieldToFilter('update_needed', 1);
        $feedUpdateNeededCount = $collection->count();
        if(!headers_sent()) {
    		header('Content-type: text/xml; charset=UTF-8');
        }
        echo "<status>";
        echo "<feed_generation>";
        echo "<product_count>{$productCount}</product_count>";
        echo "<feed_count>{$feedCount}</feed_count>";
        echo "<feed_update_needed_count>{$feedUpdateNeededCount}</feed_update_needed_count>";
        echo "</feed_generation>";
        echo "</status>";
        exit();
    }
    
    public function indexAction() {
        Mage::register('export_feed_start_at', microtime(true));
    	error_reporting(-1);
        ini_set('display_errors', 1);
        set_time_limit(0);
        ini_set("memory_limit", Mage::getSingleton('profileolabs_shoppingflux/config')->getMemoryLimit() . "M");
        
        

        
        $limit = $this->getRequest()->getParam('limit');
        $productSku = $this->getRequest()->getParam('product_sku');
        $forceMultiStores = $this->getRequest()->getParam('force_multi_stores', false);
        
        if(!headers_sent()) {
    		header('Content-type: text/xml; charset=UTF-8');
        }

        $block = $this->getLayout()->createBlock('profileolabs_shoppingflux/export_flux', 'sf.export.flux');
        if ($limit) {
            $block->setLimit($limit);
        }
        if ($productSku) {
            $block->setProductSku($productSku);
        }
        if($forceMultiStores) {
            $block->setForceMultiStores(true);
        }

        $block->toHtml();

        exit();
    }

    // V1 DEPRECATED
    public function v1Action() {
        $limit = $this->getRequest()->getParam('limit');
        $productSku = $this->getRequest()->getParam('product_sku');

        /**
         * Error reporting
         */
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);
        set_time_limit(0);
        /* $this->getResponse()
          ->setHttpResponseCode(200)
          ->setHeader('Pragma', 'public', true)
          ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
          ->setHeader('Content-type', 'text/xml; charset=UTF-8'); */
        header('Content-type: text/xml; charset=UTF-8');


        try {
            $block = $this->getLayout()->createBlock('profileolabs_shoppingflux/export_flow', 'sf.export.flow');
            if ($limit) {
                $block->setLimit($limit);
            }
            if ($productSku) {
                $block->setProductSku($productSku);
            }
            $block->toHtml();

            /* $this->loadLayout(false);
              if($limit) {
              $block = $this->getLayout()->getBlock('sf.export.flow');
              $block->setLimit($limit);
              }
              $this->renderLayout(); */

            //$block = $this->getLayout()->createBlock('profileolabs_shoppingflux/export_flow','sf.export.flow');
            //$output = $block->toHtml();
            //$this->getResponse()->setBody($output);
        } catch (Exception $e) {

            Mage::throwException($e);
        }



        return $this;
    }

    public function profileAction() {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);
        set_time_limit(0);

        $limit = $this->getRequest()->getParam('limit');
        $productSku = $this->getRequest()->getParam('product_sku');

        header('Content-type: text/html; charset=UTF-8');

        $block = $this->getLayout()->createBlock('profileolabs_shoppingflux/export_flux', 'sf.export.flux');
        if ($limit) {
            $block->setLimit($limit);
        }
        if ($productSku) {
            $block->setProductSku($productSku);
        }
        $block->toHtml();
        $block = $this->getLayout()->createBlock('core/profiler', 'profiler');
        $output = $block->toHtml();

        $this->getResponse()->setBody($output);
        return $this;
    }

}

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
    ALTER TABLE  `{$installer->getTable('shoppingflux_export_flux')}` ADD  `product_id` int( 11 ) NOT NULL default 0 AFTER  `id`;
        ");
        $results = $readConnection->fetchAll('SHOW COLUMNS FROM '.$installer->getTable('shoppingflux_export_flux'));
        var_dump($results);die();*/
        
        
        //Mage::getModel('profileolabs_shoppingflux/export_flux')->checkForDeletedProducts();
        //Mage::helper('profileolabs_shoppingflux')->newInstallation();
        
        
        //$installer = Mage::getResourceModel('catalog/setup','profileolabs_shoppingflux_setup');
        //$sql = "delete from `".$installer->getTable('core/config_data')."` where `path` = 'shoppingflux_export/attributes_mapping/additional,'";
        //$sql = sprintf("UPDATE `%s` SET `path` = '%s' WHERE `path` = '%s'",$installer->getTable('core/config_data'),  'shoppingflux_export/attributes_mapping/additional', 'shoppingflux_export/attributes_additionnal/list');
         //$installer->run($sql);
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
        /*if(!$this->getRequest()->getParam('bypasscheck', false)) {
            Mage::getModel('profileolabs_shoppingflux/export_flux')->checkForDeletedProducts();
        }*/
        ini_set('display_errors',1);
        error_reporting(-1);
        
        $storeId = Mage::app()->getStore()->getId();
        $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addStoreFilter($storeId)
                ->setStoreId($storeId);
        $productCount = $productCollection->getSize();
        
        
       /* $collection = Mage::getModel('profileolabs_shoppingflux/export_flux')->getCollection();
        $collection->addFieldToFilter('store_id', $storeId);
        $feedCount = $collection->count();
         $feedExportCount = $feedUpdateNeededCount = 0;
        foreach($collection as $feedProduct) {
            if($feedProduct->getUpdateNeeded()) {
                $feedUpdateNeededCount++;
            }
            if($feedProduct->getShouldExport()) {
                $feedExportCount++;
            }
        }*/
        $_conn = Mage::getSingleton('core/resource')->getConnection('read');
        $results = $_conn->fetchAll('SELECT SUM(update_needed) as total_needed, SUM(should_export) as total_export, count(*) as total from ' . Mage::getSingleton('core/resource')->getTableName('profileolabs_shoppingflux/export_flux'));
        list($feedUpdateNeededCount, $feedExportCount, $feedCount) = array_values($results[0]);
        $feedNotExportCount = $feedCount-$feedExportCount;
        if(!headers_sent()) {
    		header('Content-type: text/xml; charset=UTF-8');
        }
        echo '<status version="'.Mage::getConfig()->getModuleConfig("Profileolabs_Shoppingflux")->version.'">';
        echo "<feed_generation>";
        echo "<product_count>{$productCount}</product_count>";
        echo "<feed_count>{$feedCount}</feed_count>";
        echo "<feed_not_export_count>{$feedNotExportCount}</feed_not_export_count>";
        echo "<feed_export_count>{$feedExportCount}</feed_export_count>";
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

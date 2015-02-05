<?php

/**
 * this is the Petolio application bootstrap
 * the code what is commented has been moved to the Petolio_Plugins_Layoutsetup plugin and will be removed soon
 * @author Lotzi
 */
class Thread extends Zend_Application_Bootstrap_Bootstrap {
	protected function _initLog() {
	    $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/log/thread.log');
		$logger = new Zend_Log($writer);
		Zend_Registry::set('Zend_Log', $logger);
	}

	protected function _initDb()
	{
	    $resource = $this->getPluginResource('db');
	    $db = $resource->getDbAdapter();
		Zend_Db_Table::setDefaultAdapter($db);
	    Zend_Registry::set("db", $db);
	}

	protected function _initConfig() {
    	Zend_Registry::set('config', $this->getOptions());
	}
}
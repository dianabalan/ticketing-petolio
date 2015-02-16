<?php

/**
 * this is the Petolio application bootstrap
 * the code what is commented has been moved to the Petolio_Plugins_Layoutsetup plugin and will be removed soon
 * @author Lotzi
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	protected function _initModules() {
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->addModuleDirectory(APPLICATION_PATH.'/modules');
		$frontController->setDefaultModule('frontend');

		$frontController->registerPlugin(new Petolio_Plugins_Layoutsetup());
	}

	protected function _initSession() {
		//get your database connection ready
		$config = $this->getOptions();
		$db_adapter = new Zend_Db_Adapter_Pdo_Mysql(array(
			'dbname' => $config['resources']['db']['params']['dbname'],
			'username' => $config['resources']['db']['params']['username'],
			'password' => $config['resources']['db']['params']['password'],
			'charset' => $config['resources']['db']['params']['charset']
		));

		// you can either set the Zend_Db_Table default adapter or you can pass the db connection straight to the save handler $options
		Zend_Db_Table_Abstract::setDefaultAdapter($db_adapter);
		$options = array(
		    'name'				=> 'po_sessions',
		    'primary'			=> 'id',
		    'modifiedColumn'	=> 'modified',
		    'dataColumn'		=> 'data',
		    'lifetimeColumn'	=> 'lifetime',
			'overrideLifetime'	=> true
		);

		// create your Zend_Session_SaveHandler_DbTable and set the save handler for Zend_Session
		Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($options));

		// start your session!
		Zend_Session::start();

		// update timezone
		Petolio_Service_Util::updateTimezone();
	}

	protected function _initConfig() {
    	Zend_Registry::set('config', $this->getOptions());
	}
}
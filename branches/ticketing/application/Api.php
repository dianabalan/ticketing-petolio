<?php

/**
 * Api bootstrap
 * @author Lotzi
 */
class Api extends Zend_Application_Bootstrap_Bootstrap {
	
	protected function _initLog() {
		$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/log/api.log');
		$logger = new Zend_Log($writer);
		Zend_Registry::set('Zend_Log', $logger);
	}

	protected function _initDb() {
		$resource = $this->getPluginResource('db');
		$db = $resource->getDbAdapter();
		Zend_Db_Table::setDefaultAdapter($db);
		Zend_Registry::set("db", $db);
	}

	protected function _initConfig() {
		$frontController = Zend_Controller_Front::getInstance();
		$restRoute = new Zend_Rest_Route($frontController);
		$frontController->getRouter()->addRoute('default', $restRoute);
		
		$options = $this->getOptions();
		Zend_Registry::set('config', $options);
		define("PO_BASE_URL", $options["petolio"]["host"]);
	}
	
	protected function _initTranslation() {
		$logger = Zend_Registry::get('Zend_Log');
		$translate = new Petolio_Translate(
				array(
						'adapter' => 'gettext',
						'content' => APPLICATION_PATH . '/../languages/german.mo',
						'locale'  => 'de',
						'log' => $logger
				)
		);
		$translate->addTranslation(
				array(
						'content' => APPLICATION_PATH . '/../languages/english.mo',
						'locale'  => 'en'
				)
		);
		
		/*
		 * Detect if the user has chosen a different language
		* DO NOT CHANGE THIS ORDER, OTHERWISE THE LANGUAGE SELECTION DOESN'T WORK
		*/
		if ( isset($_COOKIE['petolio_language']) ) {
			// By default, the locale will be detected from the user's browser.
			$selected_locale = $_COOKIE['petolio_language'];
		} elseif ( $this->detectBrowserLanguage() == 'de' ) {
			$selected_locale = 'de';
		} else {
			$selected_locale = 'en';
		}
		setcookie('petolio_language', $selected_locale, time() + 365 * 24 * 60 * 60, '/');
		
		$locale = new Zend_Locale($selected_locale);
		
		// Check if the locale is available
		if ( $translate->isAvailable($locale) ) {
			$translate->setLocale($locale);
		}
		
		// this must be 'en' for the Float and Date validators
		$validation_locale = new Zend_Locale('en');
		Zend_Registry::set('Zend_Locale', $validation_locale);
		Zend_Registry::set('Zend_Translate', $translate);
		
		// translate the validation messages
		$options = array (
				'adapter' => 'array',
				'content' => APPLICATION_PATH . '/../languages',
				'locale' => $translate->getLocale(),
				'scan' => Zend_Translate::LOCALE_DIRECTORY
		);
		$translator = new Zend_Translate($options);
		Zend_Validate_Abstract::setDefaultTranslator($translator);
		
		// translate db fields
		$config = Zend_Registry::get('config');
		$db_adapter = Zend_Registry::get('db');
		
		$data = array(
				'dbAdapter'     => $db_adapter,
				'tableName'		=> 'po_translations',
				'localeField'	=> 'language',
				'keyField'		=> 'label',
				'valueField'	=> 'value',
		);
		
		$options = array ();
		
		try {
			$db_translate = new Zend_Translate('Petolio_Translate_Adapter_Db', $data, 'en', $options);
			$db_translate->addTranslation($data, 'de', $options);
			$db_translate->addTranslation($data, 'ln', $options);
		
			// Check if the locale is available
			if ( $db_translate->isAvailable($locale) ) {
				$db_translate->setLocale($locale);
			}
		
			Zend_Registry::set('Zend_Translate_Db', $db_translate);
		} catch (Zend_Translate_Exception $zte) {
			echo 'Zend_Translate_Exception: ' . $zte->getMessage();
		}
		
		$this->bootstrap('view');
    	$view = $this->getResource('view');
		$view->translate = $translate;
		
		setlocale(LC_ALL, 'en_US');
		if ( $locale->toString() == 'de' ) {
			setlocale(LC_TIME, 'de_DE.UTF-8', 'de_DE@euro', 'de_DE', 'de', 'ge');
		}
	}

	/**
	 * detect the preferred language of the user agent
	 * split request header Accept-Language to switch
	 * between english and german, default is english
	 *
	 * @param string $defaultlang preselected language, default en
	 * @return string returns 'de' or 'en'
	 */
	private function detectBrowserLanguage($defaultlang = 'en') {
		$lang = $defaultlang;
		if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
			$langlist = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach($langlist as $curLang) {
				$curLang = explode(';', $curLang);
				/* use regular expression for language detection */
				if (preg_match('/(en|de)-?.*/', $curLang[0], $reg)) {
					$lang = $reg[1];
					break;
				}
			}
		}
		return $lang;
	}
}
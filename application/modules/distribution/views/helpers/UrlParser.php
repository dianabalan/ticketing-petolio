<?php

class Zend_View_Helper_UrlParser extends Zend_View_Helper_Abstract {

	/**
	 * parses an url using the current route
	 * returns an array, where is specified the module, controller, action and other parameters
	 *
	 * @param string $url
	 */
	public function urlParser($url) {
		return Petolio_Service_Util::parseUrl($url);
	}
}
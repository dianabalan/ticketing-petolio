<?php 

class Zend_View_Helper_GetRequestParam extends Zend_View_Helper_Abstract {
	
    public function getRequestParam($name) {
    	return Zend_Controller_Front::getInstance()->getRequest()->getParam($name, null);
    }
    
}
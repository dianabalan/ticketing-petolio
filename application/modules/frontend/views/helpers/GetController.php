<?php 

class Zend_View_Helper_GetController extends Zend_View_Helper_Abstract {
	
    public function getController() {
        return Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
    }
    
}
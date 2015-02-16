<?php

class ErrorController extends Zend_Controller_Action
{
	public function errorAction()
	{
		// get translate obj
		$translate = Zend_Registry::get('Zend_Translate');

		// get error handler
		$errors = $this->_getParam('error_handler');
		if(!$errors || !$errors instanceof ArrayObject) {
			$this->view->message = $translate->_('You have reached the error page');
			return;
		}

		// switch between error types
		switch ($errors->type) {
			// no route
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
				break;

			// no controller
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
				// get params and load microsite
				$this->getResponse()->setHttpResponseCode(404);
				$priority = Zend_Log::NOTICE;
				$this->view->message = $translate->_('Page not found');
				break;

			// no action
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->getResponse()->setHttpResponseCode(404);
				$priority = Zend_Log::NOTICE;
				$this->view->message = $translate->_('Page not found');
				break;

			// application error
			default:
				$this->getResponse()->setHttpResponseCode(500);
				$priority = Zend_Log::CRIT;
				$this->view->message = $translate->_('Application error');
				break;
		}

		// Log exception, if logger available
		$log = $this->getLog();
		if($log != false) {
			$log->log($this->view->message, $priority, $errors->exception);
			$log->log('Request Parameters', $priority, $errors->request->getParams());
		}

		// conditionally display exceptions
		if($this->getInvokeArg('displayExceptions') == true) {
			$this->view->exception = $errors->exception;
		}

		// write exceptions in log
		Zend_Registry::get('Zend_Log')->err("Exception -> Message: {$errors->exception->getMessage()}");
		Zend_Registry::get('Zend_Log')->err("Exception -> Trace: {$errors->exception->getTraceAsString()}");
		Zend_Registry::get('Zend_Log')->err("Exception -> Params: " . print_r($errors->request->getParams(), true));

		// bla bla bla
		$this->view->request = $errors->request;
		$this->view->translate = $translate;
	}

	public function getLog()
	{
		$bootstrap = $this->getInvokeArg('bootstrap');
		if(!$bootstrap->hasResource('Log'))
			return false;

		return $bootstrap->getResource('Log');
	}
}
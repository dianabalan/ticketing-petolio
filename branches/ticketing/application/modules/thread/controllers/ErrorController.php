<?php

class ErrorController extends Zend_Controller_Action {

	public function errorAction() {
		// get error handler
		$errors = $this->_getParam('error_handler');
		echo "Thread error!";
		if (!$errors || !$errors instanceof ArrayObject)
			return;

		echo $errors->exception;

		// Log exception, if logger available
		$log = $this->getLog();
		if ($log != false) {
			$log->log($this->view->message, $priority, $errors->exception);
			$log->log('Request Parameters', $priority, $errors->request->getParams());
		}

		// conditionally display exceptions
		if ($this->getInvokeArg('displayExceptions') == true) {
			$this->view->exception = $errors->exception;
		}

		// write exceptions in log
		Zend_Registry::get('Zend_Log')->err("Exception -> Message: {$errors->exception->getMessage()}");
		Zend_Registry::get('Zend_Log')->err("Exception -> Trace: {$errors->exception->getTraceAsString()}");
		Zend_Registry::get('Zend_Log')->err("Exception -> Params: " . print_r($errors->request->getParams(), true));

		// bla bla bla
		$this->view->request = $errors->request;
	}

	public function getLog()
	{
		$bootstrap = $this->getInvokeArg('bootstrap');
		if (!$bootstrap->hasResource('Log'))
			return false;

		return $bootstrap->getResource('Log');
	}
}
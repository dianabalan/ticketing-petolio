<?php

class Zend_View_Helper_LoginForm extends Zend_View_Helper_Abstract {

	public function loginForm(Petolio_Form_Login $form) {
        return $form->render();
    }

    public function logoutHtml() {

    }
}
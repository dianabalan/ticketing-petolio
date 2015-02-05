<?php

class Petolio_Decorator_PoFile extends Zend_Form_Decorator_Abstract {

    protected $_format = '<label for="%s">%s</label><input id="%s" name="%s" type="text" value="%s"/>';

    public function render($content)
    {
        $element = $this->getElement();
        $name    = htmlentities($element->getFullyQualifiedName(), ENT_QUOTES, 'UTF-8');
        $label   = htmlentities($element->getLabel(), ENT_QUOTES, 'UTF-8');
        $id      = htmlentities($element->getId(), ENT_QUOTES, 'UTF-8');
        $value   = htmlentities($element->getValue(), ENT_QUOTES, 'UTF-8');

        $markup  = sprintf($this->_format, $id, $label, $id, $name, $value);
        return $markup;
    }
}

?>
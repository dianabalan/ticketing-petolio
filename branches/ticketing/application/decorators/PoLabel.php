<?php

class Petolio_Decorator_PoLabel extends Zend_Form_Decorator_Abstract {

    protected $_format = '<label for="%s" class="%s">%s</label>';

    public function render($content) {

    	$element = $this->getElement();
        $id      = htmlentities($element->getId(), ENT_QUOTES, 'UTF-8');
        $label   = $element->getLabel();
        $class	 = $this->getOption('class');

        $markup = sprintf($this->_format, $id, $class, $label);

        $placement = $this->getPlacement();
        $separator = $this->getSeparator();
        switch ($placement) {
            case self::APPEND:
                return $markup . $separator . $content;
            case self::PREPEND:
            default:
                return $content . $separator . $markup;
        }
    }
}

?>
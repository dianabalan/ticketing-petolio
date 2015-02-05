<?php

class Petolio_Decorator_PoWysiwyg extends Zend_Form_Decorator_Abstract {

    protected $_format = '<textarea name="%s" id="%s" class="%s">%s</textarea>';

    public function render($content) {

        $element  = $this->getElement();
		$errors_class = $element->getAttrib('errors_class');
        $name     = htmlentities($element->getFullyQualifiedName(), ENT_QUOTES, 'UTF-8');
        $id       = htmlentities($element->getId(), ENT_QUOTES, 'UTF-8');
        $value    = htmlentities($element->getValue(), ENT_QUOTES, 'UTF-8');
        $messages = $element->getMessages();
        $class	 = $this->getOption('class');

        $markup   = sprintf($this->_format, $name, $id, $class, $value);

        if (!empty($messages)) {
        	$cls = 'errors';
        	if ( isset($errors_class) && strlen($errors_class) > 0 ) {
        		$cls .= ' '.$errors_class;
        	}
        	$markup .= '<ul class="'.$cls.'">';
            foreach ($messages as $key => $value) {
            	$markup .= '<li>'.$value.'</li>';
            }
            $markup .= '</ul>';
        }

        $placement = $this->getPlacement();
        $separator = $this->getSeparator();
        switch ($placement) {
            case self::PREPEND:
                return $markup . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $markup;
        }
    }
}

?>
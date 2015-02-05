<?php

class Petolio_Decorator_PoInput extends Zend_Form_Decorator_Abstract {

    protected $_format = '<input type="%s" name="%s" id="%s" value="%s" rel="#%s" class="%s" %s />';

    public function render($content) {

        $element  	 = $this->getElement();
		$errors_class = $element->getAttrib('errors_class');
		$msg_errors = $element->getAttrib('msg_errors');
        $name     	 = htmlentities($element->getFullyQualifiedName(), ENT_QUOTES, 'UTF-8');
        $label = $element->getLabel();
        $id       	 = htmlentities($element->getId(), ENT_QUOTES, 'UTF-8');
        $value 		 = htmlentities($element->getValue(), ENT_QUOTES, 'UTF-8');
        $description = htmlentities($element->getDescription(), ENT_QUOTES, 'UTF-8');
        $messages 	 = $element->getMessages();
        $class	 	 = $this->getOption('class');

        $tmp	  = explode('_', $element->getType());
        $type	  = strtolower($tmp[count($tmp)-1]);
        $extra	  = '';

        if ( $element instanceof Zend_Form_Element_Checkbox ) {
        	$value = $element->getCheckedValue();
        	if ( $element->isChecked() ) {
        		$extra .= ' checked="checked"';
        	}
        }

        $rel 	  = $id.'-'.$type;

        if ( !empty($messages) && isset($errors_class) && strlen($errors_class) > 0 && strcasecmp($errors_class, 'cluetip_errors') == 0 ) {
        	$class .= ' red-error';
        }
        
        $markup   = sprintf($this->_format, $type, $name, $id, $value, $rel, $class, $extra);

        if (!empty($messages)) {
	        // save error in session if msg_errors is true
	        if($msg_errors)
	        	$_SESSION['msg_errors'][$name] = array($label, $messages);

        	$cls = 'errors';
        	if ( isset($errors_class) && strlen($errors_class) > 0 ) {
        		$cls .= ' '.$errors_class;
        	}
        	$markup .= '<div class="red-dot" style="width: 5px;">*</div><ul class="'.$cls.'" id="'.$rel.'">';
            foreach ($messages as $key => $value) {
            	$markup .= '<li>'.$value.'</li>';
            }
            $markup .= '</ul>';
        }
        if ( isset($description) && strlen($description) > 0 ) {
        	$markup .= '<label class="description">'.$description.'</label>';
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
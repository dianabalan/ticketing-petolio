<?php

class Petolio_Decorator_PoRadio extends Zend_Form_Decorator_Abstract {

    public function render($content) {

        $element  	 = $this->getElement();
		$errors_class = $element->getAttrib('errors_class');
		$msg_errors = $element->getAttrib('msg_errors');
        $label		 = $element->getLabel();
        $name     	 = htmlentities($element->getFullyQualifiedName(), ENT_QUOTES, 'UTF-8');
        $id       	 = htmlentities($element->getId(), ENT_QUOTES, 'UTF-8');
        $value 		 = htmlentities($element->getValue(), ENT_QUOTES, 'UTF-8');
        $description = htmlentities($element->getDescription(), ENT_QUOTES, 'UTF-8');
        $messages 	 = $element->getMessages();

        $options 	 = $element->getMultiOptions();
        //$markup 	 = '<div><label for="'.$name.'">'.$label.'</label>';
        $markup 	 = '<div class="radio_container"><div class="radio-container">';
        $first = true;

        foreach ($options as $key => $option) {
        	$checked = '';
        	$extra = '';
        	if ( strcasecmp($value, $key) == 0 ) {
        		$checked = 'checked="checked"';
        	}
        	if ( (!isset($value) || strlen($value) <= 0 ) && $first ) {
        		$checked = 'checked="checked"';
        	}
        	if ( $first ) {
        		$extra = 'style="width: 60px !important"';
        	}
        	$first = false;
        	$markup .= '';
        	$markup .= '<input type="radio" id="'.$name.'_'.$key.'" value="'.$key.'" name="'.$name.'" '.$checked.' class="radiobtn" />';
        	$markup .= '<label for="'.$name.'_'.$key.'" class="radio-option">'.$option.'</label>';
        	$markup .= '';
        }

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
        $markup .= '</div></div><div class="clear"></div>';

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

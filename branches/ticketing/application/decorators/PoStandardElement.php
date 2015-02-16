<?php

class Petolio_Decorator_PoStandardElement extends Zend_Form_Decorator_Abstract {

	protected $_input_hidden_format = '<input type="%s" name="%s" id="%s" value="%s" rel="#%s" %s/>';
    protected $_input_format = '<div><label for="%s">%s</label><input type="%s" name="%s" id="%s" value="%s" rel="#%s" %s/>';
    protected $_submit_format = '<div><label>%s</label><input type="%s" name="%s" id="%s" value="%s" rel="#%s" %s/>';
    protected $_select_format = '<div><label for="%s">%s</label><select class="chzn-select" name="%s" id="%s" rel="#%s" %s>%s</select>';
    protected $_multiselect_format = '<div><label for="%s">%s</label><select class="chzn-select" multiple="multiple" name="%s" id="%s" rel="#%s" %s>%s</select>';
    protected $_dob_format = '<div><label for="%s">%s</label><div class="dob" name="%s" id="%s" rel="#%s"><select class="dobd chzn-select" name="%s">%s</select><select class="dobm chzn-select" name="%s">%s</select><select class="doby chzn-select" name="%s">%s</select></div>';
    protected $_textarea_format = '<div style="height: auto;"><label for="%s">%s</label><textarea name="%s" id="%s" rel="#%s" %s>%s</textarea>';

	private function html($array)
	{
		return is_array($array) ? array_map(array($this, 'html'), $array) : htmlentities($array, ENT_QUOTES, 'UTF-8');
	}

	private function determineSelected($id, $value) {
		$selected = null;
		if(is_array($value)) {
			if(in_array((string)$id, (array)$value)) $selected = "selected='selected'";
		} else {
			if((string)$value === (string)$id) $selected = "selected='selected'";
		}

		return $selected;
	}

    public function render($content)
    {
    	$translate = Zend_Registry::get('Zend_Translate');

		$element = $this->getElement();
		$attr = $element->getAttrib('html');
		$errors_class = $element->getAttrib('errors_class');
		$msg_errors = $element->getAttrib('msg_errors');
		$name = htmlentities($element->getFullyQualifiedName(), ENT_QUOTES, 'UTF-8');
		$label = $element->getLabel();
		$id = htmlentities($element->getId(), ENT_QUOTES, 'UTF-8');
		$value = $this->html($element->getValue());
		$description = $element->getDescription();
		$messages = $element->getMessages();

		// select start --
		$options = strlen($element->getAttrib('empty')) > 0 ? "<option value=''>{$element->getAttrib('empty')}</option>" : null;
		if(!is_null($element->options))
			if($element->getAttrib('style') == 'tree') {
				foreach($element->options as $idx => $tree) {
					$selected = $this->determineSelected($tree['id'], $value);
					$closed = $idx != 0 ? "</optgroup>" : null;

					if($tree['indent'] == 0) $options .= "{$closed}<optgroup label='{$tree['name']}'>";
					else $options .= "<option value='{$tree['id']}' {$selected}>{$tree['name']}</option>";
				}
				$options .= "</optgroup>";
			} else {
				foreach($element->options as $idx => $line) {
					$selected = $this->determineSelected($idx, $value);
					$options .= "<option value='{$idx}' $selected>{$line}</option>";
				}
			}
		// -- end select

		// dob start --
		if($element->getAttrib('style') == 'date' || $element->getAttrib('style') == 'future_date') {
			if(is_array($value)) {
				$dob_day = $value['day'];
				$dob_month = $value['month'];
				$dob_year = $value['year'];
			} else {
				$dob_day = $value ? date('j', strtotime($value)) : null;
				$dob_month = $value ? date('n', strtotime($value)) : null;
				$dob_year = $value ? date('Y', strtotime($value)) : null;
			}

			$day_opt = "<option value=''>".$translate->_('Day')."</option>";
			for($day = 1; $day <= 31; $day ++) {
				if($dob_day == $day)
					$day_opt .= "<option value='{$day}' selected='selected'>{$day}</option>";
				else
					$day_opt .= "<option value='{$day}'>{$day}</option>";
			}

		    $_months = array (
		    	'1' => $translate->_('January'),
		    	'2' => $translate->_('February'),
			    '3' => $translate->_('March'),
			    '4' => $translate->_('April'),
			    '5' => $translate->_('May'),
			    '6' => $translate->_('June'),
			    '7' => $translate->_('July'),
			    '8' => $translate->_('August'),
			    '9' => $translate->_('September'),
			    '10' => $translate->_('October'),
			    '11' => $translate->_('November'),
			    '12' => $translate->_('December')
		    );
			$month_opt = "<option value=''>".$translate->_('Month')."</option>";
			foreach ($_months as $month => $value) {
				if($dob_month == $month)
					$month_opt .= "<option value='{$month}' selected='selected'>{$value}</option>";
				else
					$month_opt .= "<option value='{$month}'>{$value}</option>";
			}

			$year_opt = "<option value=''>".$translate->_('Year')."</option>";

			// future stuff
			if($element->getAttrib('style') == 'future_date') {
				for($year = date("Y"); $year <= date("Y") + 5; $year++) {
					if($dob_year == $year)
						$year_opt .= "<option value='{$year}' selected='selected'>{$year}</option>";
					else
						$year_opt .= "<option value='{$year}'>{$year}</option>";
				}
			} else {
				for($year = date ( "Y" ); $year >= date ( "Y" ) - 100; $year --) {
					if($dob_year == $year)
						$year_opt .= "<option value='{$year}' selected='selected'>{$year}</option>";
					else
						$year_opt .= "<option value='{$year}'>{$year}</option>";
				}
			}
		}
		// -- end dob

        $tmp = explode('_', $element->getType());
        $type = strtolower($tmp[count($tmp)-1]);
        $rel = "{$id}-{$type}";

        if ( !empty($messages) && isset($errors_class) && strlen($errors_class) > 0 && strcasecmp($errors_class, 'cluetip_errors') == 0 ) {
        	$attr .= ' class="red-error"';
        }

        // contruct the field html
        if($type == 'select')
        	$markup = sprintf($this->_select_format, $id, $label, $name, $id, $rel, $attr, $options);
        elseif($type == 'multiselect')
			$markup = sprintf($this->_multiselect_format,  $id, $label, $name, $id, $rel, $attr, $options);
        elseif($type == 'textarea')
			$markup = sprintf($this->_textarea_format, $id, $label, $name, $id, $rel, $attr, $value);
        else {
        	if($element->getAttrib('style') == 'date' || $element->getAttrib('style') == 'future_date')
        		$markup = sprintf($this->_dob_format, $id, $label, $name, $id, $rel, $name."[day]", $day_opt, $name."[month]", $month_opt, $name."[year]", $year_opt);
        	else {
        		if($name == 'submit')
        			$markup = sprintf($this->_submit_format, $label, $type, $name, $id, $value, $rel, $attr, $description);
        		elseif($type == 'hidden')
        			$markup = sprintf($this->_input_hidden_format, $type, $name, $id, $value, $rel, $attr, $description);
        		else
        			$markup = sprintf($this->_input_format, $id, $label, $type, $name, $id, $value, $rel, $attr, $description);
        	}
        }

        // add error/validation dot
        if (!empty($messages)) {
            $markup .= '<div class="red-dot" style="width: 5px;">*</div>';
        }

        // add description text
        if ( isset($description) && strlen($description) > 0 ) {
        	$markup .= '<label class="description">'.$description.'</label>';
        }

        // add description field
        if ( $element->getAttrib('has_description') && intval($element->getAttrib('has_description')) == 1 ) {
        	$description_value = isset($_REQUEST[$name.'_description']) ? $_REQUEST[$name.'_description'] : '';
        	$markup .= "<input type=\"text\" name=\"{$name}_description\" value=\"{$description_value}\" />";
        }

        // add error/validation messages
        if (!empty($messages)) {
	        // save error in session if msg_errors is true
	        if($msg_errors)
	        	$_SESSION['msg_errors'][$name] = array($label, $messages);

        	$cls = 'errors';
        	if ( isset($errors_class) && strlen($errors_class) > 0 ) {
        		$cls .= ' '.$errors_class;
        	}
            $markup .= '<ul class="'.$cls.'" id="'.$rel.'">';
            foreach ($messages as $key => $value) {
            	$markup .= '<li>'.$value.'</li>';
            }
            $markup .= '</ul>';
        }

        // close div tag
        if ($type != 'hidden') {
        	$markup .= '<div class="cls"></div></div>';
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
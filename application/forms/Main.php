<?php

class Petolio_Form_Main extends Zend_Form {

	/**
     * Populate form
     * first unescape the values
     *
     * Proxies to {@link setDefaults()}
     *
     * @param  array $values
     * @return Zend_Form
     */
    public function populate(array $values)
    {
    	foreach ($values as $key => $value) {
    		if ( is_array($value) ) {
    			if ( isset($value['type']) ) { // it's attribute values
	    			if ( strcasecmp($value['type'], 'text') == 0 ) {
	    				// the text type attribute values wasn't escaped because in this case we have tinymce editor
	    				$values[$key] = $values[$key]['value'];
	    			} else {
	    				$values[$key] = Petolio_Service_Util::unescape($values[$key]['value']);
	    			}
    			} else { // other array values
    				foreach ( $value as $k => $val ) {
    					if ( !is_array($val) ) {
    						$values[$key][$k] = Petolio_Service_Util::unescape($val);
    					}
    				}
    			}
    		} else {
    			$values[$key] = Petolio_Service_Util::unescape($values[$key]);
    		}
        }

        return $this->setDefaults($values);
    }
	

}

?>
<?php

class Petolio_Model_DbTable_PoAttributes extends Zend_Db_Table_Abstract {

    protected $_name = 'po_attributes';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoAttributeOptions',
			'PoAttributeEntityDatetime',
			'PoAttributeEntityDecimal',
			'PoAttributeEntityInt',
			'PoAttributeEntityText',
			'PoAttributeEntityVarchar'
	);

	protected $_referenceMap    = array(
        'PoAttributeSets' => array(
            'columns'           => array('attribute_set_id'),
            'refTableClass'     => 'PoAttributeSets',
            'refColumns'        => array('id')
        ),
        'PoAttributeInputTypes' => array(
            'columns'           => array('attribute_input_type_id'),
            'refTableClass'     => 'PoAttributeInputTypes',
            'refColumns'        => array('id')
        ),
        'PoCurrencies' => array(
            'columns'           => array('currency_id'),
            'refTableClass'     => 'PoCurrencies',
            'refColumns'        => array('id')
        ),
        'PoAttributeGroups' => array(
            'columns'           => array('group_id'),
            'refTableClass'     => 'PoAttributeGroups',
            'refColumns'        => array('id')
        )
	);

	private $output = array();

	/**
	 * fetches all rows optionally filtered by where, order, count and offset
	 *
	 * @param string $where
	 * @param string $order
	 * @param int $count
	 * @param int $offset
	 *
	 */
	public function fetchList($where=null, $order=null, $count=null, $offset=null)
	{
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_attribute_input_types'), 'b.id = a.attribute_input_type_id', array('attr_type' => 'b.type'));

		if(!is_null($where)) $this->_where($select, $where);
		if(!is_null($order)) $this->_order($select, $order);
		if(!is_null($count) || !is_null($offset)) $select->limit($count, $offset);

		$select->group("a.id");
		return $select;
	}

	/**
	 * get all the attributes for a specific attribute set id
	 * if an attribute scope is transmitted then first get the first attribute set id for that scope
	 *
	 * @param int or string $val attribute_set_id or attribute scope
	 */
	public function getAttributes($val) // val can be int for attribute set id or varchar for attribute scope
	{
		if(!$val)
			return null;

		if(!is_numeric($val)) {
			$db = new Petolio_Model_DbTable_PoAttributeSets();
			$select = $db->select()->where($this->getAdapter()->quoteInto('scope = ?', $val));
			$result = reset($this->fetchRow($select));
			$val = $result['id'];
		}

		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(
				array('b' => 'po_attribute_input_types'),
				'a.attribute_input_type_id = b.id',
				array(
					'attribute_type' => 'type',
					'attribute_name' => 'name'
				))
			->joinLeft(
				array('c' => 'po_attribute_groups'),
				'a.group_id = c.id',
				array(
					'group_name' => 'name'
				))
        	->where($this->getAdapter()->quoteInto("a.attribute_set_id = ?", $val, Zend_Db::BIGINT_TYPE))
        	->where("a.active = 1")
        	->order("print_order");

		return $this->fetchAll($select);
	}

	/**
	 * Generate the form based on the Attributes
	 *
	 * @param array $base the return of $this->getAttributes()
	 * @param array $files needed for defaults if we have an attribute of the type file
	 * @return array with form builer variables
	 */
	public function formAttributes($base = array(), $files = array()){
		if(!(count($base) > 0))
			return false;

		$out = array();
		$translate = Zend_Registry::get('Zend_Translate');
		$file_path = '/images/userfiles/attributes/';

		foreach($base as $idx => $field) {
			// show member limit, only after 1st attribute (name)
			if($idx == 1) {
				$attribute_set = new Petolio_Model_PoAttributeSets();
				if ($attribute_set->find($field['attribute_set_id']) && $attribute_set->getType() == 1) {
					// set in array
					$out[] = array(
						'name' => 'text',
						'code' => 'members_limit',
						'attr' => array (
							'label' => $translate->_('Member Limit'),
							'required' => false,
							'validators' => array('Int'),
							'description' => $translate->_('Leave it empty for unlimited members.')
						),
						'tiny' => false
					);
				}
			}

			// defaults
			$array = array(
				'label' => Petolio_Service_Util::Tr($field['label']),
				'required' => $field['is_required'] == 1 ? true : false,
				'description' => Petolio_Service_Util::Tr($field['description']),
        		'attribs' => array('has_description' => $field['has_description'])
			);

			// ajax
			if($field['attribute_name'] == 'text' && $field['attribute_type'] == 'select') {
				$defaultText = Petolio_Service_Util::title_case($translate->_("Select")." ".Petolio_Service_Util::Tr($field['label']));
				$array['attribs']['html'] = "class='chzn-select chzn-custom' attribute_id='{$field['id']}' title='{$defaultText}'";
			}

			// yesno
			if($field['attribute_name'] == 'select' && $field['attribute_type'] == 'yesno') {
				$options = array(
					'1' => Petolio_Service_Util::Tr('Yes'),
					'0' => Petolio_Service_Util::Tr('No')
				);

				$defaultText = $translate->_("Select")." ".Petolio_Service_Util::Tr($field['label']);
				$array['attribs']['empty'] = $defaultText;
				$array['multiOptions'] = $options;

				// put onchange confirm for the _sale attribute/field
				if(substr($field['code'], strrpos($field['code'], '_sale'), 5) == '_sale')
					$array['html'] = "onchange=\"Petolio.confirmSale('".$translate->_('This will automatically put your pet to the adoption list')."', this);\"";
			}

			// dropdown
			if($field['attribute_name'] == 'select' && $field['attribute_type'] == 'select') {
				$db = new Petolio_Model_DbTable_PoAttributeOptions();
				$select = $db->select()->where("attribute_id = ?", $field['id']);

				$options = array();
				foreach($db->fetchAll($select) as $option)
					$options[$option['id']] = Petolio_Service_Util::Tr($option['value']);
				asort($options);

				$defaultText = Petolio_Service_Util::title_case(strtolower($translate->_("Select")." ".Petolio_Service_Util::Tr($field['label'])));
				$array['attribs']['empty'] = $defaultText;
				$array['multiOptions'] = $options;
			}

			// datetime
			if($field['attribute_type'] == 'datetime') {
				$field['attribute_name'] = 'text';
				$array['attribs']['style'] = 'date';
				$array['validators'] = array('Date');
			}

			// integer
			if($field['attribute_type'] == 'int')
				$array['validators'] = array('Int');

			// decimal
			if($field['attribute_type'] == 'decimal')
				$array['validators'] = array('Float');

			// varchar
			if($field['attribute_type'] == 'varchar') {
				$array['validators'] = array(
					array('StringLength', false, array('max'=>200))
				);
			}

			// text
			$tiny = false;
			if($field['attribute_type'] == 'text') {
				$tiny = true;
				$array['attribs']['html'] = 'class="tinymce"';
			}

			// file
			if($field['attribute_name'] == 'file') {
				$ds = DIRECTORY_SEPARATOR;
				$config = Zend_Registry::get('config');

				$array['attribs']['description'] = $field['description'];
				$array['attribs']['size'] = '50';
				$array['attribs']['style'] = 'width: 400px;';

				$array['destination'] = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}attributes{$ds}";
				$array['validators'] = array(
					array('IsImage', false),
					array('Size', false, $config['max_filesize'])
				);

				if (isset($files[$field['code']])) {
					$filename = sha1(md5($files[$field['code']][0]) . 'unimatrix') . '.' . pathinfo($files[$field['code']][1], PATHINFO_EXTENSION);
					$delete = 'http://'.$_SERVER['SERVER_NAME'] . '/site/delete/attribute/' . sha1(md5($files[$field['code']][0] . $files[$field['code']][1]) . 'unimatrix');
					$array['description'] = "<a href='{$file_path}{$filename}'>{$files[$field['code']][1]}</a>" .
						"<a style='margin-left: 5px;' href='{$delete}'>" .
							"<img style='vertical-align: middle;' src='/images/icons/delete.png' /></a>";

					$array['required'] = false;
				} else
					$array['description'] = null;

				$array['decorators'] = array(
					'File',
					array('ViewScript',
						array('viewScript' => 'file.phtml', 'placement' => false)
					)
				);
			}

			// set in array
			$out[] = array(
				'name' => $field['attribute_name'],
				'grup' => $field['group_name'],
				'code' => $field['code'],
				'attr' => $array,
				'tiny' => $tiny
			);
		}

		// return builders
		return $out;
	}

	/**
	 * Save attribute values
	 *   - Will first delete existing attributes
	 *
	 * @param array $data list of attributes, key is code
	 * @param id $entityId assign these values to a entity (could be id of po_pets, po_services etc)
	 */
	public function saveAttributeValues($data, $entityId)
	{
		if(!is_array($data))
			return null;

		foreach($data as $idx => $line) {
			$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => $this->_name))
				->joinLeft(array('b' => 'po_attribute_input_types'), 'a.attribute_input_type_id = b.id', array('attribute_type' => 'type', 'attribute_name' => 'name'))
	        	->where($this->getAdapter()->quoteInto("a.code = ?", $idx))
	        	->where("a.active = 1")
				->order("print_order");
			$self = current(reset($this->fetchAll($select)));

			if(is_array($line) && $self['attribute_type'] == 'datetime') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) {
					$line = null;
				} else {
					$line = "{$line['year']}-{$line['month']}-{$line['day']}";
				}
			} elseif($self['attribute_type'] == 'select' || $self['attribute_type'] == 'yesno') {
				$self['attribute_type'] = 'int';
				if(is_array($line)) {
					if(!(count($line) > 0))
						$line = null;
				} else {
					if(!(strlen($line) > 0))
						$line = null;
				}
			} elseif($self['attribute_type'] == 'int') {
				if(!(strlen($line) > 0))
					$line = null;
			} else {
				if(!(strlen($line) > 0))
					$line = null;
			}

			// if our attribute is a file but the value is null, just abort this nonsence
			if($self['attribute_name'] == 'file' && is_null($line))
				continue;

			// set the null zend style
			if (is_null($line))
				$line = new Zend_Db_Expr('NULL');

	        // delete existing attribute values
	        $this->deleteAttributeValue($self, $entityId);

	        // insert new attribute values
	        if(is_array($line)) {
	        	foreach($line as $one) {
					// load class
			        $model_class = "Petolio_Model_PoAttributeEntity" . ucfirst($self['attribute_type']);
			        $entity = new $model_class();

					// save entity
		        	$entity->setAttributeId($self['id']);
		        	$entity->setEntityId($entityId);
					$entity->setValue($one);
					$entity->setDescription(isset($_REQUEST[$idx.'_description']) ? $_REQUEST[$idx.'_description'] : new Zend_Db_Expr('NULL'));
					$entity->save(false);
				}
	        } else {
				// load class
		        $model_class = "Petolio_Model_PoAttributeEntity" . ucfirst($self['attribute_type']);
		        $entity = new $model_class();

				// save entity
	        	$entity->setAttributeId($self['id']);
	        	$entity->setEntityId($entityId);
				$entity->setValue($line);
				$entity->setDescription(isset($_REQUEST[$idx.'_description']) ? $_REQUEST[$idx.'_description'] : new Zend_Db_Expr('NULL'));
				$entity->save(false);
			}

			// if attribute is file, rename the file to attribute id
			if($self['attribute_name'] == 'file') {
				$ds = DIRECTORY_SEPARATOR;
				$upload_dir = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}attributes{$ds}";
				$filename = sha1(md5($entity->getId()) . 'unimatrix') . '.' . pathinfo($line, PATHINFO_EXTENSION);
				@rename($upload_dir . $line, $upload_dir . $filename);

				// assuming file description is instruction to resize
				if($self['description']) {
					// resize original picture if bigger
					$props = @getimagesize($upload_dir . $filename);
					list($w, $h) = explode('x', $self['description']);
					if($props[0] > $w || $props[1] > $h) {
						Petolio_Service_Image::output($upload_dir . $filename, $upload_dir . $filename, array(
							'type'   => IMAGETYPE_JPEG,
							'width'   => $w,
							'height'  => $h,
							'method'  => THUMBNAIL_METHOD_SCALE_MIN
						));
					}
				}
			}
		}
	}

	/**
	 * See if values exist for an attribute
	 *
	 * @param $id the attribute id
	 * @return $output bool found
	 */
	public function attributeValuesExist($id) {
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_attribute_input_types'), 'a.attribute_input_type_id = b.id', array('attribute_type' => 'type', 'attribute_name' => 'name'))
        	->where($this->getAdapter()->quoteInto("a.id = ?", $id, Zend_Db::BIGINT_TYPE));

		// set default
		$found = false;
		$attr = reset($this->fetchAll($select)->toArray());

		// have we even found that attribute?
		if($attr) {
			if($attr['attribute_type'] == 'select' || $attr['attribute_type'] == 'yesno')
				$attr['attribute_type'] = 'int';

			// load class
	        $model_class = "Petolio_Model_PoAttributeEntity" . ucfirst($attr['attribute_type']);
	        $entities = new $model_class();
	        foreach($entities->fetchList("attribute_id = {$attr['id']}") as $entity) {
	        	if(strlen($entity->getValue()) > 0) {
	        		$found = true;
	        		break;
	        	}
			}
		}

		// return found and array of attribute ids (in case found is false)
		return $found;
	}

	/**
	 * See if values exist for attributes belonging to a set id
	 *
	 * @param $id the attribute set id
	 * @return $output array of found and attribute ids
	 */
	public function attributeValuesBySetExist($id) {
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name))
			->joinLeft(array('b' => 'po_attribute_input_types'), 'a.attribute_input_type_id = b.id', array('attribute_type' => 'type', 'attribute_name' => 'name'))
        	->where($this->getAdapter()->quoteInto("a.attribute_set_id = ?", $id, Zend_Db::BIGINT_TYPE))
			->order("print_order");

		// get all attributes
		$found = false;
		$attributes = array();
		foreach($this->fetchAll($select)->toArray() as $attr) {
			// if type is select or yes no then its int basically
			if($attr['attribute_type'] == 'select' || $attr['attribute_type'] == 'yesno')
				$attr['attribute_type'] = 'int';

			// load class
	        $model_class = "Petolio_Model_PoAttributeEntity" . ucfirst($attr['attribute_type']);
	        $entities = new $model_class();
	        foreach($entities->fetchList("attribute_id = {$attr['id']}") as $entity) {
	        	if(strlen($entity->getValue()) > 0) {
	        		$found = true;
	        		break;
	        	}
			}

			// attach attributes
			$attributes[] = $attr['id'];
		}

		// return found and array of attribute ids (in case found is false)
		return array($found, $attributes);
	}

	/**
	 * Get max print + 1 based on attribute set id
	 *
	 * @param $id the attribute set id
	 * @return $output int max print
	 */
	public function maxPrint($id) {
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name), array('max' => 'MAX(a.print_order)'))
			->where($this->getAdapter()->quoteInto("a.attribute_set_id = ?", $id, Zend_Db::BIGINT_TYPE))
			->limit(1);

		$val = reset($this->fetchAll($select)->toArray());
		return is_null($val['max']) ? 1 : $val['max'] + 1;
	}

	/**
	 * Loads attributes with values for one or more entity
	 *
	 * @param $obj one object or an array of objects of type PoServices, PoPets, PoPedigree or
	 * 		       any other type of object where we have an attribute_set_id field
	 * @param $format true or false: format fields or not
	 * @param $alternativeId int, to replace $entity->getId(); for when we have another table between id and attribute_set_id
	 * @param $translate boolean, to translate or not the attribute values; only if they are option values
	 *
	 * @return $output array of PoAttributes objects
	 */
	public function loadAttributeValues($obj, $format = false, $alternativeId = null, $translate = true) {
		$optMap = new Petolio_Model_PoAttributeOptionsMapper();
		if (!is_array($obj))
			$obj = array($obj);

		foreach($obj as $entity) {
			$the_id = is_null($alternativeId) ? $entity->getId() : $alternativeId;
			if(isset($this->output[($format ? $the_id.'_formatted' : $the_id.'_unformatted')]))
				continue;

			$select = $this->select()->setIntegrityCheck(false)
				->from(array('a' => $this->_name))
				->joinLeft(
					array('b' => 'po_attribute_input_types'),
					'a.attribute_input_type_id = b.id',
					array(
						'attribute_input_type_type' => 'type',
						'attribute_input_type_name' => 'name',
						'attribute_input_type_description' => 'description'
					))
				->joinLeft(
					array('c' => 'po_currencies'),
					'a.currency_id = c.id',
					array(
						'currency_code' => 'code',
						'currency_name' => 'name'
					))
				->joinLeft(
					array('d' => 'po_attribute_groups'),
					'a.group_id = d.id',
					array(
						'group_name' => 'name'
					))
				->where($this->getAdapter()->quoteInto("a.attribute_set_id = ?", $entity->getAttributeSetId(), Zend_Db::BIGINT_TYPE))
	        	->where("a.active = 1")
				->order("print_order");

			foreach($this->fetchAll($select) as $line) {
				$attribute = new Petolio_Model_PoAttributes();
				$attribute->setId($line['id'])
					->setAttributeSetId($line['attribute_set_id'])
					->setCode($line['code'])
					->setAttributeInputTypeId($line['attribute_input_type_id'])
					->setIsUnique($line['is_unique'])
					->setIsRequired($line['is_required'])
					->setActive($line['active'])
					->setPrintOrder($line['print_order'])
					->setLabel($line['label'])
					->setDescription($line['description'])
					->setCurrencyId($line['currency_id'])
					->setGroupId($line['group_id'])
					->setHasDescription($line['has_description']);

				if ( isset($line['group_id']) && strlen($line['group_id']) > 0 ) {
					$group = new Petolio_Model_PoAttributeGroups();
					$group->setId($line['group_id'])
						  ->setName($line['group_name']);
					$attribute->setGroup($group);
				}

				if ( isset($line['currency_id']) && strlen($line['currency_id']) > 0 ) {
					$currency = new Petolio_Model_PoCurrencies();
					$currency->setId($line['currency_id'])
							 ->setName($line['currency_name'])
							 ->setCode($line['currency_code']);
					$attribute->setCurrency($currency);
				}

				// we don't need the attributeset object at this time
				// if you need it the future then you can uncomment this line or the getAttributeSet() function will return you a new one anyway
				// $attribute->setAttributeSet();

				// attribute input type obj
				$attribute_input_type = new Petolio_Model_PoAttributeInputTypes();
				$attribute_input_type->setId($line['attribute_input_type_id'])
					->setName($line['attribute_input_type_name'])
					->setType($line['attribute_input_type_type'])
					->setDescription($line['attribute_input_type_description']);
				$attribute->setAttributeInputType($attribute_input_type);

				// if select or yesno then its int basically
				if ($line['attribute_input_type_type'] == 'select' || $line['attribute_input_type_type'] == 'yesno')
					$line['attribute_input_type_type'] = 'int';

				// get attribute entity object
				$entity_class = "Petolio_Model_PoAttributeEntity" . ucfirst($line['attribute_input_type_type']);
		        $attribute_entity = new $entity_class();
		        if ( $attribute->getGroupId() && intval($attribute->getGroupId()) > 0 ) {
		        	// if we have a group then we load an array of entity objects
		        	$group_entities = new Petolio_Model_PoAttributeGroupEntities();
		        	$entity_ids = '';
		        	foreach ($group_entities->fetchList("group_id = '{$attribute->getGroupId()}' AND entity_id = ".$this->getAdapter()->quote($the_id, Zend_Db::BIGINT_TYPE)) as $group_entity) {
		        		if ( strlen($entity_ids) > 0 ) {
		        			$entity_ids .= ', ';
		        		}
		        		$entity_ids .= $group_entity->getId();
		        	}
		        	if ( strlen($entity_ids) > 0 ) {
						$results = $attribute_entity->fetchList("attribute_id = ".$this->getAdapter()->quote($line['id'], Zend_Db::BIGINT_TYPE)." AND entity_id IN ({$entity_ids})");

						// set attribute entity object
				        $attribute_entity = is_array($results) && count($results) > 0 ? $results : null;
						$attribute->setAttributeEntity($attribute_entity);
		        	}
		        } else {
					$results = $attribute_entity->fetchList("attribute_id = '{$line['id']}' AND entity_id = ".$this->getAdapter()->quote($the_id, Zend_Db::BIGINT_TYPE), 'id ASC');

					// set attribute entity object
			        $attribute_entity = (is_array($results) && count($results) > 0 ? ($line['attribute_input_type_id'] == 10 ? $results : reset($results)) : null);
					$attribute->setAttributeEntity($attribute_entity);
		        }

				// output accordingly
				$key = str_replace(substr($attribute->getCode(), 0, strpos($attribute->getCode(), '_')) . '_', '', $attribute->getCode());
				$this->output[($format ? $the_id.'_formatted' : $the_id.'_unformatted')][$key] = $this->formatAttribute($attribute, $optMap, $format, $translate);
			}
		}

		return $this->output;
	}

	/**
	 * loadAttributeValues loaded raw values (for form edit)
	 * this function will format the values to properly display them (for view pages)
	 *
	 * @param $obj one object or an array of objects of type PoServices, PoPets, PoPedigree or
	 * 			any other type of object where we have an attribute_set_id field
	 * @param $translate boolean, to translate or not the values; onlky if they are option values
	 * @return $output array of PoAttributes formatted objects
	 */
	public function formatAttribute($attr, $optMap, $format = false, $translate = true)
	{
		$atype = $attr->getAttributeInputType()->getType();
		$attribute_entity = $attr->getAttributeEntity();
		if (!is_array($attribute_entity))
			$attribute_entity = array($attribute_entity);

		foreach ($attribute_entity as $value) {
			if (isset($value) && strlen($value->getValue()) > 0) {
				if ($atype == 'decimal')
					$value->setValue(round($value->getValue(), 2));

				if($format) {
					if ($attr->getCurrencyId() && intval($attr->getCurrencyId()) > 0 && ($atype == 'decimal' || $atype == 'int'))
						$value->setValue(Petolio_Service_Util::formatCurrency($value->getValue(), $attr->getCurrency()->getCode()));

					if($atype == 'datetime')
						$value->setValue(Petolio_Service_Util::formatDate($value->getValue(), null, false));

					if($atype == 'yesno') {
						$val = 'No';
						if($value->getValue() == 1) $val = 'Yes';

						if ($translate)
							$value->setValue(Petolio_Service_Util::Tr($val));
						else
							$value->setValue($val);
					}

					if($atype == 'select') {
						$result = $optMap->findByField('id', $value->getValue(), new Petolio_Model_PoAttributeOptions());
						$optVal = is_array($result) && count($result) > 0 ? reset($result) : null;

						if(!is_null($optVal) && $translate) {
				        	// translate entity value
							$value->setValue(Petolio_Service_Util::Tr($optVal->getValue()));
							$value->setLatin(Petolio_Service_Util::Latin($optVal->getValue()));
						} elseif (!is_null($optVal))
							$value->setValue($optVal->getValue());
					}
				}
			}
		}

		return $attr;
	}

	/**
	 * Deletes an attribute value
	 * @param array $attribute
	 * @param int $entity_id - Entity to which its bound
	 */
	public function deleteAttributeValue($attribute, $entity_id) {
		if($attribute['attribute_type'] == 'select' || $attribute['attribute_type'] == 'yesno')
			$attribute['attribute_type'] = 'int';

		$model_class = "Petolio_Model_PoAttributeEntity" . ucfirst($attribute['attribute_type']);
        $entity = new $model_class();

        $results = $entity->fetchList("attribute_id = '{$attribute['id']}' AND entity_id IN ({$entity_id})");
		$result = reset($results);

		if(!$result)
			return false;

        if($attribute['attribute_name'] == 'file' && $result->getValue()) {
			$ds = DIRECTORY_SEPARATOR;
			$upload_dir = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}attributes{$ds}";
			$filename = sha1(md5($result->getId()) . 'unimatrix') . '.' . pathinfo($result->getValue(), PATHINFO_EXTENSION);
        	@unlink($upload_dir . $filename);
        }

		foreach($results as $result)
        	$result->deleteRowByPrimaryKey();
	}

	/**
	 * Delete all attribute values
	 * @param int $entityId - Entity to which its bound
	 * @param int $attribute_set_id - The attribute set id, to identify all attributes
	 */
	public function deleteAttributeValues($entityId, $attribute_set_id) {
		foreach($this->getAttributes($attribute_set_id) as $attribute)
			$this->deleteAttributeValue(current($attribute), $entityId);
	}
}
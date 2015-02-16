<?php

class AttributesController extends Zend_Controller_Action
{
	private $translate = null;
	private $msg = null;
	private $req = null;

	private $db = null;

    public function init()
    {
    	// needed objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_admin_messages");
		$this->req = $this->getRequest();

		// database objects
		$this->db = new stdClass();

		// load models
		$this->db->sets = new Petolio_Model_PoAttributeSets();
		$this->db->attrs = new Petolio_Model_PoAttributes();
		$this->db->attrTypes = new Petolio_Model_PoAttributeInputTypes();
		$this->db->opts = new Petolio_Model_PoAttributeOptions();
		$this->db->ints = new Petolio_Model_PoAttributeEntityInt();
		$this->db->translations = new Petolio_Model_PoTranslations();
    }

    public function indexAction() {
        // action body
    }

    /**
     * Add attribute set
     */
    public function addSetAction() {
    	// based on URL
    	$scope = $this->req->getParam("scope", '');
    	$group = $this->req->getParam("group", '');

    	// send form
    	$form = new Petolio_Form_AttributeSet($scope);
    	$this->view->form = $form;

    	// make data
    	$in = array();

    	// scope
    	if(strlen($scope) > 0)
    		$in['scope'] = $scope;

    	// group
    	if(strlen($group) > 0) {
    		$in['group_name'] = $group;

    		// get de group name
    		$group_de = reset($this->db->translations->fetchList("label = '{$group}' AND language = 'de'"));
    		$in['group_name_de'] = $group_de ? $group_de->getValue() : '';
    	}

    	// set data
    	$form->populate($in);

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

		// save set
		$this->db->sets->setOptions($data)->save(false, true);

		// save translations
		$this->_saveTranslation($data['name'], $data['name_de']);
		$this->_saveTranslation($data['group_name'], $data['group_name_de']);

		// insert attributes automatically based on scope
		$this->_autoAttributes($data['scope'], $this->db->sets->getId(), $data['name']);

		// make data
		$in = array();

		// scope
		if(strlen($scope) > 0)
			$in['scope'] = $scope;

		// group
		if(strlen($group) > 0)
			$in['group'] = $group;

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The attribute set has been added successfully.");
		return $this->_redirect('/admin/attributes/list-sets/' . str_replace(array('=', '&'), '/', http_build_query($in)));
    }

    /**
     * Edit attribute set
     */
    public function editSetAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$set = $this->db->sets->find($id);
    	if(!$set->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute Set does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// send form
    	$form = new Petolio_Form_AttributeSet($set->getScope(), true);
    	$this->view->form = $form;

    	// get translations
    	$name_de = reset($this->db->translations->fetchList("label = '{$set->getName()}' AND language = 'de'"));
    	$group_de = reset($this->db->translations->fetchList("label = '{$set->getGroupName()}' AND language = 'de'"));

    	// set data
    	$form->populate(array_merge(array(
    		'name_de' => $name_de ? $name_de->getValue() : '',
    		'group_name_de' => $group_de ? $group_de->getValue() : ''
    	), $set->toArray()));

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// if name changes we must update all attribute codes
    	$codea = $this->_code($set->getName());
    	$codeb = $this->_code($data['name']);
   		if($codea != $codeb)
   			foreach($this->db->attrs->fetchList("attribute_set_id = {$set->getId()}") as $attr)
				$attr->setCode(str_replace($codea, $codeb, $attr->getCode()))->save(false, true);

    	// save set
    	$set->setOptions($data)->save(false, true);

    	// save translations
    	$this->_saveTranslation($data['name'], $data['name_de']);
    	$this->_saveTranslation($data['group_name'], $data['group_name_de']);

    	// make data
    	$in = array();

    	// scope
    	if(strlen($set->getScope()) > 0)
    		$in['scope'] = $data['scope'];

    	// group
    	if(strlen($set->getGroupName()) > 0)
    		$in['group'] = $data['group_name'];

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("The attribute set has been saved successfully.");
    	return $this->_redirect('/admin/attributes/list-sets/' . str_replace(array('=', '&'), '/', http_build_query($in)));
    }

    /**
     * Delete attribute set
     */
    public function deleteSetAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$set = $this->db->sets->find($id);
    	if(!$set->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute Set does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// see if we have values in the attributes
    	list($found, $attrs) = $this->db->attrs->getMapper()->getDbTable()->attributeValuesBySetExist($set->getId());

    	// make data
    	$in = array();

    	// scope
    	if(strlen($set->getScope()) > 0)
    		$in['scope'] = $set->getScope();

    	// group
    	if(strlen($set->getGroupName()) > 0)
    		$in['group'] = $set->getGroupName();

    	// cannot delete, found values in attributes
    	if($found) {
    		// deactivate
    		$set->setActive(0)->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("The attribute set cannot be deleted and it was deactivated instead.");
    	} else {
    		// delete set
			$set->deleteRowByPrimaryKey();

    		// delete attributes
    		foreach($attrs as $attr) {
    			$attr = $this->db->attrs->find($attr);
    			$attr->deleteRowByPrimaryKey();

    			// delete options if attribute type is select
    			if($this->_typeSelect($attr->getAttributeInputTypeId()))
    				foreach($this->db->opts->fetchList("attribute_id = {$attr->getId()}") as $opt)
    					$opt->deleteRowByPrimaryKey();
    		}

			// msg
			$this->msg->messages[] = $this->translate->_("The attribute set has been deleted successfully.");
    	}

    	// redirect
    	return $this->_redirect('/admin/attributes/list-sets/' . str_replace(array('=', '&'), '/', http_build_query($in)));
    }

    /**
     * List attribute sets
     */
    public function listSetsAction() {
    	// based on URL
    	$name = $this->req->getParam("name", '');
    	$scope = $this->req->getParam("scope", '');
    	$group = $this->req->getParam("group", '');
    	$active = $this->req->getParam("active", '');

    	// output filters
    	$this->view->name = $name;
    	$this->view->scope = $scope;
    	$this->view->group = $group;
    	$this->view->active = $active;

    	// output sorting
    	$this->view->order = $this->req->getParam('order', 'a.scope');
    	$this->view->dir = $this->req->getParam('dir', 'asc');

	   	// handle filter
    	$where = array();

    	// name
    	if(strlen($name) > 0)
    		$where[] = "a.name LIKE '%".strtolower($name)."%'";

    	// scope
    	if(strlen($scope) > 0)
    		$where[] = "a.scope LIKE '%".strtolower($scope)."%'";

    	// group_name
    	if(strlen($group) > 0)
    		$where[] = "a.group_name LIKE '%".strtolower($group)."%'";

    	// active
    	if(strlen($active) > 0)
    		$where[] = "a.active = ".(int)$active;

    	// get sets
    	$paginator = $this->db->sets->fetchListToPaginator(count($where) > 0 ? implode(" AND ", $where) : null, "{$this->view->order} {$this->view->dir}");
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

    	// output sets
    	$this->view->sets = $paginator;
    }

    /**
     * Add attribute to set
     */
    public function addAttributeAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$set = $this->db->sets->find($id);
    	if(!$set->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute Set does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// output params
    	$this->view->set = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($set->getName()));

    	// send form
    	$form = new Petolio_Form_Attribute();
    	$this->view->form = $form;

    	// set data
    	$form->populate(array(
    		'print_order' => $this->db->attrs->getMapper()->getDbTable()->maxPrint($set->getId())
    	));

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

       	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// set attribute set id and transform code
    	$data['attribute_set_id'] = $set->getId();
    	$data['code'] = $this->_code($set->getName()) . '_' . $this->_code($data['label']) . '_' . uniqid();

		// save attribute
		$this->db->attrs->setOptions($data)->save(false, true);

		// save translations
		$this->_saveTranslation($data['label'], $data['label_de']);

		// see if we have next step option edit
		$next = $this->_typeSelect($data['attribute_input_type_id']) ? "list-options/id/{$this->db->attrs->getId()}" : "list-attributes/id/{$set->getId()}";

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The attribute has been added successfully.");
		return $this->_redirect("/admin/attributes/{$next}");
    }

    /**
     * Edit attribute from set
     */
    public function editAttributeAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$attr = $this->db->attrs->find($id);
    	if(!$attr->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// found with values?
    	$found = $this->db->attrs->getMapper()->getDbTable()->attributeValuesExist($attr->getId());

    	// send form
    	$form = new Petolio_Form_Attribute($found);
    	$this->view->form = $form;

    	// get translations
    	$label_de = reset($this->db->translations->fetchList("label = '{$attr->getLabel()}' AND language = 'de'"));

    	// set data
    	$form->populate(array_merge(array(
    		'label_de' => $label_de ? $label_de->getValue() : ''
    	), $attr->toArray()));

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// transform code and set input type if disabled
    	$data['code'] = str_replace($this->_code($attr->getLabel()), $this->_code($data['label']), $attr->getCode());
    	if($found) $data['attribute_input_type_id'] = $attr->getAttributeInputTypeId();

    	// save attribute
    	$attr->setOptions($data)->save(false, true);

    	// save translations
    	$this->_saveTranslation($data['label'], $data['label_de']);

    	// is it select?
    	$select = $this->_typeSelect($data['attribute_input_type_id']);

    	// delete all options if type is not select
    	if(!$select)
    		foreach($this->db->opts->fetchList("attribute_id = {$attr->getId()}") as $opt)
    			$opt->deleteRowByPrimaryKey();

    	// see if we have next step option edit
    	$next = $select ? "list-options/id/{$attr->getId()}" : "list-attributes/id/{$attr->getAttributeSetId()}";

		// msg and redirect
		$this->msg->messages[] = $this->translate->_("The attribute has been saved successfully.");
		return $this->_redirect("/admin/attributes/{$next}");
    }

    /**
     * Delete attribute from set
     */
    public function deleteAttributeAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$attr = $this->db->attrs->find($id);
    	if(!$attr->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// found values or not
    	$found = $this->db->attrs->getMapper()->getDbTable()->attributeValuesExist($attr->getId());

    	// cannot delete, found values in attributes
    	if($found) {
    		// deactivate
    		$attr->setActive(0)->save();

    		// msg
    		$this->msg->messages[] = $this->translate->_("The attribute cannot be deleted and it was deactivated instead.");
    	} else {
    		// delete set
    		$attr->deleteRowByPrimaryKey();

    		// delete all options if type select
    		if($this->_typeSelect($attr->getAttributeInputTypeId()))
    			foreach($this->db->opts->fetchList("attribute_id = {$attr->getId()}") as $opt)
    				$opt->deleteRowByPrimaryKey();

    		// msg
    		$this->msg->messages[] = $this->translate->_("The attribute has been deleted successfully.");
    	}

    	// redirect
    	return $this->_redirect("/admin/attributes/list-attributes/id/{$attr->getAttributeSetId()}");
    }

    /**
     * List attributes from set
     */
    public function listAttributesAction() {
		// based on url
		$id = $this->req->getParam("id", 0);
		$set = $this->db->sets->find($id);
		if(!$set->getId()) {
			$this->msg->messages[] = $this->translate->_("Attribute Set does not exist.");
			return $this->_redirect('admin/attributes/list-sets');
		}

		// based on URL
		$label = $this->req->getParam("label", '');
		$code = $this->req->getParam("code", '');
		$type = $this->req->getParam("type", '');
		$active = $this->req->getParam("active", '');
		$unique = $this->req->getParam("unique", '');
		$required = $this->req->getParam("required", '');

		// output filters
		$this->view->label = $label;
		$this->view->code = $code;
		$this->view->type = $type;
		$this->view->active = $active;
		$this->view->unique = $unique;
		$this->view->required = $required;

		// output params
		$this->view->id = $id;
		$this->view->set = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($set->getName()));

		// output sorting
		$this->view->order = $this->req->getParam('order', 'a.print_order');
		$this->view->dir = $this->req->getParam('dir', 'asc');

		// attribute types resource
		$this->view->types = array();
		foreach($this->db->attrTypes->fetchAll() as $typs)
			$this->view->types[$typs->getId()] = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($typs->getDescription()));

		// handle filter
		$where = array("a.attribute_set_id = ".(int)$id);

		// label
		if(strlen($label) > 0)
			$where[] = "a.label LIKE '%".strtolower($label)."%'";

		// code
		if(strlen($code) > 0)
			$where[] = "a.code LIKE '%".strtolower($code)."%'";

		// type
		if(strlen($type) > 0)
			$where[] = "a.attribute_input_type_id = ".(int)$type;

		// active
		if(strlen($active) > 0)
			$where[] = "a.active = ".(int)$active;

		// unique
		if(strlen($unique) > 0)
			$where[] = "a.is_unique = ".(int)$unique;

		// required
		if(strlen($required) > 0)
			$where[] = "a.is_required = ".(int)$required;

		// get attrs
		$paginator = $this->db->attrs->fetchListToPaginator(implode(" AND ", $where), "{$this->view->order} {$this->view->dir}");
		$paginator->setItemCountPerPage(25);
		$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output attrs
		$this->view->attrs = $paginator;
    }

    /**
     * Add option
     */
    public function addOptionAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$attr = $this->db->attrs->find($id);
    	if(!$attr->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// not type select?
    	if(!$this->_typeSelect($attr->getAttributeInputTypeId())) {
    		$this->msg->messages[] = $this->translate->_("Attribute type is incorrect.");
    		return $this->_redirect("admin/attributes/list-attributes/id/{$attr->getAttributeSetId()}");
    	}

    	// output attr
    	$this->view->attr = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($attr->getLabel()));

    	// output set
    	$set = $this->db->sets->find($attr->getAttributeSetId());
    	$this->view->set = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($set->getName()));
    	$this->view->set_id = $attr->getAttributeSetId();

    	// send form
    	$form = new Petolio_Form_AttributeOption();
    	$this->view->form = $form;

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// add attribute id
    	$data['attribute_id'] = $attr->getId();

    	// save option
    	$this->db->opts->setOptions($data)->save(false, true);

    	// save translations
    	$this->_saveTranslation($data['value'], $data['value_de']);

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("The option has been added successfully.");
    	return $this->_redirect("/admin/attributes/list-options/id/{$attr->getId()}");
    }

    /**
     * Edit option
     */
    public function editOptionAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$opt = $this->db->opts->find($id);
    	if(!$opt->getId()) {
    		$this->msg->messages[] = $this->translate->_("Option does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// used? deny edit
    	$found = $this->db->ints->fetchList("attribute_id = '{$opt->getAttributeId()}' AND value = '{$opt->getId()}'");
    	if(count($found) > 0) {
    		$this->msg->messages[] = $this->translate->_("This option cannot be altered because it is in use.");
    		return $this->_redirect("/admin/attributes/list-options/id/{$opt->getAttributeId()}");
    	}

    	// output set
    	$attr = $this->db->attrs->find($opt->getAttributeId());
    	$set = $this->db->sets->find($attr->getAttributeSetId());
    	$this->view->set = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($set->getName()));
    	$this->view->set_id = $attr->getAttributeSetId();

    	// send form
    	$form = new Petolio_Form_AttributeOption();
    	$this->view->form = $form;

    	// get translations
    	$value_de = reset($this->db->translations->fetchList("label = '{$opt->getValue()}' AND language = 'de'"));

    	// set data
    	$form->populate(array_merge(array(
   			'value_de' => $value_de ? $value_de->getValue() : ''
    	), $opt->toArray()));

    	// did we submit form ? if not just return here
    	if(!($this->req->isPost()))
    		return false;

    	// is the form valid ? if not just return here
    	if(!$form->isValid($this->req->getPost()))
    		return false;

    	// get data
    	$data = $form->getValues();

    	// format data
    	foreach($data as $idx => &$line) {
    		if(is_array($line) && $idx == 'date_of_birth') {
    			if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
    			else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
    		} else {
    			if(!(strlen($line) > 0)) $line = NULL;
    		}
    	}

    	// save option
    	$opt->setOptions($data)->save(false, true);

    	// save translations
    	$this->_saveTranslation($data['value'], $data['value_de']);

    	// msg and redirect
    	$this->msg->messages[] = $this->translate->_("The option has been saved successfully.");
    	return $this->_redirect("/admin/attributes/list-options/id/{$opt->getAttributeId()}");
    }

    /**
     * Delete option
     */
    public function deleteOptionAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$opt = $this->db->opts->find($id);
    	if(!$opt->getId()) {
    		$this->msg->messages[] = $this->translate->_("Option does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// used? deny edit
    	$found = $this->db->ints->fetchList("attribute_id = '{$opt->getAttributeId()}' AND value = '{$opt->getId()}'");
    	if(count($found) > 0) {
    		$this->msg->messages[] = $this->translate->_("This option cannot be altered because it is in use.");
    		return $this->_redirect("/admin/attributes/list-options/id/{$opt->getAttributeId()}");
    	}

    	// delete option
    	$opt->deleteRowByPrimaryKey();

    	// msg & redirect
    	$this->msg->messages[] = $this->translate->_("The option has been deleted successfully.");
    	return $this->_redirect("/admin/attributes/list-options/id/{$opt->getAttributeId()}");
    }

    /**
     * List options
     */
    public function listOptionsAction() {
    	// based on url
    	$id = $this->req->getParam("id", 0);
    	$attr = $this->db->attrs->find($id);
    	if(!$attr->getId()) {
    		$this->msg->messages[] = $this->translate->_("Attribute does not exist.");
    		return $this->_redirect('admin/attributes/list-sets');
    	}

    	// not type select?
    	if(!$this->_typeSelect($attr->getAttributeInputTypeId())) {
    		$this->msg->messages[] = $this->translate->_("Attribute type is incorrect.");
    		return $this->_redirect("admin/attributes/list-attributes/id/{$attr->getAttributeSetId()}");
    	}

		// based on URL
		$value = $this->req->getParam("value", '');

		// output filters
		$this->view->value = $value;

		// output params
		$this->view->id = $id;
		$this->view->attr = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($attr->getLabel()));

		// output set
		$set = $this->db->sets->find($attr->getAttributeSetId());
		$this->view->set = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($set->getName()));
		$this->view->set_id = $attr->getAttributeSetId();

		// output sorting
		$this->view->order = $this->req->getParam('order', 'id');
		$this->view->dir = $this->req->getParam('dir', 'asc');

		// handle filter
		$where = array("attribute_id = ".(int)$id);

		// value
		if(strlen($value) > 0)
			$where[] = "value LIKE '%".strtolower($value)."%'";

		// get options
    	$paginator = $this->db->opts->fetchListToPaginator(implode(" AND ", $where), "{$this->view->order} {$this->view->dir}");
    	$paginator->setItemCountPerPage(25);
    	$paginator->setCurrentPageNumber((int)$this->req->getParam('page', 0));

		// output attrs
		$this->view->opts = $paginator;
    }

    /**
     * Check if type is select
     *
     * @param int $id -> attribute_input_type_id
     * @return boolean
     */
    private function _typeSelect($id) {
    	$next = false;
    	foreach($this->db->attrTypes->fetchAll() as $type) {
    		if($id == $type->getId() && $type->getType() == 'select') {
    			$next = true;
    			break;
    		}
    	}

    	return $next;
    }

    /**
     * Save Translation in db
     * WARNING: modifying a value might change the whole translation everywhere
     *
     * @param string $en the EN string
     * @param string $de the DE string
     */
    private function _saveTranslation($en, $de) {
    	$trans = reset($this->db->translations->fetchList("label = '{$en}' AND language = 'de'"));
    	if($trans)
    		$trans->setValue($de)->save(true, true);
    	else {
    		if(strlen($de) > 0)
    			$this->db->translations->setOptions(array(
    				'language' => 'de',
    				'label' => $en,
    				'value' => $de
    			))->save(true, true);
    	}

		// regenerate cache
    	$cache = new Petolio_Service_Cache();
    	$cache->PoTranslations(true);
    }

    /**
     * Format for code
     * Leave only alpha numeric characters and transform everything lowercase
     *
     * @param string $i
     * @return string the formatted string
     */
    private function _code($i) {
    	$i = preg_replace("/[^a-zA-Z0-9]+/", "", $i);
    	$i = strtolower($i);

    	return $i;
    }

    /**
     * Insert attributes for sets automatically based on scope
     * @param string $scope
     * @param int $id
     * @param string $name
     */
    private function _autoAttributes($scope, $id, $name) {
    	switch($scope) {
    		case "po_pets":
				// name
				$label = 'Name';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 1,
					'print_order' => 1,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// breed
				$label = 'Breed';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 1,
					'print_order' => 2,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// gender
				$label = 'Gender';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 1,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 1,
					'print_order' => 3,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// add gender
				$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$db->getId(),'value'=>'Male'))->save();
				$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$db->getId(),'value'=>'Female'))->save();

				// birth
				$label = 'Birth';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 2,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 4,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// sale
				$label = 'Sale';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 11,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 5,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);
    		break;

    		case "po_services":
				// name
				$label = 'Name';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 1,
					'print_order' => 1,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// description
				$label = 'Description';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 5,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 2,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// street
				$label = 'Street';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 3,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// zipcode
				$label = 'Zipcode';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 4,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// address
				$label = 'Address';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 5,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// location
				$label = 'Location';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 6,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 6,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// country
				$label = 'Country';
		    	$db = clone $this->db->attrs;
				$db->setOptions(array(
					'label' => $label,
					'attribute_input_type_id' => 1,
					'active' => 1,
					'is_unique' => 0,
					'is_required' => 0,
					'print_order' => 7,
					'attribute_set_id' => $id,
					'code' => $this->_code($name) . '_' . $this->_code($label)
				))->save(false, true);

				// insert country options
				$this->_insertCountries($db->getId());
    		break;
    	}
    }

    /**
     * Insert Countries
     * @param attribute $id
     */
    private function _insertCountries($id) {
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Afghanistan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Aland Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Albania'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Algeria'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'American Samoa'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Andorra'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Angola'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Anguilla'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Antarctica'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Antigua and Barbuda'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Argentina'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Armenia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Aruba'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Australia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Austria'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Azerbaijan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bahamas'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bahrain'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bangladesh'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Barbados'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Belarus'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Belgium'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Belize'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Benin'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bermuda'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bhutan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bolivia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bosnia and Herzegovina'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Botswana'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bouvet Island'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Brazil'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'British Indian Ocean Territory'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Brunei Darussalam'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Bulgaria'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Burkina Faso'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Burundi'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cambodia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cameroon'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Canada'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cape Verde'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cayman Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Central African Republic'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Chad'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Chile'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'China'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Christmas Island'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cocos (Keeling) Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Colombia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Comoros'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Congo'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Congo, Democratic Republic of the'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cook Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Costa Rica'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cote d\'Ivoire'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Croatia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cuba'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Cyprus'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Czech Republic'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Denmark'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Djibouti'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Dominica'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Dominican Republic'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Ecuador'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Egypt'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'El Salvador'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Equatorial Guinea'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Eritrea'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Estonia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Ethiopia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Falkland Islands (Malvinas)'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Faroe Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Fiji'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Finland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'France'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'French Guiana'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'French Polynesia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'French Southern Territories'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Gabon'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Gambia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Georgia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Germany'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Ghana'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Gibraltar'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Greece'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Greenland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Grenada'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guadeloupe'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guam'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guatemala'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guernsey'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guinea'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guinea-Bissau'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Guyana'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Haiti'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Heard Island and McDonald Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Holy See (Vatican City State)'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Honduras'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Hong Kong'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Hungary'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Iceland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'India'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Indonesia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Iran, Islamic Republic of'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Iraq'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Ireland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Isle of Man'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Israel'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Italy'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Jamaica'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Japan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Jersey'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Jordan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Kazakhstan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Kenya'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Kiribati'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Korea, Democratic Peoples Republic of'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Korea, Republic of'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Kuwait'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Kyrgyzstan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Lao People\'s Democratic Republic'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Latvia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Lebanon'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Lesotho'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Liberia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Libyan Arab Jamahiriya'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Liechtenstein'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Lithuania'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Luxembourg'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Macao'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Macedonia, the former Yugoslav Republic of'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Madagascar'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Malawi'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Malaysia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Maldives'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mali'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Malta'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Marshall Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Martinique'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mauritania'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mauritius'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mayotte'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mexico'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Micronesia, Federated States of'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Moldova'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Monaco'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mongolia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Montenegro'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Montserrat'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Morocco'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Mozambique'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Myanmar'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Namibia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Nauru'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Nepal'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Netherlands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Netherlands Antilles'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'New Caledonia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'New Zealand'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Nicaragua'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Niger'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Nigeria'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Niue'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Norfolk Island'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Northern Mariana Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Norway'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Oman'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Pakistan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Palau'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Palestinian Territory, Occupied'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Panama'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Papua New Guinea'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Paraguay'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Peru'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Philippines'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Pitcairn'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Poland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Portugal'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Puerto Rico'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Qatar'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Reunion'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Romania'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Russian Federation'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Rwanda'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Barthelemy'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Helena'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Kitts and Nevis'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Lucia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Martin (French part)'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Pierre and Miquelon'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saint Vincent and the Grenadines'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Samoa'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'San Marino'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Sao Tome and Principe'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Saudi Arabia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Senegal'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Serbia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Seychelles'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Sierra Leone'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Singapore'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Slovakia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Slovenia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Solomon Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Somalia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'South Africa'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'South Georgia and the South Sandwich Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Spain'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Sri Lanka'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Sudan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Suriname'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Svalbard and Jan Mayen'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Swaziland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Sweden'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Switzerland'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Syrian Arab Republic'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Taiwan, Province of China'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Tajikistan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Tanzania, United Republic of'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Thailand'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Timor-Leste'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Togo'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Tokelau'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Tonga'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Trinidad and Tobago'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Tunisia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Turkey'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Turkmenistan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Turks and Caicos Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Tuvalu'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Uganda'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Ukraine'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'United Arab Emirates'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'United Kingdom'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'United States'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'United States Minor Outlying Islands'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Uruguay'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Uzbekistan'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Vanuatu'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Venezuela'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Viet Nam'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Virgin Islands, British'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Virgin Islands, U.S.'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Wallis and Futuna'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Western Sahara'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Yemen'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Zambia'))->save();
    	$opt = clone $this->db->opts; $opt->setOptions(array('attribute_id'=>$id,'value'=>'Zimbabwe'))->save();
    }
}
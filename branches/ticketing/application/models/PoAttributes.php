<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoAttributes extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_Id;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_AttributeSetId;
    
    /**
     * mysql var type varchar(100)
     *
     * @var string     
     */
    protected $_Code;
    
    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_AttributeInputTypeId;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_IsUnique;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_IsRequired;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Active;
    
    /**
     * mysql var type int(10)
     *
     * @var int     
     */
    protected $_PrintOrder;
    
    /**
     * mysql var type varchar(100)
     *
     * @var string     
     */
    protected $_Label;
    
    /**
     * mysql var type varchar(300)
     *
     * @var string     
     */
    protected $_Description;
    
    /**
     * the attribute set obj for the current attribute_set_id
     * @var PoAttributeSets
     */
	protected $_AttributeSet;
	
	/**
	 * the attribute input type obj for the current attribute_input_type_id
	 * @var PoAttributeInputTypes
	 */
	protected $_AttributeInputType;
	
	/**
	 * the entity obj if it's any exists
	 * @var PoAttributeEntityDatetime or PoAttributeEntityDecimal or PoAttributeEntityInt or PoAttributeEntityText or PoAttributeEntityVarchar
	 */
	protected $_AttributeEntity;

	/**
	 * mysql var type bigint(20)
	 * 
	 * @var int
	 */
	protected $_CurrencyId;
    
	/**
	 * currency obj
	 * 
	 * @var Petolio_Model_PoCurrencies
	 */
	protected $_Currency;
	
	/**
	 * mysq var type bigint(20)
	 * 
	 * @var int 
	 */
	protected $_GroupId;
	
	/**
	 * $var string
	 */
	protected $_Group;

	/**
	 * mysql var type tinyint(1)
	 * 
	 * @var int
	 */
	protected $_HasDescription;
	

	/**
	 * default contructor
	 */
	function __construct() {
	    $this->setColumnsList(array(
		    'id'=>'Id',
		    'attribute_set_id'=>'AttributeSetId',
		    'code'=>'Code',
		    'attribute_input_type_id'=>'AttributeInputTypeId',
		    'is_unique'=>'IsUnique',
		    'is_required'=>'IsRequired',
		    'active'=>'Active',
		    'print_order'=>'PrintOrder',
		    'label'=>'Label',
		    'description'=>'Description',
	    	'currency_id'=>'CurrencyId',
	    	'group_id'=>'GroupId',
	    	'has_description'=>'HasDescription'
	    ));
	}
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setId($data)
    {
        $this->_Id=$data;
        return $this;
    }

    /**
     * gets column id type bigint(20)
     * @return int     
     */
     
    public function getId()
    {
        return $this->_Id;
    }
    
    /**
     * sets column attribute_set_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setAttributeSetId($data)
    {
        $this->_AttributeSetId=$data;
        return $this;
    }

    /**
     * gets column attribute_set_id type bigint(20)
     * @return int     
     */
     
    public function getAttributeSetId()
    {
        return $this->_AttributeSetId;
    }
    
    /**
     * sets column currency_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setCurrencyId($data)
    {
        $this->_CurrencyId=$data;
        return $this;
    }

    /**
     * gets column currency_id type bigint(20)
     * @return int     
     */
     
    public function getCurrencyId()
    {
        return $this->_CurrencyId;
    }
    
    /**
     * sets column group_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setGroupId($data)
    {
        $this->_GroupId=$data;
        return $this;
    }

    /**
     * gets column group_id type bigint(20)
     * @return int     
     */
     
    public function getGroupId()
    {
        return $this->_GroupId;
    }
    
    /**
     * gets the attribute set name for the current attribute set id
     * @return string
     */
    public function getAttributeSetName() {
    	if ( !$this->getAttributeSetId() ) {
            throw new Exception('Attribute set id it\'s not set');
    	}
    	$attr_set = new Petolio_Model_PoAttributeSets();
    	$attr_set->getMapper()->find($this->getAttributeSetId(), $attr_set);
    	return $attr_set->getName();
    }

    /**
     * sets column code type varchar(100)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setCode($data)
    {
        $this->_Code=$data;
        return $this;
    }

    /**
     * gets column code type varchar(100)
     * @return string     
     */
     
    public function getCode()
    {
        return $this->_Code;
    }
    
    /**
     * sets column attribute_input_type_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setAttributeInputTypeId($data)
    {
        $this->_AttributeInputTypeId=$data;
        return $this;
    }

    /**
     * gets column attribute_input_type_id type bigint(20)
     * @return int     
     */
     
    public function getAttributeInputTypeId()
    {
        return $this->_AttributeInputTypeId;
    }
    
    /**
     * sets column is_unique type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setIsUnique($data)
    {
        $this->_IsUnique=$data;
        return $this;
    }

    /**
     * gets column is_unique type tinyint(1)
     * @return int     
     */
     
    public function getIsUnique()
    {
        return $this->_IsUnique;
    }
    
    /**
     * sets column is_required type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setIsRequired($data)
    {
        $this->_IsRequired=$data;
        return $this;
    }

    /**
     * gets column is_required type tinyint(1)
     * @return int     
     */
     
    public function getIsRequired()
    {
        return $this->_IsRequired;
    }
    
    /**
     * sets column active type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setActive($data)
    {
        $this->_Active=$data;
        return $this;
    }

    /**
     * gets column active type tinyint(1)
     * @return int     
     */
     
    public function getActive()
    {
        return $this->_Active;
    }
    
    /**
     * sets column print_order type int(10)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setPrintOrder($data)
    {
        $this->_PrintOrder=$data;
        return $this;
    }

    /**
     * gets column print_order type int(10)
     * @return int     
     */
     
    public function getPrintOrder()
    {
        return $this->_PrintOrder;
    }
    
    /**
     * sets column label type varchar(100)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setLabel($data)
    {
        $this->_Label=$data;
        return $this;
    }

    /**
     * gets column label type varchar(100)
     * @return string     
     */
     
    public function getLabel()
    {
        return $this->_Label;
    }
    
    /**
     * sets column description type varchar(300)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setDescription($data)
    {
        $this->_Description=$data;
        return $this;
    }

    /**
     * gets column description type varchar(300)
     * @return string     
     */
     
    public function getDescription()
    {
        return $this->_Description;
    }

    /**
     * sets our extra field _AttributeSet
     * @param Petolio_Model_PoAttributeSets $attribute_set
     */
    public function setAttributeSet($attribute_set = null) {
    	if ( !isset($attribute_set) ) {
	    	if ( !$this->getAttributeSetId() ) {
	            throw new Exception('Attribute set it\'s not set');
	    	}
    		$attribute_set = new Petolio_Model_PoAttributeSets();
    		$attribute_set = $attribute_set->find($this->getAttributeSetId());
    	}
    	if (!$attribute_set instanceof Petolio_Model_PoAttributeSets) {
    		throw new Exception('Invalid instance for $attribute_set object, Petolio_Model_PoAttributeSets expected.');
    	}
    	$this->_AttributeSet = $attribute_set;
    	return $this;
    }

    /**
     * gets our extra field _AttributeSet
     * @return Petolio_Model_PoAttributeSets
     */
    public function getAttributeSet() {
    	if ( !isset($this->_AttributeSet) ) {
    		$this->setAttributeSet();
    	}
    	return $this->_AttributeSet;
    }
    
    /**
     * sets our extra field _Group
     * @param Petolio_Model_PoAttributeGroups $group
     */
    public function setGroup($group = null) {
    	if ( !isset($group) ) {
	    	if ( !$this->getGroupId() ) {
	            throw new Exception('Attribute group id it\'s not set');
	    	}
    		$group = new Petolio_Model_PoAttributeGroups();
    		$group->find($this->getGroupId());
    	}
    	if (!$group instanceof Petolio_Model_PoAttributeGroups) {
    		throw new Exception('Invalid instance for $group object, Petolio_Model_PoAttributeGroups expected.');
    	}
    	$this->_Group = $group;
    	return $this;
    }

    /**
     * gets our extra field _Group
     * @return Petolio_Model_PoAttributeGroups
     */
    public function getGroup() {
    	if ( !isset($this->_Group) ) {
    		$this->setGroup();
    	}
    	return $this->_Group;
    }
    
    /**
     * sets our extra field _Currency
     * @param Petolio_Model_PoCurrencies $currency
     */
    public function setCurrency($currency = null) {
    	if ( !isset($currency) ) {
	    	if ( !$this->getCurrencyId() ) {
	            throw new Exception('Currency id it\'s not set');
	    	}
    		$currency = new Petolio_Model_PoCurrencies();
    		$currency = $currency->find($this->getCurrencyId());
    	}
    	if (!$currency instanceof Petolio_Model_PoCurrencies) {
    		throw new Exception('Invalid instance for $currency object, Petolio_Model_PoCurrencies expected.');
    	}
    	$this->_Currency = $currency;
    	return $this;
    }

    /**
     * gets our extra field _Currency
     * @return Petolio_Model_PoCurrencies
     */
    public function getCurrency() {
    	if ( !isset($this->_Currency) ) {
    		$this->setCurrency();
    	}
    	return $this->_Currency;
    }

    /**
     * sets our extra field _AttributeInputType
     * @param Petolio_Model_PoAttributeInputTypes $attribute_input_type
     */
    public function setAttributeInputType($attribute_input_type = null) {
    	if ( !isset($attribute_input_type) ) {
    		$attribute_input_type = new Petolio_Model_PoAttributeInputTypes();
    		$attribute_input_type = $attribute_input_type->find($this->getAttributeInputTypeId());
    	}
    	if (!$attribute_input_type instanceof Petolio_Model_PoAttributeInputTypes) {
    		throw new Exception('Invalid instance for $attribute_input_type object, Petolio_Model_PoAttributeInputTypes expected.');
    	}
    	$this->_AttributeInputType = $attribute_input_type;
    	return $this;
    }

    /**
     * gets our extra field _AttributeInputType
     * @return Petolio_Model_PoAttributeInputTypes
     */
    public function getAttributeInputType() {
    	if ( !isset($this->_AttributeInputType) ) {
    		$this->setAttributeInputType();
    	}
    	return $this->_AttributeInputType;
    }

    /**
     * sets column has_description type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributes     
     *
     **/

    public function setHasDescription($data)
    {
        $this->_HasDescription=$data;
        return $this;
    }

    /**
     * gets column has_description type tinyint(1)
     * @return int     
     */
     
    public function getHasDescription()
    {
        return $this->_HasDescription;
    }
    
    /**
     * sets the entity obj or array of entity objects 
     * @param PoAttributeEntityDatetime or PoAttributeEntityDecimal or PoAttributeEntityInt or PoAttributeEntityText or PoAttributeEntityVarchar or array of this $attribute_entity
     * @throws Exception if null or invalid parameter is set
     */
    public function setAttributeEntity($attribute_entity) {
    	if ( !isset($attribute_entity) ) {
    		// this is something temporary
	    	$this->_AttributeEntity = new Petolio_Model_PoAttributeEntityVarchar();
	    	return $this;
    	}
    	if ( is_array($attribute_entity) ) {
    		foreach ( $attribute_entity as $entity ) {
		    	if ( !$entity instanceof Petolio_Model_PoAttributeEntityDatetime
		    			&& !$entity instanceof Petolio_Model_PoAttributeEntityDecimal
		    			&& !$entity instanceof Petolio_Model_PoAttributeEntityInt
		    			&& !$entity instanceof Petolio_Model_PoAttributeEntityText
		    			&& !$entity instanceof Petolio_Model_PoAttributeEntityVarchar ) {
		    		throw new Exception('Invalid instance for $attribute_entity array of objects, one of the following expected: 
		    				Petolio_Model_PoAttributeEntityDatetime, Petolio_Model_PoAttributeEntityDecimal, Petolio_Model_PoAttributeEntityInt, 
		    				Petolio_Model_PoAttributeEntityText, Petolio_Model_PoAttributeEntityVarchar.');
		    	}
    		}
    	} else if ( !$attribute_entity instanceof Petolio_Model_PoAttributeEntityDatetime
    			&& !$attribute_entity instanceof Petolio_Model_PoAttributeEntityDecimal
    			&& !$attribute_entity instanceof Petolio_Model_PoAttributeEntityInt
    			&& !$attribute_entity instanceof Petolio_Model_PoAttributeEntityText
    			&& !$attribute_entity instanceof Petolio_Model_PoAttributeEntityVarchar ) {
    		throw new Exception('Invalid instance for $attribute_entity object, one of the following expected: 
    				Petolio_Model_PoAttributeEntityDatetime, Petolio_Model_PoAttributeEntityDecimal, Petolio_Model_PoAttributeEntityInt, 
    				Petolio_Model_PoAttributeEntityText, Petolio_Model_PoAttributeEntityVarchar.');
    	}
    	$this->_AttributeEntity = $attribute_entity;
    	return $this;
    }

    /**
     * @return entity object
     */
    public function getAttributeEntity() {
    	if ( !isset($this->_AttributeEntity) ) {
    		return null;
    	}
    	return $this->_AttributeEntity;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAttributesMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAttributesMapper());
        }
        return $this->_mapper;
    }


    /**
     * deletes current row by deleting a row that matches the primary key
     * 
     * @return int
     */

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }

}
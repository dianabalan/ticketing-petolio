<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoFlags extends MainModel
{
    protected $_Id;
    protected $_UserId;
    protected $_Scope;
	protected $_EntryId;
	protected $_ReasonId;
	protected $_DateFlagged;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'user_id' => 'UserId',
			'scope' => 'Scope',
			'entry_id' => 'EntryId',
			'reason_id' => 'ReasonId',
			'date_flagged' => 'DateFlagged',
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setUserId($data) { $this->_UserId = $data; return $this; }
    public function getUserId() { return $this->_UserId; }

    public function setScope($data) { $this->_Scope = $data; return $this; }
    public function getScope() { return $this->_Scope; }

    public function setEntryId($data) { $this->_EntryId = $data; return $this; }
    public function getEntryId() { return $this->_EntryId; }

    public function setReasonId($data) { $this->_ReasonId = $data; return $this; }
    public function getReasonId() { return $this->_ReasonId; }

    public function setDateFlagged($data) { $this->_DateFlagged = $data; return $this; }
    public function getDateFlagged() { return $this->_DateFlagged; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoFlagsMapper());
        }

        return $this->_mapper;
    }

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }

    /**
     * Get flags list
     *
     * @param string $type - paginator or array
     * @param string $where
     * @param string $order
     * @param array $limit
     *
     * @return either array or paginator
     */
    public function getFlags($type = 'array', $where = false, $order = false, $limit = false) {
    	$db = $this->getMapper()->getDbTable();

    	// main query
    	$select = $db->select()->setIntegrityCheck(false)
    		->from(array('a' => 'po_flags'), array('*'));

    	// join with users
    	$select->joinLeft(array('x' => 'po_users'), "a.user_id = x.id", array('user_name' => 'name'));

    	// join with reasons
    	$select->joinLeft(array('y' => 'po_flag_reasons'), "a.reason_id = y.id", array('reason_name' => 'value'));

    	// join with reasons category
    	$select->joinLeft(array('z' => 'po_flag_reasons'), "y.parent_id = z.id", array('reason_category' => 'value'));

    	// group up
    	$select->group('a.id');

    	// handle where
    	if($where) $select->where($where);

    	// handle order
    	if($order) {
    		if (strpos($order, ",") > 0)
    			foreach (explode(",", $order) as $token)
    			$select->order($token);
    		else $select->order($order);
    	}

    	// handle limit
    	if($limit) $select->limit($limit);

    	// return either array or paginator
    	return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
    }
}
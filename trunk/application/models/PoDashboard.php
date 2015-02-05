<?php
require_once('MainModel.php');

class Petolio_Model_PoDashboard extends MainModel
{
    protected $_Id;
    protected $_UserId;
    protected $_DateCreated;
    protected $_Data;
    protected $_Serialized;
    protected $_Identity;
    protected $_Rights;
    protected $_Scope;
    protected $_EntityId;
    protected $_Deleted;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
		    'user_id' => 'UserId',
		    'date_created' => 'DateCreated',
		    'data' => 'Data',
	    	'serialized' => 'Serialized',
	    	'identity' => 'Identity',
		    'rights' => 'Rights',
		    'scope' => 'Scope',
		    'entity_id' => 'EntityId',
		    'deleted' => 'Deleted'
	    ));
	}

    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setUserId($data) { $this->_UserId = $data; return $this; }
    public function getUserId() { return $this->_UserId; }

    public function setDateCreated($data) { $this->_DateCreated = $data; return $this; }
    public function getDateCreated() { return $this->_DateCreated; }

    public function setData($data) { $this->_Data = $data; return $this; }
    public function getData() { return $this->_Data; }

    public function setSerialized($data) { $this->_Serialized = $data; return $this; }
    public function getSerialized() { return $this->_Serialized; }

    public function setIdentity($data) { $this->_Identity = $data; return $this; }
    public function getIdentity() { return $this->_Identity; }

    public function setRights($data) { $this->_Rights = $data; return $this; }
    public function getRights() { return $this->_Rights; }

    public function setScope($data) { $this->_Scope = $data; return $this; }
    public function getScope() { return $this->_Scope; }

    public function setEntityId($data) { $this->_EntityId = $data; return $this; }
    public function getEntityId() { return $this->_EntityId; }

    public function setDeleted($data) { $this->_Deleted = $data; return $this; }
    public function getDeleted() { return $this->_Deleted; }

    public function getMapper() {
        if ($this->_mapper === null)
            $this->setMapper(new Petolio_Model_PoDashboardMapper());

        return $this->_mapper;
    }

    public function deleteRowByPrimaryKey() {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');

        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }

    /**
	 * Get dashboard entries complete with owner name
     *
     * @param string $where
     * @param string $order
     * @param string $limit
     *
     * @return either array or paginator
     */
    public function getEntries($where = false, $order = false, $limit = false) {
    	$db = $this->getMapper()->getDbTable();

    	// main query
    	$select = $db->select()->setIntegrityCheck(false)
    	->from(array('a' => 'po_dashboard'), array('*'))

    	// get entry owner
    	->joinLeft(array('x' => 'po_users'), "a.user_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar', 'user_gender' => 'gender'))
    	->joinLeft(array('r' => 'po_dashboard_rights'), "a.id = r.dashboard_id", array());

    	// filter and sort and limit ? ok
    	$w_string = '(x.active = 1 AND x.is_banned != 1)';
    	if($where) $w_string .= " AND {$where}";
    	$select->where($w_string);

    	if($order) $select->order($order);
		if($limit) $select->limit($limit[0], $limit[1]);

		// group by entry id
		$select->group('a.id');

    	// return either array or paginator
    	return $db->fetchAll($select)->toArray();
    }
}
<?php

class Petolio_Model_DbTable_PoFriends extends Zend_Db_Table_Abstract {

    protected $_name = 'po_friends';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        ),
        'PoFriends' => array(
            'columns'           => array('friend_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

	public function findFriends($id = null, $fid = null, $status = null)
	{
		$columns = array('link_id' => 'id');
		if ( isset($id) && isset($fid) ) {
			$columns = array();
		}
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('a' => $this->_name), $columns);

		if(isset($fid) && is_null($id)) {
			$select->joinLeft(array('b' => 'po_users'), 'a.user_id = b.id');
			// show only active users
        	$select->where("b.active = 1 AND b.is_banned != 1");
		}
		if(is_null($fid) && isset($id)) {
			$select->joinLeft(array('b' => 'po_users'), 'a.friend_id = b.id');
			// show only active users
        	$select->where("b.active = 1 AND b.is_banned != 1");
		}
			
		if(isset($id))
        	$select->where($this->getAdapter()->quoteInto("a.user_id = ?", $id, Zend_Db::BIGINT_TYPE));

		if(isset($fid))
        	$select->where($this->getAdapter()->quoteInto("a.friend_id = ?", $fid, Zend_Db::BIGINT_TYPE));

		if(isset($status))
        	$select->where($this->getAdapter()->quoteInto("a.status = ?", $status));
        	
        $out = array();
		foreach($this->fetchAll($select) as $line)
			$out[isset($line->name) ? $line->name.'-'.$line->id : $line->id] = $line; // the key is sorting purposes only

		return $out;
	}

	public function acceptFriend($id, $who) {
		$this->update(array('status' => 1), array('user_id = ?' => $who, 'friend_id = ?' => $id));
	}

	public function declineFriend($id, $who) {
		$this->delete(array('user_id = ?' => $who, 'friend_id = ?' => $id));
	}

	public function removeFriend($id, $who){
		$this->declineFriend($id, $who);
		$this->declineFriend($who, $id);
	}
}
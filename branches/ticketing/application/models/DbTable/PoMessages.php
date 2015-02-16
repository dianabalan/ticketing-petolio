<?php

class Petolio_Model_DbTable_PoMessages extends Zend_Db_Table_Abstract {

    protected $_name = 'po_messages';
	protected $_primary = 'id';

	protected $_dependentTables = array(
			'PoMessageRecipients'
	);

	protected $_referenceMap    = array(
        'PoFromUsers' => array(
            'columns'           => array('from_user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

	public function countNew($user_id)
	{
		$db = $this->getAdapter();
		$select = $db->query("SELECT COUNT(*) AS new FROM po_message_recipients AS r
			LEFT JOIN po_messages AS m ON r.message_id = m.id
			LEFT JOIN po_users AS u ON m.from_user_id = u.id   
			WHERE r.to_user_id = ".$db->quote($user_id, Zend_Db::BIGINT_TYPE)." AND (m.status = '1' OR m.status = '2') AND r.status = '0' 
			AND (u.active = 1 OR u.active IS NULL) AND (u.is_banned != 1 OR u.is_banned IS NULL) 
			ORDER BY m.id DESC
		");

		$found = $select->fetchAll();
        $found = reset($found);

        return $found['new'];
	}

	public function getInbox($user_id, $order = false, $filter = false, $product = false)
	{
		$select = $this->select()->setIntegrityCheck(false)
			->from(array('r' => 'po_message_recipients'), array('rstatus' => 'status'))
			->joinLeft(array('m' => 'po_messages'), 'r.message_id = m.id', array('*'))
			->joinLeft(array('u' => 'po_users'), 'm.from_user_id = u.id', array('name'))
        	->where($this->getAdapter()->quoteInto("r.to_user_id = ?", $user_id, Zend_Db::BIGINT_TYPE))
        	->where("m.status = '1' OR m.status = '2'")
        	// show only messages from active users and system messages
        	->where("(u.active = 1 OR u.active IS NULL) AND (u.is_banned != 1 OR u.is_banned IS NULL)")
        	->where("r.status = '0' OR r.status = '1'");

        if($product) {
        	$select->joinLeft(array('p' => 'po_message_products'), 'm.id = p.message_id', array('pid' => 'product_id'))
        		->where("p.product_id = '{$product}'");
        }

        if($filter != false)
       		$select->where($filter);

        if($order != false)
        	$select->order($order);

		$msg = new Petolio_Model_PoMessages();
		return $msg->select2Paginator($select);
	}

	public function getOutbox($user_id, $order = false, $filter = false)
	{
		$subquery1 = $this->select()->setIntegrityCheck(false)
			->from(array('r' => 'po_message_recipients'), new Zend_Db_Expr("CAST(GROUP_CONCAT(r.to_user_id SEPARATOR ',') AS CHAR)"))
			->where("r.message_id = m.id");

		$subquery2 = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'), new Zend_Db_Expr("GROUP_CONCAT(u.name SEPARATOR ', ')"))
			->where("FIND_IN_SET(u.id, combinedids)");

		$select = $this->select()->setIntegrityCheck(false)
			->from(array('m' => 'po_messages'), array('*', 'combinedids' => new Zend_Db_Expr("({$subquery1})"), 'combinedusers' => new Zend_Db_Expr("({$subquery2})")))
        	->where($this->getAdapter()->quoteInto("m.from_user_id = ?", $user_id, Zend_Db::BIGINT_TYPE))
        	->where("m.status = '1'");

        if($filter != false)
       		$select->where($filter);

        if($order != false)
        	$select->order($order);

		$msg = new Petolio_Model_PoMessages();
		return $msg->select2Paginator($select);
	}

	public function getDrafts($user_id, $order = false, $filter = false)
	{
		$subquery = $this->select()->setIntegrityCheck(false)
			->from(array('u' => 'po_users'), new Zend_Db_Expr("GROUP_CONCAT(u.name SEPARATOR ', ')"))
			->where("FIND_IN_SET(u.id, m.draft_to)");

		$select = $this->select()->setIntegrityCheck(false)
			->from(array('m' => 'po_messages'), array('*', 'combinedusers' => new Zend_Db_Expr("({$subquery})")))
        	->where($this->getAdapter()->quoteInto("m.from_user_id = ?", $user_id, Zend_Db::BIGINT_TYPE))
        	->where("m.status = '0'");

        if($filter != false)
       		$select->where($filter);

        if($order != false)
        	$select->order($order);

		$msg = new Petolio_Model_PoMessages();
		return $msg->select2Paginator($select);
	}

	public function getMessage($user_id, $message_id)
	{
		$db = $this->getAdapter();
		$sql = "SELECT m.*, u.name
			FROM po_messages AS m
			LEFT JOIN po_users AS u ON m.from_user_id = u.id
			WHERE m.id = ".$db->quote($message_id, Zend_Db::BIGINT_TYPE)." 
					AND ((m.from_user_id = ".$db->quote($user_id, Zend_Db::BIGINT_TYPE)." AND m.status != 2) 
							OR ".$db->quote($user_id, Zend_Db::BIGINT_TYPE)." IN (SELECT r.to_user_id FROM po_message_recipients AS r
								WHERE r.message_id = m.id AND r.status != 2))
			LIMIT 1";
		$select = $db->query($sql);

		$found = $select->fetchAll();
		return count($found) == 0 ? false : reset($found);
	}

	public function markAsRead($user_id, $message_id)
	{
		$db = $this->getAdapter();
		$db->query("UPDATE po_message_recipients SET status = '1' WHERE to_user_id = ".$db->quote($user_id, Zend_Db::BIGINT_TYPE)." AND message_id = ".$db->quote($message_id, Zend_Db::BIGINT_TYPE)." AND status = '0'");
	}
}
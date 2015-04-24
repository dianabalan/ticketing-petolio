<?php
require_once('MainModel.php');

class Petolio_Model_PoClients extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    protected $_SpId;
    protected $_ClientId;
    protected $_ClientTypeId;
    protected $_ClientNo;
    protected $_Remarks;
    protected $_BillingInterval;
    protected $_Payment;
    protected $_IsActive;
    protected $_DateCreated;
    protected $_DateModified;

	function __construct() {
	    $this->setColumnsList(array(
			'ID'=>'Id',
		    'sp_id'=>'SpId',
		    'client_id'=>'ClientId',
		    'clienttype_id'=>'ClientTypeId',
		    'clientno'=>'ClientNo',
		    'remarks'=>'Remarks',
		    'billing_interval'=>'BillingInterval',
		    'payment'=>'Payment',
		    'isActive'=>'IsActive',
		    'date_created'=>'DateCreated',
		    'date_modified'=>'DateModified'
	    ));
	}

	public function user_is_client($user_id) {

		$user_id = (int)$user_id;
		$db = $this->getMapper()->getDbTable()->getAdapter();
		$select = $db->query("
			SELECT
				COUNT(1) AS user_is_client
			FROM
				po_clients AS pc
			WHERE
				client_id = {$user_id}
		");

		return $select->fetch();

	}

}
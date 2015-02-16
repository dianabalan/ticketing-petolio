<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoDashboardRights extends MainModel
{
    protected $_Id;
    protected $_DashboardId;
    protected $_UserId;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'dashboard_id' => 'DashboardId',
		    'user_id' => 'UserId'
	    ));
	}

	// setter / getter
    public function setId($data) { $this->_Id = $data; return $this; }
    public function getId() { return $this->_Id; }

    public function setDashboardId($data) { $this->_DashboardId = $data; return $this; }
    public function getDashboardId() { return $this->_DashboardId; }

    public function setUserId($data) { $this->_UserId = $data; return $this; }
    public function getUserId() { return $this->_UserId; }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoDashboardRightsMapper());
        }
        return $this->_mapper;
    }

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }
}
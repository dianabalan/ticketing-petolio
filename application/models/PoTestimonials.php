<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoTestimonials extends MainModel
{
    protected $_Id;
    protected $_UserId;
    protected $_Name;
    protected $_Email;
    protected $_Subject;
    protected $_Message;
    protected $_DateCreated;
    protected $_Status;

	function __construct() {
	    $this->setColumnsList(array(
		    'id' => 'Id',
	    	'user_id' => 'UserId',
		    'name' => 'Name',
		    'email' => 'Email',
	    	'subject' => 'Subject',
	    	'message' => 'Message',
	    	'date_created' => 'DateCreated',
	    	'status' => 'Status'
	    ));
	}

	// setter / getter
    public function setId($data)
    {
        $this->_Id = $data;
        return $this;
    }
    public function getId()
    {
        return $this->_Id;
    }

    public function setUserId($data)
    {
        $this->_UserId = $data;
        return $this;
    }
    public function getUserId()
    {
        return $this->_UserId;
    }

    public function setName($data)
    {
        $this->_Name = $data;
        return $this;
    }
    public function getName()
    {
        return $this->_Name;
    }

    public function setEmail($data)
    {
        $this->_Email = $data;
        return $this;
    }
    public function getEmail()
    {
        return $this->_Email;
    }

    public function setSubject($data)
    {
        $this->_Subject = $data;
        return $this;
    }
    public function getSubject()
    {
        return $this->_Subject;
    }

    public function setMessage($data)
    {
        $this->_Message = $data;
        return $this;
    }
    public function getMessage()
    {
        return $this->_Message;
    }

    public function setDateCreated($data)
    {
        $this->_DateCreated = $data;
        return $this;
    }
    public function getDateCreated()
    {
        return $this->_DateCreated;
    }
    
    public function setStatus($data)
    {
        $this->_Status = $data;
        return $this;
    }
    public function getStatus()
    {
        return $this->_Status;
    }

    // other stuff
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoTestimonialsMapper());
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
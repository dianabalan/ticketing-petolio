<?php
class Petolio_Model_Ticket_Ticket extends Petolio_Model_Ticket_Entity
{
	private $_itemId;
	private $_userId;
	private $_description;
	private $_ticketDate;
	private $_dateCreated;
	private $_dateModified;
	private $_flagReminder;
	private $_archive;
	private $_scope;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getItemId()
	{
		return $this->_itemId;
	}
	public function setItemId($value)
	{
		$this->_itemId = $value;
	}
	
	public function getUserId()
	{
		return $this->_userId;
	}
	public function setUserId($value)
	{
		$this->_userId = $value;
	}
	
	public function getDescription()
	{
		return $this->_description;
	}
	public function setDescription($value)
	{
		$this->_description = $value;
	}
	
	public function getTicketDate()
	{
		return $this->_ticketDate;
	}
	public function setTicketDate($value)
	{
		$this->_ticketDate = $value;
	}
	
	public function getDateCreated()
	{
		return $this->_dateCreated;
	}
	public function setDateCreated($value)
	{
		$this->_dateCreated = $value;
	}
	
	public function getDateModified()
	{
		return $this->_dateModified;
	}
	public function setDateModified($value)
	{
		$this->_dateModified = $value;
	}
	
	public function getFlagReminder()
	{
		return $this->_flagReminder;
	}
	public function setFlagReminder($value)
	{
		$this->_flagReminder = $value;
	}
	
	public function getArchive()
	{
		return $this->_archive;
	}
	public function setArchive($value)
	{
		$this->_archive = $value;
	}
	
	public function getScope()
	{
		return $this->_scope;
	}
	public function setScope($value)
	{
		$this->_scope = $value;
	}
}
<?php
class Petolio_Model_Ticket_TicketsClientWf extends Petolio_Model_Ticket_Entity
{
	private $_flgworkflow;

	private $_dateCreated;
	
	private $_ticketsClientsId;

	public function __construct()
	{
		parent::__construct();
	}

	public function getFlgworkflow()
	{
		return $this->_flgworkflow;
	}

	public function setFlgworkflow($_flgworkflow)
	{
		$this->_flgworkflow = $_flgworkflow;
		return $this;
	}

	public function getDateCreated()
	{
		return $this->_dateCreated;
	}

	public function setDateCreated($_dateCreated)
	{
		$this->_dateCreated = $_dateCreated;
		return $this;
	}

	public function getTicketsClientsId()
	{
		return $this->_ticketsClientsId;
	}

	public function setTicketsClientsId($_ticketsClientsId)
	{
		$this->_ticketsClientsId = $_ticketsClientsId;
		return $this;
	}
}

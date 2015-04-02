<?php
class Petolio_Model_Ticket_TicketsClient extends Petolio_Model_Ticket_Entity
{
	private $_ticketId;

	private $_clientId;

	private $_amount;

	private $_price;

	private $_date;

	public function __construct()
	{
		parent::__construct();
	}

	public function getTicketId()
	{
		return $this->_ticketId;
	}
	
	public function setTicketId($_ticketId)
	{
		$this->_ticketId = $_ticketId;
		return $this;
	}
	
	public function getClientId()
	{
		return $this->_clientId;
	}
	
	public function setClientId($clientId)
	{
		$this->_clientId = $clientId;
		return $this;
	}
	
	public function getAmount()
	{
		return $this->_amount;
	}

	public function setAmount($_amount)
	{
		$this->_amount = $_amount;
		return $this;
	}

	public function getPrice()
	{
		return $this->_price;
	}
	
	public function setPrice($_price)
	{
		$this->_price = $_price;
		return $this;
	}
	
	public function getDate()
	{
		return $this->_date;
	}
	
	public function setDate($date)
	{
		$this->_date = $date;
		return $this;
	}

}

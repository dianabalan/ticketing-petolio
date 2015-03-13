<?php

class Petolio_Model_Ticket_TicketMapper extends Petolio_Model_Ticket_DataMapperAbstract
{
    public function __construct()
    {
        $this->setDbTableClass('Petolio_Model_DbTable_Ticket_Tickets');
    }

    /** 
     * @param Petolio_Model_Ticket_Ticket $object
     * @return array
     */
    protected function fromClassToDb($object)
    {
        $data = array(
            'ID' => $object->getId(),
			'item_id' => $object->getItemId(),
			'user_id' => $object->getUserId(),
			'description' => $object->getDescription(),
			'ticket_date' => $object->getTicketDate(),
			'date_created' => $object->getDateCreated(),
			'date_modified' => $object->getDateModified(),
			'flgreminder' => $object->getFlagReminder(),
			'archive' => $object->getArchive(),
			'scope' => $object->getScope()
        );
        
        return $data;
    }

    /**
     * @param array $row
     */
    protected function fromDbToClass($row)
    {
        $ticket = new Petolio_Model_Ticket_Ticket();
        
        $ticket->setId($row['ID']);
        $ticket->setItemId($row['item_id']);
        $ticket->setUserId($row['user_id']);
        $ticket->setDescription($row['description']);
        $ticket->setTicketDate($row['ticket_date']);
        $ticket->setDateCreated($row['date_created']);
        $ticket->setDateModified($row['date_modified']);
        $ticket->setFlagReminder($row['flgreminder']);
        $ticket->setArchive($row['archive']);
        $ticket->setScope($row['scope']);
        
        return $ticket;
    }
    
    /**
     * 
     * @param integer $id
     * @return Petolio_Model_Ticket_Ticket|NULL
     */
    public function fetchTicket($id)
    {
        $row = $this->getDbTable()->fetchTicket($id);
        
        if ( $row )
        {
            return $this->fromDbToClass($row);
        }
        
        return null;
    }

    /**
     * 
     * @param integer $user_id
     * @param integer $archive Defalut is 1. Can be set to 0.
     * @return Zend_Paginator
     */
    public function fetchTickets($user_id, $archive = 1)
    {
    	$rows = $this->getDbTable()->fetchTickets($user_id, $archive);
    	$adapter = new Zend_Paginator_Adapter_Array($rows);
    	return new Zend_Paginator($adapter);
    }
    
    public function save(Petolio_Model_Ticket_Ticket $ticket, $ignoreNullValues = true, $escapeValues = false)
    {
        parent::save($ticket, $ignoreNullValues, $escapeValues);
    }

}

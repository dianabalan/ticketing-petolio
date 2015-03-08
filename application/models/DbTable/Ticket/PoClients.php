<?php

class Petolio_Model_DbTable_Ticket_PoClients extends Zend_Db_Table_Abstract
{

    protected $_name = 'po_clients';

    protected $_primary = 'ID';

    public function fetchClient($client_id, $sp_id)
    {
        $db = $this->getAdapter();
        
        $columns = array(
            'c.ID', 
            'c.sp_id', 
            'c.client_id', 
            'c.clienttype_id', 
            'c.clientno', 
            'c.remarks', 
            'c.billing_interval', 
            'c.payment', 
            'c.isActive', 
            'c.date_created', 
            'c.date_modified'
        );
        
        $query = $db->select()
                    ->from(array('c' => $this->_name), $columns)
                    ->where('c.sp_id = :sp_id')
                    ->where('c.client_id = :client_id');
        
        $row = $db->fetchRow($query, array(
            ':sp_id' => $sp_id, 
            ':client_id' => $client_id
        ));
        
        return $row;
    }

}

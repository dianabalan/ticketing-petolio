<?php

class Petolio_Model_DbTable_Ticket_PoClients extends Zend_Db_Table_Abstract
{

    protected $_name = 'po_clients';

    protected $_primary = 'ID';

    public function fetchClient ($sp_id, $client_id) {
        $db = $this->getAdapter();
        
        $query = $db->select()
        ->from(array('c' => $this->_name), '*')
        ->where('c.sp_id = :sp_id')
        ->where('c.client_id = :client_id');
        
        $row = $db->fetchRow($query, array(
            ':sp_id' => $sp_id,
            ':client_id' => $client_id
        ));
        
        return $row;
    }
}

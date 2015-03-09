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
    
    public function insert(array $data)
    {
        date_default_timezone_set("UTC");
        $now = date("Y-m-d H:i:s", time());
        
        $data['date_created'] = $now;
        $data['date_modified'] = $now;
        
        $row = $this->fetchClient($data['client_id'], $data['sp_id']);
        if ( $row )
        {
            throw new Exception('client already added');
        }
        
        return parent::insert($data);
    }
    
    public function update(array $data, $where)
    {
        date_default_timezone_set("UTC");
        $now = date("Y-m-d H:i:s", time());
        
        $data['date_modified'] = $now;
        
        return parent::update($data, $where);
    }

}

<?php

class Petolio_Model_Ticket_ClientsManager
{

    private $_dataMapper;

    public function __construct()
    {
        $this->_dataMapper = new Petolio_Model_Ticket_ClientMapper();
    }

    private function addClient(Petolio_Model_Ticket_Client $client)
    {
        $success = true;
        
        try {
            $this->_dataMapper->save($client, true, true);
        } catch (Exception $e) {
            $success = false;
        }
        
        return $success;
    }

    public function addClients(array $user_ids, $sp_id)
    {
        $success_count = 0;
        
        foreach ($user_ids as $id)
        {
            $client = new Petolio_Model_Ticket_Client();
            $client->setClientId($id);
            $client->setSpId($sp_id);
            $client->setIsActive(true);
            
            if ( $this->addClient($client) )
            {
                $success_count += 1;
            }
        }
        
        return $success_count;
    }

    public function getClients($sp_id)
    {
    }

    public function getActiveClients($sp_id)
    {
    }

    public function getInactiveClients($sp_id)
    {
    }

}

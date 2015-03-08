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
        
        try
        {
            $this->_dataMapper->save($client, true, true);
        }
        catch (Exception $e)
        {
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
    
    /**
     * Saves the changes performed on the specified client to the data source.
     * 
     * @param Petolio_Model_Ticket_Client $client The client object to be saved.
     * 
     * @return void
     */
    public function save(Petolio_Model_Ticket_Client $client)
    {
        return $this->_dataMapper->save($client);
    }

    /**
     * Gets a client associated with the specified service provider.
     *
     * @author Stefan Baiu
     *        
     * @param int $user_id The id of the user registered as client.
     * @param int $sp_id The id of the service provider.
     *       
     * @return Petolio_Model_Ticket_Client
     */
    public function getClient($user_id, $sp_id)
    {
        return $this->_dataMapper->fetchClient($user_id, $sp_id);
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

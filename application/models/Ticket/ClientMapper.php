<?php

class Petolio_Model_Ticket_ClientMapper extends Petolio_Model_Ticket_DataMapperAbstract
{

    private static $_paymentMethods = array(
        'cash', 
        'paypal', 
        'bank'
    );

    public function __construct()
    {
        $this->setDbTableClass('Petolio_Model_DbTable_Ticket_PoClients');
    }

    protected function fromClassToDb($object)
    {
        $data = array(
            'ID' => $object->getId(), 
            'sp_id' => $object->getSpId(), 
            'client_id' => $object->getClientId(), 
            'clienttype_id' => $object->getClientTypeId(), 
            'clientno' => $object->getClientNo(), 
            'remarks' => $object->getRemarks(), 
            'billing_interval' => $object->getBillingInterval(), 
            'payment' => $object->getPayment(), 
            'isActive' => $object->getIsActive(), 
            'date_created' => $object->getDateCreated(), 
            'date_modified' => $object->getDateModified()
        );
        
        if ( !in_array($data['payment'], self::$_paymentMethods) )
        {
            $data['payment'] = null;
        }
        
        return $data;
    }

    protected function fromDbToClass($row)
    {
        $client = new Petolio_Model_Ticket_Client();
        
        $client->setId($row['ID']);
        $client->setSpId($row['sp_id']);
        $client->setClientId($row['client_id']);
        $client->setClientTypeId($row['clienttype_id']);
        $client->setClientNo($row['clientno']);
        $client->setRemarks($row['remarks']);
        $client->setBillingInterval($row['billing_interval']);
        $client->setPayment($row['payment']);
        $client->setIsActive($row['isActive']);
        $client->setDateCreated($row['date_created']);
        $client->setDateModified($row['date_modified']);
        
        return $client;
    }
    
    public function fetchClient($user_id, $sp_id)
    {
        $row = $this->getDbTable()->fetchClient($user_id, $sp_id);
        
        if ( $row )
        {
            return $this->fromDbToClass($row);
        }
        
        return null;
    }

    public function save(Petolio_Model_Ticket_Client $client, $ignoreNullValues = true, $escapeValues = false)
    {
        date_default_timezone_set("UTC");
        $now = date("Y-m-d H:i:s", time());
        
        $client->setDateModified($now);
        if ( null === $client->getId() )
        {
            $client->setDateCreated($now);
            
            $row = $this->getDbTable()->fetchClient($client->getClientId(), $client->getSpId());
            if ( $row )
            {
                throw new Exception('client already added');
            }
        }
        
        parent::save($client, $ignoreNullValues, $escapeValues);
    }

}

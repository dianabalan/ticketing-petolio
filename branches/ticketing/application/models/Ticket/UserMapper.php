<?php

class Petolio_Model_Ticket_UserMapper extends Petolio_Model_Ticket_DataMapperAbstract
{

    public function __construct()
    {
        $this->setDbTableClass('Petolio_Model_DbTable_Ticket_PoUsers');
    }

    protected function fromClassToDb($object)
    {
        $data = array(
            'id' => $object->getId(),
            'name' => $object->getName(),
            'email' => $object->getEmail(),
            'address' => $object->getAddress(),
            'location' => $object->getLocation(),
            'countryId' => $object->getCountryId(),
            'zipcode' => $object->getZipcode(),
            'type' => $object->getType(),
            'avatar' => $object->getAvatar()
        );
        
        return $data;
    }

    protected function fromDbToClass($row)
    {
        $user = new Petolio_Model_Ticket_User();
        
        $user->setId($row['id']);
        $user->setName($row['name']);
        $user->setEmail($row['email']);
        $user->setAddress($row['address']);
        $user->setLocation($row['location']);
        $user->setCountryId($row['country_id']);
        $user->setZipcode($row['zipcode']);
        $user->setType($row['type']);
        $user->setAvatar($row['avatar']);
        
        return $user;
    }
    
    public function fetchNonClients($sp_id, $page, $items_per_page, Petolio_Model_Ticket_SearchUserFilter $filter = null)
    {
        $filter_data = isset($filter) ? $filter->toArray() : null;
        $paginator = $this->getDbTable()->fetchNonClients($sp_id, $page, $items_per_page, $filter_data);
        return $paginator;
        
        //$users = array();
        //foreach ($rows as $row)
        //{
           // $users[] = $this->fromDbToClass($row);
        //}
        
        //return $users;
    }

}

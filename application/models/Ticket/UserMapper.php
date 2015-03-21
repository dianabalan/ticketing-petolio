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
            'first_name' => $object->getFirstname(), 
            'last_name' => $object->getLastname(), 
            'email' => $object->getEmail(), 
            'street' => $object->getStreet(), 
            'address' => $object->getAddress(), 
            'location' => $object->getLocation(), 
            'country_id' => $object->getCountryId(), 
            'zipcode' => $object->getZipcode(), 
            'type' => $object->getType(), 
            'avatar' => $object->getAvatar(), 
            'phone' => $object->getPhone(), 
            'private_phone' => $object->getPrivatePhone(), 
            'gender' => $object->getGender(),
            'date_modified' => $object->getDateModified()
        );
        
        return $data;
    }

    protected function fromDbToClass($row)
    {
        $user = new Petolio_Model_Ticket_User();
        
        $user->setId($row['id']);
        $user->setName($row['name']);
        $user->setFirstname($row['first_name']);
        $user->setLastname($row['last_name']);
        $user->setEmail($row['email']);
        $user->setStreet($row['street']);
        $user->setAddress($row['address']);
        $user->setLocation($row['location']);
        $user->setCountryId($row['country_id']);
        $user->setZipcode($row['zipcode']);
        $user->setType($row['type']);
        $user->setAvatar($row['avatar']);
        $user->setPhone($row['phone']);
        $user->setPrivatePhone($row['private_phone']);
        $user->setGender($row['gender']);
        $user->setDateModified($row['date_modified']);
        
        return $user;
    }
    
    private function _fromDbToNonPetolioClass($row)
    {
        $user = new Petolio_Model_Ticket_NonPetolioMember();
        
        $user->setId($row['id']);
        $user->setName($row['name']);
        $user->setFirstname($row['first_name']);
        $user->setLastname($row['last_name']);
        $user->setEmail($row['email']);
        $user->setStreet($row['street']);
        $user->setAddress($row['address']);
        $user->setLocation($row['location']);
        $user->setCountryId($row['country_id']);
        $user->setZipcode($row['zipcode']);
        $user->setType($row['type']);
        $user->setAvatar($row['avatar']);
        $user->setPhone($row['phone']);
        $user->setPrivatePhone($row['private_phone']);
        $user->setGender($row['gender']);
        $user->setRemarks($row['remarks']);
        
        return $user;
    }

    public function fetchNonClients($sp_id, $page, $items_per_page, Petolio_Model_Ticket_SearchUserFilter $filter = null)
    {
        $filter_data = isset($filter) ? $filter->toArray() : null;
        $paginator = $this->getDbTable()->fetchNonClients($sp_id, $page, $items_per_page, $filter_data);
        return $paginator;
        
        // $users = array();
        // foreach ($rows as $row)
        // {
        // $users[] = $this->fromDbToClass($row);
        // }
        
        // return $users;
    }

    public function fetchClients($sp_id)
    {
        $rows = $this->getDbTable()->fetchClients($sp_id);
        
        $users = array();
        foreach ($rows as $row)
        {
            $users[] = $this->fromDbToClass($row);
        }
        
        return $users;
    }
    
    public function fetchInactiveClients($sp_id)
    {
        $rows = $this->getDbTable()->fetchInactiveClients($sp_id);
    
        $users = array();
        foreach ($rows as $row)
        {
            $users[] = $this->fromDbToClass($row);
        }
    
        return $users;
    }

    public function registerNonPetolioMember(Petolio_Model_Ticket_NonPetolioMember $user, $sp_id)
    {
        $user_data = $this->fromClassToDb($user);
        $user_data['remarks'] = $user->getRemarks();
        $this->_replaceEmptyStringsWithNullValues($user_data);
        
        return $this->getDbTable()->registerNonPetolioMember($user_data, $sp_id);
    }
    
    public function updateNonPetolioMember(Petolio_Model_Ticket_NonPetolioMember $user, $sp_id)
    {
        $user_data = $this->fromClassToDb($user);
        $user_data['remarks'] = $user->getRemarks();
        $this->_replaceEmptyStringsWithNullValues($user_data);
    
        return $this->getDbTable()->updateNonPetolioMember($user_data, $sp_id);
    }
    
    public function fetchNonPetolioMember($user_id, $sp_id)
    {
        $row = $this->getDbTable()->fetchNonPetolioMember($user_id, $sp_id);
        
        if ( $row )
        {
            $user = $this->_fromDbToNonPetolioClass($row);
            return $user;
        }
        
        return null;
    }
    
    public function fetchNonPetolioMembers($sp_id)
    {
        $rows = $this->getDbTable()->fetchNonPetolioMembers($sp_id);
        
        $users = array();
        foreach ($rows as $row)
        {
            $users[] = $this->_fromDbToNonPetolioClass($row);
        }
        
        return $users;
    }

    public function isAlreadyRegisteredAsPetolio($email)
    {
    	return $this->getDbTable()->isAlreadyRegisteredAsPetolio($email);
    }
    
    public function isAlreadyRegisteredAsNonPetolio($email, $sp_id)
    {
        return $this->getDbTable()->isAlreadyRegisteredAsNonPetolio($email, $sp_id);
    }
}

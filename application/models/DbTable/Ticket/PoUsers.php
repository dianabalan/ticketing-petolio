<?php

class Petolio_Model_DbTable_Ticket_PoUsers extends Zend_Db_Table_Abstract
{

    protected $_name = 'po_users';

    protected $_primary = 'id';

    public function fetchNonClients($sp_id, $page, $items_per_page, $filter_data = null)
    {
        $db = $this->getAdapter();
        
        $columns = array(
            'u.id', 
            'u.name', 
            'u.email', 
            'u.address', 
            'u.location', 
            'u.country_id', 
            'u.zipcode', 
            'u.avatar'
        );
        
        $subquery = 'SELECT c.client_id FROM po_clients c WHERE c.sp_id = :sp_id';
        
        $params = array();
        
        $query = $db->select()
                    ->from(array('u' => $this->_name), $columns)
                    ->where('u.active = 1')
                    ->where(new Zend_Db_Expr('u.type = 1 OR u.type = 2'))
                    ->where('u.id <> :sp_id')
                    ->where(new Zend_Db_Expr('u.id NOT IN (' . $subquery . ')'));
        
        if ( is_array($filter_data) )
        {
            if ( isset($filter_data['keyword']) )
            {
                $query = $query->where(new Zend_Db_Expr("u.name LIKE :keyword"));
                $params[':keyword'] = '%' . $filter_data['keyword'] . '%';
            }
            
            if ( isset($filter_data['country']) )
            {
                $query = $query->where(new Zend_Db_Expr("u.country_id = :country"));
                $params[':country'] = $filter_data['country'];
            }
            
            if ( isset($filter_data['zipcode']) )
            {
                $query = $query->where(new Zend_Db_Expr("u.zipcode LIKE :zipcode"));
                $params[':zipcode'] = '%' . $filter_data['zipcode'] . '%';
            }
            
            if ( isset($filter_data['address']) )
            {
                $query = $query->where(new Zend_Db_Expr("u.address LIKE :address"));
                $params[':address'] = '%' . $filter_data['address'] . '%';
            }
            
            if ( isset($filter_data['location']) )
            {
                $query = $query->where(new Zend_Db_Expr("u.location LIKE :location"));
                $params[':location'] = '%' . $filter_data['location'] . '%';
            }
        }
        
        $params[':sp_id'] = $sp_id;
        
        $query->bind($params);
        
        $adapter = new Zend_Paginator_Adapter_DbSelect($query);
        $paginator = new Zend_Paginator($adapter);
        
        $paginator->setItemCountPerPage((int) $items_per_page);
        $paginator->setCurrentPageNumber((int) $page);
        
        return $paginator;
    }

    public function fetchClients($sp_id)
    {
        $db = $this->getAdapter();
        
        $columns = array(
            'u.id', 
            'u.name', 
            'u.email', 
            'u.address', 
            'u.location', 
            'u.country_id', 
            'u.zipcode', 
            'u.avatar', 
            'u.type'
        );
        
        $query = $db->select()
                    ->from(array('u' => $this->_name), $columns)
                    ->joinInner(array('c' => 'po_clients'), 'u.id = c.client_id', array())
                    ->where('c.sp_id = :sp_id')
                    ->where('c.isActive = 1');
        
        $rows = $db->fetchAll($query, array(
            ':sp_id' => $sp_id
        ));
        
        return $rows;
    }

    public function isAlreadyRegisteredAsNonPetolio($email, $sp_id)
    {
        $db = $this->getAdapter();
        
        $columns = array('COUNT(*)');
        
        $query = $db->select()
                    ->from(array('u' => $this->_name), $columns)
                    ->joinInner(array('c' => 'po_clients'), 'u.id = c.client_id', array())
                    ->where('u.type = 3')
                    ->where('u.email = :email')
                    ->where('c.sp_id = :sp_id');
        
        $count = $db->fetchOne($query, array(
            ':email' => $email, 
            ':sp_id' => $sp_id
        ));
        
        return $count > 0;
    }

    public function isAlreadyRegisteredAsPetolio($email)
    {
    	$db = $this->getAdapter();
    	
    	$columns = array('COUNT(*)');
    	 
    	$query = $db->select()
    				->from(array('u' => $this->_name), $columns)
    				->where('u.email = :email')
    				->where('u.type <> 3');
    	
    	$count = $db->fetchOne($query, array(
    			':email' => $email
    	));
    	
    	return $count > 0;
    }
    
    private function _getNonPetolioDefaults()
    {
        $defaults = array(
            'password' => 'dummy password',
            'dash_email_notification' => 0, 
            'weekly_email_notification' => 0, 
            'active' => 1
        );
        
        return $defaults;
    }

    private function _insertNonPetolio(array $user_data, $sp_id)
    {
        $email = $user_data['email'];
        
        if ( $this->isAlreadyRegisteredAsNonPetolio($email, $sp_id) )
        {
            throw new Exception('non petolio member already exists');
        }
        
        $defaults = $this->_getNonPetolioDefaults();
        $data = array_merge($defaults, $user_data);
        $data['type'] = 3; 
        
        $pk_value = $this->insert($data);
        
        return $pk_value;
    }
    
    private function _updateNonPetolio(array $user_data, $sp_id)
    {
        $data = $user_data;
        $data['type'] = 3;
        
        $id = (int) $data[$this->_primary];
        $affected_rows_count = $this->update($data, array(
            $this->_primary. ' = ?' => $id    
        ));
        
        return $affected_rows_count;
    }

    public function registerNonPetolioMember(array $user_data, $sp_id)
    {
        $db = $this->getAdapter();
        
        $db->beginTransaction();
        
        try
        {
            $remarks = $user_data['remarks'];
            unset($user_data['remarks']);
            
            $pk_value = $this->_insertNonPetolio($user_data, $sp_id);
            
            $client_data = array(
                'client_id' => $pk_value, 
                'sp_id' => $sp_id, 
                'remarks' => $remarks
            );
            
            $clientsDbTable = new Petolio_Model_DbTable_Ticket_PoClients();
            $clientsDbTable->insert($client_data);
            
            $db->commit();
        }
        catch (Exception $e)
        {
            $db->rollBack();
            throw $e;
        }
    }

    public function updateNonPetolioMember(array $user_data, $sp_id)
    {
        $db = $this->getAdapter();
        
        $db->beginTransaction();
        
        try
        {
            $remarks = $user_data['remarks'];
            $id = (int) $user_data[$this->_primary];
            
            unset($user_data['remarks']);
            
            $clientsDbTable = new Petolio_Model_DbTable_Ticket_PoClients();
            
            $client_id = $id;
            $row = $clientsDbTable->fetchClient($client_id, $sp_id);
        
            if ( null === $row )
            {
                throw new Exception('no such client');
            }
            
            $pk_value = $this->_updateNonPetolio($user_data, $sp_id);
        
            $client_data = array(
                'remarks' => $remarks
            );
        
            $clientsDbTable->update($client_data, array(
                'ID = ?' => $row['ID'] 
            ));
        
            $db->commit();
        }
        catch (Exception $e)
        {
            $db->rollBack();
            throw $e;
        }
    }
    
    public function fetchNonPetolioMembers($sp_id)
    {
        $db = $this->getAdapter();
        
        $columns = array(
            'u.id',
            'u.name',
            'u.email',
            'u.address',
            'u.location',
            'u.zipcode',
            'u.avatar',
        );
        
        $query = $db->select()
                    ->from(array('u' => $this->_name), $columns)
                    ->joinInner(array('c' => 'po_clients'), 'u.id = c.client_id', array())
                    ->where('u.type = 3')
                    ->where('c.sp_id = :sp_id');
        
        $rows = $db->fetchAll($query, array(
            ':sp_id' => $sp_id
        ));
        
        return $rows;
    }
    
    public function fetchNonPetolioMember($user_id, $sp_id)
    {
        $db = $this->getAdapter();
    
        $columns = array(
            'u.id',
            'u.name',
            'u.first_name',
            'u.last_name',
            'u.email',
            'u.street',
            'u.address',
            'u.zipcode',
            'u.location',
            'u.country_id',
            'u.phone',
            'u.private_phone',
            'u.gender',
            'c.remarks'
        );
    
        $query = $db->select()
                    ->from(array('u' => $this->_name), $columns)
                    ->joinInner(array('c' => 'po_clients'), 'u.id = c.client_id', array())
                    ->where('u.id = :user_id')
                    ->where('u.type = 3')
                    ->where('c.sp_id = :sp_id');
    
        $row = $db->fetchRow($query, array(
            ':sp_id' => $sp_id,
            ':user_id' => $user_id
        ));
    
        return $row;
    }
}

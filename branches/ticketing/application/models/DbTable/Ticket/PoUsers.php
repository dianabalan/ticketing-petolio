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
            ->from(array(
            'u' => $this->_name
        ), $columns)
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

    private function _isAlreadyRegisteredAsNonPetolio($email, $sp_id)
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

    private function _getNonPetolioDefaults()
    {
        $defaults = array(
            'password' => 'dummy password', 
            'type' => 3, 
            'dash_email_notification' => 0, 
            'weekly_email_notification' => 0, 
            'active' => 1
        );
        
        return $defaults;
    }

    private function _insertNonPetolio(array $user_data, $sp_id)
    {
        $email = $user_data['email'];
        
        if ( $this->_isAlreadyRegisteredAsNonPetolio($email, $sp_id) )
        {
            throw new Exception('non petolio member already exists');
        }
        
        $defaults = $this->_getNonPetolioDefaults();
        $data = array_merge($defaults, $user_data);
        $pk_value = $this->insert($data);
        
        return $pk_value;
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

}

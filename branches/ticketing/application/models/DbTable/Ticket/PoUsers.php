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
            'u.avatar',
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

}
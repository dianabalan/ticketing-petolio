<?php

abstract class Petolio_Model_Ticket_DataMapperAbstract
{

    private $_dbTable;

    private $_dbTableClass;

    protected function setDbTableClass($dbTableClass)
    {
        $this->_dbTableClass = $dbTableClass;
    }

    protected function getDbTableClass()
    {
        return $this->_dbTableClass;
    }

    protected function setDbTable($dbTable)
    {
        if ( is_string($dbTable) )
        {
            $dbTable = new $dbTable();
        }
        
        if ( !$dbTable instanceof Zend_Db_Table_Abstract )
        {
            throw new Exception('Invalid table data gateway provided');
        }
        
        $this->_dbTable = $dbTable;
        
        return $this;
    }

    protected function getDbTable()
    {
        if ( null === $this->_dbTable )
        {
            $this->setDbTable($this->getDbTableClass());
        }
        
        return $this->_dbTable;
    }

    abstract protected function fromClassToDb($object);

    abstract protected function fromDbToClass($row);

    protected function _replaceEmptyStringsWithNullValues(array &$data)
    {
        foreach ($data as $key => $value)
        {
            $replace = ((gettype($value) === 'string') && !strlen($value));
            
            if ( $replace )
            {
                $data[$key] = null;
            }
        }
    }
    
    private function _removeNullEntries(array &$data)
    {
    	foreach ($data as $key => $value)
    	{
    		if ( null === $value )
    		{
    			unset($data[$key]);
    		}
    	}
    }

    public function save(Petolio_Model_Ticket_Entity $entity, $ignoreNullValues = true, $escapeValues = false)
    {
        $data = $this->fromClassToDb($entity);
        
        $this->_replaceEmptyStringsWithNullValues($data);
        
        if ( $ignoreNullValues )
        {
        	$this->_removeNullEntries($data);
        }
        
        if ( $escapeValues )
        {
            foreach ($data as $key => $value)
            {
                if ( !($value instanceof Zend_Db_Expr) )
                {
                    $data[$key] = Petolio_Service_Util::escape($data[$key]);
                }
            }
        }
        
        $dbTable = $this->getDbTable();
        // for our tables the primary key is composed of a single column
        $pk_name = $dbTable->info(Zend_Db_Table::PRIMARY)[1];
        $pk_value = $entity->getId();
        
        if ( null === $pk_value )
        {
            $pk_value = $dbTable->insert($data);
            $entity->setId($pk_value);
        }
        else
        {
            $dbTable->update($data, array(
                $pk_name . ' = ?' => $pk_value
            ));
        }
    }

}

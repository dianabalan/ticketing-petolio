<?php

class Petolio_Model_Ticket_Entity
{

    private $_id;

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = (int) $id;
        return $this;
    }

}

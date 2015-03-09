<?php

class Petolio_Model_Ticket_NonPetolioMember extends Petolio_Model_Ticket_User
{
    private $_remarks;

    public function __construct()
    {
        parent::__construct();
        $this->setType(3);
    }
    
    public function getRemarks()
    {
        return $this->_remarks;
    }
    
    public function setRemarks($remarks)
    {
        $this->_remarks = $remarks;
        return $this;
    }
}

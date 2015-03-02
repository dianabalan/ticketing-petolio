<?php

class Petolio_Model_Ticket_Client extends Petolio_Model_Ticket_Entity
{

    private $_spId;

    private $_clientId;

    private $_clientTypeId;

    private $_clientNo;

    private $_remarks;

    private $_billingInterval;

    private $_payment;

    private $_isActive;

    private $_dateCreated;

    private $_dateModified;

    public function getSpId()
    {
        return $this->_spId;
    }

    public function setSpId($spId)
    {
        $this->_spId = $spId;
        return $this;
    }

    public function getClientId()
    {
        return $this->_clientId;
    }

    public function setClientId($clientId)
    {
        $this->_clientId = $clientId;
        return $this;
    }

    public function getClientTypeId()
    {
        return $this->_clientTypeId;
    }

    public function setClientTypeId($clientTypeId)
    {
        $this->_clientTypeId = $clientTypeId;
        return $this;
    }

    public function getClientNo()
    {
        return $this->_clientNo;
    }

    public function setClientNo($clientNo)
    {
        $this->_clientNo = $clientNo;
        return $this;
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

    public function getBillingInterval()
    {
        return $this->_billingInterval;
    }

    public function setBillingInterval($billingInterval)
    {
        $this->_billingInterval = $billingInterval;
        return $this;
    }

    public function getPayment()
    {
        return $this->_payment;
    }

    public function setPayment($payment)
    {
        $this->_payment = $payment;
        return $this;
    }

    public function getIsActive()
    {
        return $this->_isActive;
    }

    public function setIsActive($isActive)
    {
        $this->_isActive = $isActive;
        return $this;
    }

    public function getDateCreated()
    {
        return $this->_dateCreated;
    }

    public function setDateCreated($dateCreated)
    {
        $this->_dateCreated = $dateCreated;
        return $this;
    }

    public function getDateModified()
    {
        return $this->_dateModified;
    }

    public function setDateModified($dateModified)
    {
        $this->_dateModified = $dateModified;
        return $this;
    }

}

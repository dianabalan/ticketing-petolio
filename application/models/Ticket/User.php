<?php

class Petolio_Model_Ticket_User extends Petolio_Model_Ticket_Entity
{

    private $_name;

    private $_email;

    private $_address;

    private $_location;

    private $_countryId;

    private $_zipcode;

    private $_type;

    private $_avatar;

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getEmail()
    {
        return $this->_email;
    }

    public function setEmail($email)
    {
        $this->_email = $email;
        return $this;
    }

    public function getAddress()
    {
        return $this->_address;
    }

    public function setAddress($address)
    {
        $this->_address = $address;
        return $this;
    }

    public function getLocation()
    {
        return $this->_location;
    }

    public function setLocation($location)
    {
        $this->_location = $location;
        return $this;
    }

    public function getCountryId()
    {
        return $this->_countryId;
    }

    public function setCountryId($countryId)
    {
        $this->_countryId = $countryId;
        return $this;
    }

    public function getZipcode()
    {
        return $this->_zipcode;
    }

    public function setZipcode($zipcode)
    {
        $this->_zipcode = $zipcode;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($type)
    {
        if ( null !== $type )
        {
            $type = (int) $type;
        }
        
        $this->_type = $type;
        return $this;
    }

    public function getAvatar()
    {
        return $this->_avatar;
    }

    public function setAvatar($avatar)
    {
        $this->_avatar = $avatar;
        return $this;
    }

}

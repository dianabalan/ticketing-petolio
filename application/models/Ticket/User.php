<?php

class Petolio_Model_Ticket_User extends Petolio_Model_Ticket_Entity
{

    private $_name;

    private $_firstname;

    private $_lastname;

    private $_email;

    private $_street;

    private $_address;

    private $_location;

    private $_countryId;

    private $_zipcode;

    private $_type;

    private $_avatar;

    private $_phone;

    private $_privatePhone;

    private $_gender;

    private $_dateModified;

    public function __construct()
    {
        parent::__construct();
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getFirstname()
    {
        return $this->_firstname;
    }

    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;
        return $this;
    }

    public function getLastname()
    {
        return $this->_lastname;
    }

    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;
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

    public function getStreet()
    {
        return $this->_street;
    }

    public function setStreet($street)
    {
        $this->_street = $street;
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

    public function getPhone()
    {
        return $this->_phone;
    }

    public function setPhone($phone)
    {
        $this->_phone = $phone;
        return $this;
    }

    public function getPrivatePhone()
    {
        return $this->_privatePhone;
    }

    public function setPrivatePhone($privatePhone)
    {
        $this->_privatePhone = $privatePhone;
        return $this;
    }

    public function getGender()
    {
        return $this->_gender;
    }

    public function setGender($gender)
    {
        $this->_gender = $gender;
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

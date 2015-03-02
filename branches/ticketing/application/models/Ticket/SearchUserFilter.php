<?php

class Petolio_Model_Ticket_SearchUserFilter
{

    private $_keyword;

    private $_country;

    private $_zipcode;

    private $_address;

    private $_location;

    private $_radius;

    public function getKeyword()
    {
        return $this->_keyword;
    }

    public function setKeyword($keyword)
    {
        $this->_keyword = $keyword;
        return $this;
    }

    public function getCountry()
    {
        return $this->_country;
    }

    public function setCountry($country)
    {
        $this->_country = $country;
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

    public function getRadius()
    {
        return $this->_radius;
    }

    public function setRadius($radius)
    {
        if ( null !== $radius )
        {
            $radius = (int) $radius;
        }
        
        $this->_radius = $radius;
        return $this;
    }

    public function toArray()
    {
        $data = array(
            'keyword' => $this->getKeyword(),
            'country' => $this->getCountry(),
            'zipcode' => $this->getZipcode(),
            'address' => $this->getAddress(),
            'location' => $this->getLocation(),
            'radius' => $this->getRadius()
        );
        
        return $data;
    }

    public function hasValues()
    {
        foreach ($this->toArray() as $key => $value)
        {
            if ( null !== $value )
            {
                return true;
            }
        }
        
        return false;
    }

}

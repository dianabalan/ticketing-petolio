<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoServiceMembersPets extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Id;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_ServiceId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_PetId;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Status;

    /**
     * @var Petolio_Model_PoPets
     */
	protected $_MemberPet;

    /**
     * @var Petolio_Model_PoServices
     */
	protected $_MemberService;


function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'service_id'=>'ServiceId',
    'pet_id'=>'PetId',
    'status'=>'Status',
    ));
}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersPets
     *
     **/

    public function setId($data)
    {
        $this->_Id=$data;
        return $this;
    }

    /**
     * gets column id type bigint(20)
     * @return int
     */

    public function getId()
    {
        return $this->_Id;
    }

    /**
     * sets column service_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersPets
     *
     **/

    public function setServiceId($data)
    {
        $this->_ServiceId=$data;
        return $this;
    }

    /**
     * gets column service_id type bigint(20)
     * @return int
     */

    public function getServiceId()
    {
        return $this->_ServiceId;
    }

    /**
     * sets column pet_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersPets
     *
     **/

    public function setPetId($data)
    {
        $this->_PetId=$data;
        return $this;
    }

    /**
     * gets column pet_id type bigint(20)
     * @return int
     */

    public function getPetId()
    {
        return $this->_PetId;
    }

    /**
     * sets column status type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersPets
     *
     **/

    public function setStatus($data)
    {
        $this->_Status=$data;
        return $this;
    }

    /**
     * gets column status type tinyint(1)
     * @return int
     */

    public function getStatus()
    {
        return $this->_Status;
    }

    /**
     * sets the _MemberPet obj
     * @param Petolio_Model_PoPets $member_pet
     * @throws Exception
     */
    public function setMemberPet($member_pet = null) {
    	if ( !isset($member_pet) ) {
	    	if ( !$this->getPetId() ) {
	            throw new Exception('Pet id it\'s not set');
	    	}
	    	$member_pet = new Petolio_Model_PoPets();
	    	$member_pet->find($this->getPetId());
    	}
    	if ( !$member_pet instanceof Petolio_Model_PoPets ) {
    		throw new Exception('Invalid instance for $member_pet object, Petolio_Model_PoPets expected.');
    	}
	   	$this->_MemberPet = $member_pet;
	   	return $this;
    }

    /**
     * returns the member pet obj
     * @return Petolio_Model_PoPets
     */
    public function getMemberPet() {
    	if ( !isset($this->_MemberPet) ) {
    		$this->setMemberPet();
    	}
    	return $this->_MemberPet;
    }

    /**
     * sets the _MemberService obj
     * @param Petolio_Model_PoServices $member_service
     * @throws Exception
     */
    public function setMemberService($member_service = null) {
    	if ( !isset($member_service) ) {
	    	if ( !$this->getServiceId() ) {
	            throw new Exception('Service id it\'s not set');
	    	}
	    	$member_service = new Petolio_Model_PoServices();
	    	$member_service->find($this->getServiceId());
    	}
    	if ( !$member_service instanceof Petolio_Model_PoServices ) {
    		throw new Exception('Invalid instance for $member_service object, Petolio_Model_PoServices expected.');
    	}
	   	$this->_MemberService = $member_service;
	   	return $this;
    }

    /**
     * returns the member service obj
     * @return Petolio_Model_PoServices
     */
    public function getMemberService() {
    	if ( !isset($this->_MemberService) ) {
    		$this->setMemberService();
    	}
    	return $this->_MemberService;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoServiceMembersPetsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoServiceMembersPetsMapper());
        }
        return $this->_mapper;
    }

    /**
     * deletes current row by deleting a row that matches the primary key
     *
     * @return int
     */

    public function deleteRowByPrimaryKey()
    {
        if (!$this->getId())
            throw new Exception('Primary Key does not contain a value');
        return $this->getMapper()->getDbTable()->delete('id = '.$this->getId());
    }

    public function getServiceMembersPets($service_id, $status, $limit = null) {
    	if ( !isset($service_id) && $this->getServiceId() ) {
    		$service_id = $this->getServiceId();
    	}
    	if ( !isset($service_id) ) {
    		throw new Exception('Service id is not set.');
    	}
    	return $this->getMapper()->getDbTable()->getServiceMembersPets($service_id, $status, $limit);
    }

    /**
     * gets an array of all the service providers with who the specified pet has links
     * @return array of Petolio_Model_PoUsers
     * @param int $pet_id
     */
    public function getPetServiceProviders($pet_id) {
    	$result = array();
    	foreach ($this->getMapper()->getDbTable()->getPetServiceProviders($pet_id) as $line) {
			$user = new Petolio_Model_PoUsers();
			$user->setId($line['user_id'])
				->setName($line['user_name'])
				->setEmail($line['user_email'])
				->setPassword($line['user_password'])
				->setActive($line['user_active'])
				->setStreet($line['user_street'])
				->setAddress($line['user_address'])
				->setZipcode($line['user_zipcode'])
				->setLocation($line['user_location'])
				->setCountryId($line['user_country_id'])
				->setPhone($line['user_phone'])
				->setHomepage($line['user_homepage'])
				->setGender($line['user_gender'])
				->setDateOfBirth($line['user_date_of_birth'])
				->setGpsLatitude($line['user_gps_latitude'])
				->setGpsLongitude($line['user_gps_longitude'])
				->setDateForgot($line['user_date_forgot'])
				->setAvatar($line['user_avatar'])
				->setDateCreated($line['user_date_created'])
				->setDateModified($line['user_date_modified'])
				->setType($line['user_type']);

			$result[] = $user;
    	}
    	return $result;
    }

    /**
     * gets the specified pet services
     * @return array of Petolio_Model_PoServices
     * @param int $pet_id
     */
    public function getPetServices($pet_id, $status = null) {
    	$result = array();
    	foreach ($this->getMapper()->getDbTable()->getPetServices($pet_id, $status) as $line) {
			$service = new Petolio_Model_PoServices();
			$service->setId($line['service_id'])
				->setUserId($line['service_user_id'])
				->setAttributeSetId($line['service_attribute_set_id'])
				->setMembersLimit($line['service_members_limit'])
				->setDateCreated($line['service_date_created'])
				->setDateModified($line['service_date_modified']);

			$result[] = $service;
    	}
    	return $result;
    }

    /**
     * gets the specified pet services with references
     *
     * @param int $pet_id
     * @param int or array $status
     * @param string $order
     * @return array of Petolio_Model_PoServiceMembersPets
     */
    public function getPetServicesWithReferences($pet_id, $status = null, $order = null) {
    	$result = array();
    	foreach ($this->getMapper()->getDbTable()->getPetServicesWithReferences($pet_id, $status, $order) as $line) {
    		$owner = new Petolio_Model_PoUsers();
			$owner->setId($line['user_id'])
				->setName($line['user_name'])
				->setFirstName($line['user_first_name'])
				->setLastName($line['user_last_name'])
				->setEmail($line['user_email'])
				->setPassword($line['user_password'])
				->setActive($line['user_active'])
				->setStreet($line['user_street'])
				->setAddress($line['user_address'])
				->setZipcode($line['user_zipcode'])
				->setLocation($line['user_location'])
				->setCountryId($line['user_country_id'])
				->setPhone($line['user_phone'])
				->setHomepage($line['user_homepage'])
				->setGender($line['user_gender'])
				->setDateOfBirth($line['user_date_of_birth'])
				->setGpsLatitude($line['user_gps_latitude'])
				->setGpsLongitude($line['user_gps_longitude'])
				->setDateForgot($line['user_date_forgot'])
				->setAvatar($line['user_avatar'])
				->setDateCreated($line['user_date_created'])
				->setDateModified($line['user_date_modified'])
				->setType($line['user_type'])
				->setCountryName($line['user_country']);

			$service = new Petolio_Model_PoServices();
			$service->setId($line['service_id'])
				->setUserId($line['service_user_id'])
				->setAttributeSetId($line['service_attribute_set_id'])
				->setMembersLimit($line['service_members_limit'])
				->setDateCreated($line['service_date_created'])
				->setDateModified($line['service_date_modified'])
				->setName($line['service_name'])
				->setAddress($line['service_address'])
				->setZipcode($line['service_zipcode'])
				->setLocation($line['service_location'])
				->setCountry($line['service_country'])
				->setAttributeSetName($line['service_type'])
				->setOwner($owner);

			$members_pets = new Petolio_Model_PoServiceMembersPets();
			$members_pets->setId($line['id'])
				->setServiceId($line['service_id'])
				->setPetId($line['pet_id'])
				->setStatus($line['status'])
				->setMemberService($service);

			$result[] = $members_pets;
    	}
    	return $result;
    }

    /**
     * return the service owner and the pet owner of the specified link
     * @param int $link_id
     * @return array(
     * 		'pet_owner' => Petolio_Model_PoUsers,
     * 		'service_owner' => Petolio_Model_PoUsers
     * 		'pet_id' => int
     * 		'service_id' => int)
     */
    public function getLinkOwners($link_id) {
    	$result = array();
    	foreach ($this->getMapper()->getDbTable()->getLinkOwners($link_id) as $line) {
    		$pet_owner = new Petolio_Model_PoUsers();
			$pet_owner->setId($line['pet_user_id'])
				->setName($line['pet_user_name'])
				->setEmail($line['pet_user_email'])
				->setPassword($line['pet_user_password'])
				->setActive($line['pet_user_active'])
				->setStreet($line['pet_user_street'])
				->setAddress($line['pet_user_address'])
				->setZipcode($line['pet_user_zipcode'])
				->setLocation($line['pet_user_location'])
				->setCountryId($line['pet_user_country_id'])
				->setPhone($line['pet_user_phone'])
				->setHomepage($line['pet_user_homepage'])
				->setGender($line['pet_user_gender'])
				->setDateOfBirth($line['pet_user_date_of_birth'])
				->setGpsLatitude($line['pet_user_gps_latitude'])
				->setGpsLongitude($line['pet_user_gps_longitude'])
				->setDateForgot($line['pet_user_date_forgot'])
				->setAvatar($line['pet_user_avatar'])
				->setDateCreated($line['pet_user_date_created'])
				->setDateModified($line['pet_user_date_modified'])
				->setType($line['pet_user_type']);

    		$service_owner = new Petolio_Model_PoUsers();
			$service_owner->setId($line['service_user_id'])
				->setName($line['service_user_name'])
				->setEmail($line['service_user_email'])
				->setPassword($line['service_user_password'])
				->setActive($line['service_user_active'])
				->setStreet($line['service_user_street'])
				->setAddress($line['service_user_address'])
				->setZipcode($line['service_user_zipcode'])
				->setLocation($line['service_user_location'])
				->setCountryId($line['service_user_country_id'])
				->setPhone($line['service_user_phone'])
				->setHomepage($line['service_user_homepage'])
				->setGender($line['service_user_gender'])
				->setDateOfBirth($line['service_user_date_of_birth'])
				->setGpsLatitude($line['service_user_gps_latitude'])
				->setGpsLongitude($line['service_user_gps_longitude'])
				->setDateForgot($line['service_user_date_forgot'])
				->setAvatar($line['service_user_avatar'])
				->setDateCreated($line['service_user_date_created'])
				->setDateModified($line['service_user_date_modified'])
				->setType($line['service_user_type']);

			$result['pet_owner'] = $pet_owner;
			$result['service_owner'] = $service_owner;
			$result['pet_id'] = $line['pet_id'];
    		$result['service_id'] = $line['service_id'];
    	}
    	return $result;
    }
}


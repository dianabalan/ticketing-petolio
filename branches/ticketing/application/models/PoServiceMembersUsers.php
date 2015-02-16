<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoServiceMembersUsers extends MainModel
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
    protected $_UserId;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Status;
    
    /**
     * @var Petolio_Model_PoUsers
     */
	protected $_MemberUser;
    
    /**
     * @var Petolio_Model_PoServices
     */
	protected $_MemberService;
    
    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'service_id'=>'ServiceId',
    'user_id'=>'UserId',
    'status'=>'Status',
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersUsers     
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
     * @return Petolio_Model_PoServiceMembersUsers     
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
     * sets column user_id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersUsers     
     *
     **/

    public function setUserId($data)
    {
        $this->_UserId=$data;
        return $this;
    }

    /**
     * gets column user_id type bigint(20)
     * @return int     
     */
     
    public function getUserId()
    {
        return $this->_UserId;
    }
    
    /**
     * sets column status type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoServiceMembersUsers     
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
     * sets the _MemberUser obj
     * @param Petolio_Model_PoUsers $member_user
     * @throws Exception
     */
    public function setMemberUser($member_user = null) {
    	if ( !isset($member_user) ) {
	    	if ( !$this->getUserId() ) {
	            throw new Exception('User id it\'s not set');
	    	}
	    	$member_user = new Petolio_Model_PoUsers();
	    	$member_user->find($this->getUserId());
    	}
    	if ( !$member_user instanceof Petolio_Model_PoUsers ) {
    		throw new Exception('Invalid instance for $member_user object, Petolio_Model_PoUsers expected.');
    	}
	   	$this->_MemberUser = $member_user;
	   	return $this;
    }
    
    /**
     * returns the member user obj
     * @return Petolio_Model_PoUsers
     */
    public function getMemberUser() {
    	if ( !isset($this->_MemberUser) ) {
    		$this->setMemberUser();
    	}
    	return $this->_MemberUser;
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
     * @return Petolio_Model_PoServiceMembersUsersMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoServiceMembersUsersMapper());
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


    public function getServiceMembersUsers($service_id, $status, $limit = null) {
    	if ( !isset($service_id) && $this->getServiceId() ) {
    		$service_id = $this->getServiceId();
    	}
    	if ( !isset($service_id) ) {
    		throw new Exception('Service id is not set.');
    	}
    	return $this->getMapper()->getDbTable()->getServiceMembersUsers($service_id, $status, $limit);
    }
    
    /**
	 * loads a all the member services with references in which the user is member
	 * 
	 * @param int $user_id
	 * @param int or array $status
	 * @param string $order
     * @return array of Petolio_Model_PoServiceMembersUsers
	 */
	public function getUserServicesWithReferences($user_id, $status = null, $order = null) {
    	$result = array();
    	foreach ($this->getMapper()->getDbTable()->getUserServicesWithReferences($user_id, $status, $order) as $line) {
    		$owner = new Petolio_Model_PoUsers();
			$owner->setId($line['user_id'])
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
				
			$members_users = new Petolio_Model_PoServiceMembersUsers();
			$members_users->setId($line['id'])
				->setServiceId($line['service_id'])
				->setUserId($line['user_id'])
				->setStatus($line['status'])
				->setMemberService($service);
				
			$result[] = $members_users;
    	}
    	return $result;
		
	}
    
    /**
     * return the service owner and the member user of the specified link
     * @param int $link_id
     * @return array(
     * 		'member' => Petolio_Model_PoUsers, 
     * 		'service_owner' => Petolio_Model_PoUsers
     * 		'user_id' => int
     * 		'service_id' => int)
     */
    public function getLinkOwners($link_id) {
    	$result = array();
    	foreach ($this->getMapper()->getDbTable()->getLinkOwners($link_id) as $line) {
    		$member = new Petolio_Model_PoUsers();
			$member->setId($line['member_user_id'])
				->setName($line['member_user_name'])
				->setEmail($line['member_user_email'])
				->setPassword($line['member_user_password'])
				->setActive($line['member_user_active'])
				->setStreet($line['member_user_street'])
				->setAddress($line['member_user_address'])
				->setZipcode($line['member_user_zipcode'])
				->setLocation($line['member_user_location'])
				->setCountryId($line['member_user_country_id'])
				->setPhone($line['member_user_phone'])
				->setHomepage($line['member_user_homepage'])
				->setGender($line['member_user_gender'])
				->setDateOfBirth($line['member_user_date_of_birth'])
				->setGpsLatitude($line['member_user_gps_latitude'])
				->setGpsLongitude($line['member_user_gps_longitude'])
				->setDateForgot($line['member_user_date_forgot'])
				->setAvatar($line['member_user_avatar'])
				->setDateCreated($line['member_user_date_created'])
				->setDateModified($line['member_user_date_modified'])
				->setType($line['member_user_type']);
				
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
				
			$result['member'] = $member;
			$result['service_owner'] = $service_owner;
			$result['user_id'] = $line['user_id'];
    		$result['service_id'] = $line['service_id'];
    	}
    	return $result;
    }
}


<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoNotifications extends MainModel
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
    protected $_UserId;

	protected $_AuthorId;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Scope;

    /**
     * mysql var type varchar(200)
     *
     * @var varchar
     */
    protected $_Data;

    /**
     * mysql var type bigint(20)
     *
     * @var int
     */
    protected $_Status;

	/**
	 * mysql var type timestamp
	 *
	 * @var string
	 */
	protected $_DateCreated;




	function __construct() {
	    $this->setColumnsList(array(
		    'id'=>'Id',
		    'user_id'=>'UserId',
		    'author_id'=>'AuthorId',
		    'scope'=>'Scope',
		    'data'=>'Data',
		    'status'=>'Status',
			'date_created'=>'DateCreated'
	    ));
	}



    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoNotifications
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
     * sets column user_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoNotifications
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

    public function setAuthorId($data){ $this->_AuthorId=$data; return $this; }
    public function getAuthorId() { return $this->_AuthorId; }

    /**
     * sets column folder_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoNotifications
     *
     **/

    public function setScope($data)
    {
        $this->_Scope=$data;
        return $this;
    }

    /**
     * gets column folder_id type bigint(20)
     * @return int
     */

    public function getScope()
    {
        return $this->_Scope;
    }

    /**
     * sets column url type varchar(200)
     *
     * @param varchar $data
     * @return Petolio_Model_PoNotifications
     *
     **/

    public function setData($data)
    {
        $this->_Data = $data;
        return $this;
    }

    /**
     * gets column url type varchar(200)
     * @return varchar
     */

    public function getData()
    {
        return $this->_Data;
    }

    /**
     * sets column template_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoNotifications
     *
     **/

    public function setStatus($data)
    {
        $this->_Status=$data;
        return $this;
    }

    /**
     * gets column template_id type bigint(20)
     * @return int
     */

    public function getStatus()
    {
        return $this->_Status;
    }

	/**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoNotifications
	 *
	 **/

	public function setDateCreated($data)
	{
		$this->_DateCreated=$data;
		return $this;
	}

	/**
	 * gets column date_created type timestamp
	 * @return string
	 */

	public function getDateCreated()
	{
		return $this->_DateCreated;
	}

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoNotificationsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoNotificationsMapper());
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

    /**
	 * Get petolio notifications complete with owner name and avatar
     *
	 * @param string $type - array or paginator
     * @param string $where
     * @param string $order
     * @param string $limit
     *
     * @return either array or paginator
     */
    public function getNotifications($type = 'array', $where = false, $order = false, $limit = false) {
    	$db = $this->getMapper()->getDbTable();

    	// main query
    	$select = $db->select()->setIntegrityCheck(false)
    	->from(array('a' => 'po_notifications'), array('*'))

    	// get entry owner
    	->joinLeft(array('x' => 'po_users'), "a.author_id = x.id", array('user_name' => 'name', 'user_avatar' => 'avatar'));

		// make sure the user is active and not banned
		$select->where("x.active = 1 AND x.is_banned != 1");

    	// filter and sort and limit ? ok
    	if($where) $select->where($where);
    	if($order) $select->order($order);
		if($limit) $select->limit($limit[0], $limit[1]);

		// group by entry id
		$select->group('a.id');

    	// return either array or paginator
    	return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
    }
    
    public function countNewAnswers($user_id = null) {
    	if ( !isset($user_id) ) {
    		return 0;
    	}
    	
    	$where = "scope = 'question' AND status = 0 AND user_id = ".$user_id;
    	$new_answers = $this->countByQuery($where);
    	
    	if ( intval($new_answers) > 0 ) {
    		// get user questions
    		$questions = new Petolio_Model_PoHelp();
    		$your_questions = $questions->fetchList("user_id = ".$user_id);
    		$your_question_ids = array();
    		foreach ($your_questions as $question) {
    			array_push($your_question_ids, $question->id);
    		}
    	
    		$your_notifications = $this->fetchList($where);
    		$new_answers = 0;
    		foreach ($your_notifications as $notif) {
    			$data = unserialize($notif->data);
    				
    			$url = $data[1];
    			$url_params = explode('/', $url);
    			$question_id = $url_params[count($url_params) - 1];
    	
    			if ( in_array($question_id, $your_question_ids) ) {
    				$new_answers++;
    			}
    		}
    	}
		return $new_answers;
    }
}
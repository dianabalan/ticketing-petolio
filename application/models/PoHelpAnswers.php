<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoHelpAnswers extends MainModel
{

	/**
	 * mysql var type bigint(20)
	 *
	 * @var int
	 */
	protected $_Id;

	protected $_HelpId;

	/**
	 * mysql var type bigint(20)
	 *
	 * @var int
	 */
	protected $_UserId;

	/**
	 * mysql var type bigint(20)
	 *
	 * @var int
	 */
	protected $_Answer;

	/**
	 * mysql var type timestamp
	 *
	 * @var string
	 */
	protected $_DateCreated;

	/**
	 * mysql var type timestamp
	 *
	 * @var string
	 */
	protected $_DateModified;

	function __construct() {
		$this->setColumnsList(array(
		    'id'=>'Id',
		    'help_id'=>'HelpId',
		    'user_id'=>'UserId',
		    'answer'=>'Answer',
		    'date_created'=>'DateCreated',
		    'date_modified'=>'DateModified'
		));
	}



	/**
	 * sets column id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoHelpAnswers
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

    public function setHelpId($data) { $this->_HelpId = $data; return $this; }
    public function getHelpId() { return $this->_HelpId; }

	/**
	 * sets column user_id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoHelpAnswers
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
	 * sets column attribute_set_id type bigint(20)
	 *
	 * @param int $data
	 * @return Petolio_Model_PoHelpAnswers
	 *
	 **/

	public function setAnswer($data)
	{
		$this->_Answer=$data;
		return $this;
	}

	/**
	 * gets column attribute_set_id type bigint(20)
	 * @return int
	 */

	public function getAnswer()
	{
		return $this->_Answer;
	}

	/**
	 * sets column date_created type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoHelpAnswers
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
	 * sets column date_modified type timestamp
	 *
	 * @param string $data
	 * @return Petolio_Model_PoHelpAnswers
	 *
	 **/

	public function setDateModified($data)
	{
		$this->_DateModified=$data;
		return $this;
	}

	/**
	 * gets column date_modified type timestamp
	 * @return string
	 */

	public function getDateModified()
	{
		return $this->_DateModified;
	}


	/**
	 * returns the mapper class
	 *
	 * @return Petolio_Model_PoHelpAnswersMapper
	 *
	 */

	public function getMapper()
	{
		if (null === $this->_mapper) {
			$this->setMapper(new Petolio_Model_PoHelpAnswersMapper());
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
	 * Get answers list
	 * 		- including filter and sorting options
	 *
	 * @param string $type - paginator or array
	 * @param string $where
	 * @param string $order
	 * @param array $limit
	 *
	 * @return either array or paginator
	 */
	public function getAnswers($type = 'array', $where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('a' => 'po_help_answers'), array('*'))

			// get question owner
			->joinLeft(array('q' => 'po_help'), "a.help_id = q.id", array('question_user_id' => 'user_id'))

			// get answer owner
			->joinLeft(array('x' => 'po_users'), "a.user_id = x.id", 
					array(
						'user_name' => 'name', 
						'user_email' => 'email', 
						'user_avatar' => 'avatar',
						'user_other_email_notification' => 'other_email_notification'
			));

		// group by pet
		$select->group("a.id");

		if($where) $select->where($where);
		if($order) {
			if (strpos($order, ",") > 0) {
				$tokens = explode(",", $order);
				foreach ($tokens as $token) {
					$select->order($token);
				}
			} else {
				$select->order($order);
			}
		}
		if($limit) $select->limit($limit);

		// return either array or paginator
		return $type == 'paginator' ? $this->select2Paginator($select) : $db->fetchAll($select)->toArray();
	}
}
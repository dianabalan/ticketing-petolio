<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */

class Petolio_Model_PoPedigree extends MainModel
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
    protected $_PetId;

    protected $_Name;
    protected $_PetIdLinked;

    /**
     * mysql var type tinyint(1)
     *
     * @var int
     */
    protected $_Level;


	function __construct() {
	    $this->setColumnsList(array(
		    'id'=>'Id',
		    'pet_id'=>'PetId',
		    'name'=>'Name',
		    'pet_id_linked'=>'PetIdLinked',
		    'level'=>'Level'
	    ));
	}

    /**
     * sets column id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoPedigree
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
     * sets column pet_id type bigint(20)
     *
     * @param int $data
     * @return Petolio_Model_PoPedigree
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

    public function setName($data){ $this->_Name = $data; return $this; }
    public function getName() { return $this->_Name; }

    public function setPetIdLinked($data){ $this->_PetIdLinked = $data; return $this; }
    public function getPetIdLinked() { return $this->_PetIdLinked; }

    /**
     * sets column level type tinyint(1)
     *
     * @param int $data
     * @return Petolio_Model_PoPedigree
     *
     **/

    public function setLevel($data)
    {
        $this->_Level=$data;
        return $this;
    }

    /**
     * gets column level type tinyint(1)
     * @return int
     */

    public function getLevel()
    {
        return $this->_Level;
    }

    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoPedigreeMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoPedigreeMapper());
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
	 * Get pedigree list complete with name and level and status
	 * 		- including filter and sorting options
	 *
	 * @param string $where
	 * @param string $order
	 * @param string $limit
	 *
	 * @return either array
	 */
	public function getPedigree($where = false, $order = false, $limit = false) {
		$db = $this->getMapper()->getDbTable();

		// main query
		$select = $db->select()->setIntegrityCheck(false)
			->from(array('x' => 'po_pedigree'), array('id', 'level', 'pet_id_linked',
				'type' => new Zend_Db_Expr("CASE WHEN d1.value IS NULL THEN 0 ELSE 1 END"),
				'name' => new Zend_Db_Expr("CASE WHEN d1.value IS NULL THEN x.name ELSE d1.value END")
			))

			// get pet
			->joinLeft(array('a' => 'po_pets'), "x.pet_id_linked = a.id", array())

			// get pet name
			->joinLeft(array('c1' => 'po_attributes'), "c1.attribute_set_id = a.attribute_set_id AND SUBSTRING(c1.code, -5) = '_name'", array())
			->joinLeft(array('d1' => 'po_attribute_entity_varchar'), "d1.attribute_id = c1.id AND a.id = d1.entity_id", array());

		// filter and sort and limit ? ok
		if($where) $select->where($where);
		if($order) $select->order($order);
		if($limit) $select->limit($limit);

		// return array
		return $db->fetchAll($select)->toArray();
	}
}
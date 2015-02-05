<?php
require_once('MainModel.php');

/**
 * Add your description here
 *
 * @author Riffcode
 * @copyright Riffcode
 * @license http://riffcode.eu
 */
 
class Petolio_Model_PoAttributeSets extends MainModel
{

    /**
     * mysql var type bigint(20)
     *
     * @var int     
     */
    protected $_Id;
    
    /**
     * mysql var type varchar(100)
     *
     * @var string     
     */
    protected $_Name;
    
    /**
     * mysql var type varchar(100)
     *
     * @var string     
     */
    protected $_Scope;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Active;
    
    /**
     * mysql var type tinyint(1)
     *
     * @var int     
     */
    protected $_Type;
    
    /**
     * mysql var type varchar(200)
     * 
     * @var string
     */
    protected $_GroupName;
    
    /**
	 * mysql var type varchar(500)
	 *
	 * @var string
     */
    protected $_Description;
    

function __construct() {
    $this->setColumnsList(array(
    'id'=>'Id',
    'name'=>'Name',
    'scope'=>'Scope',
    'active'=>'Active',
    'type'=>'Type',
    'group_name'=>'GroupName',
    'description'=>'Description'
    ));
}

	
    
    /**
     * sets column id type bigint(20)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeSets     
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
     * sets column name type varchar(100)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeSets     
     *
     **/

    public function setName($data)
    {
        $this->_Name=$data;
        return $this;
    }

    /**
     * gets column name type varchar(100)
     * @return string     
     */
     
    public function getName()
    {
        return $this->_Name;
    }
    
    /**
     * sets column scope type varchar(100)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeSets     
     *
     **/

    public function setScope($data)
    {
        $this->_Scope=$data;
        return $this;
    }

    /**
     * gets column scope type varchar(100)
     * @return string     
     */
     
    public function getScope()
    {
        return $this->_Scope;
    }
    
    /**
     * sets column active type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeSets     
     *
     **/

    public function setActive($data)
    {
        $this->_Active=$data;
        return $this;
    }

    /**
     * gets column active type tinyint(1)
     * @return int     
     */
     
    public function getActive()
    {
        return $this->_Active;
    }
    
    /**
     * sets column type type tinyint(1)     
     *
     * @param int $data
     * @return Petolio_Model_PoAttributeSets     
     *
     **/

    public function setType($data)
    {
        $this->_Type = $data;
        return $this;
    }

    /**
     * gets column type type tinyint(1)
     * @return int     
     */
     
    public function getType()
    {
        return $this->_Type;
    }
    
    /**
     * sets column group_name type varchar(200)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeSets     
     *
     **/
    public function setGroupName($data) {
        $this->_GroupName = $data;
        return $this;
    }

    /**
     * gets column group_name type varchar(200)
     * @return string   
     */
    public function getGroupName() {
        return $this->_GroupName;
    }
    
    /**
     * sets column description type varchar(500)     
     *
     * @param string $data
     * @return Petolio_Model_PoAttributeSets     
     *
     **/
    public function setDescription($data) {
        $this->_Description = $data;
        return $this;
    }

    /**
     * gets column description type varchar(500)
     * @return string   
     */
    public function getDescription() {
        return $this->_Description;
    }
    
    /**
     * returns the mapper class
     *
     * @return Petolio_Model_PoAttributeSetsMapper
     *
     */

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Petolio_Model_PoAttributeSetsMapper());
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

}


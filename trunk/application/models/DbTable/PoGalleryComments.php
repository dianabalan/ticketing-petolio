<?php

class Petolio_Model_DbTable_PoGalleryComments extends Zend_Db_Table_Abstract {

    protected $_name = 'po_gallery_comments';
	protected $_primary = 'id';

	protected $_referenceMap    = array(
        'PoGalleries' => array(
            'columns'           => array('gallery_id'),
            'refTableClass'     => 'PoGalleries',
            'refColumns'        => array('id')
        ),
        'PoUsers' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'PoUsers',
            'refColumns'        => array('id')
        )
	);

}

?>
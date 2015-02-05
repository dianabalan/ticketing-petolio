<?php

class IndexController extends Zend_Controller_Action
{
    private $config = null;
    private $translate = null;

    public function init()
    {
        /* Initialize action controller here */
    	$this->config = Zend_Registry::get("config");
		$this->translate = Zend_Registry::get('Zend_Translate');
	}

    public function indexAction()
    {
    	$jsonp = false;
    	if(isset($_GET['jsonp']) && $_GET['jsonp'] = 1)
			$jsonp = true;

		// ?
    	if(strlen($this->getRequest()->getParam("url", "")) > 0) {
	        $content_distributions = new Petolio_Model_PoContentDistributions();
	        $content_distributions->getMapper()->findOneByField("url", $this->getRequest()->getParam("url", ""), $content_distributions);
	        if(!$content_distributions->getId()) {
	        	// if not found do not diplay anything
	        	Zend_Registry::get('Zend_Log')->err("Content distribution not found, searching after url: ".$this->getRequest()->getParam("url", ""));
	        	die('gtfo noob');
	        }

		// ?
    	} else {
			$distribution_tabs = new Petolio_Model_PoContentDistributionTabs();
			$distribution_tabs->getMapper()->findOneByField("tab_id", $this->getRequest()->getParam("tab_id", 0), $distribution_tabs);
			if(!$distribution_tabs->getId()) {
				Zend_Registry::get('Zend_Log')->err("Content distribution tab not found: ".$this->getRequest()->getParam("tab_id", 0));
				die('gtfo noob');
			}

			$content_distributions = new Petolio_Model_PoContentDistributions();
			$content_distributions->find($distribution_tabs->getContentDistributionId());
	        if(!$content_distributions->getId()) {
	        	// if not found do not diplay anything
				Zend_Registry::get('Zend_Log')->err("Content distribution not found: ".$distribution_tabs->getContentDistributionId());
	        	die('gtfo noob');
	        }
    	}

        $attributes = new Petolio_Model_PoAttributes();
        $distribution_attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($content_distributions, true, null, false));

        $this->view->content_distributions = $content_distributions;
        $this->view->distribution_attributes = $distribution_attributes;

        if($distribution_attributes['withmainmenu']->getAttributeEntity()->getValue() == 'Yes'
        		&& !$this->getRequest()->getParam('species')) {
        	// show categories first
        	$attribute_sets = new Petolio_Model_PoAttributeSets();
        	$where = "p.user_id = ".$content_distributions->getUserId();
        	switch ($distribution_attributes['data']->getAttributeEntity()->getValue()) {
        		case "Select categories":
        			$distribution_data = new Petolio_Model_PoContentDistributionData();
        			$category_ids = array(0); // put 0 in case that it's nothing selected
        			foreach ($distribution_data->fetchList("content_distribution_id = ".$content_distributions->getId()) as $data) {
        				array_push($category_ids, $data->getDataId());
        			}
        			$where .= " AND a.id IN (".implode(",", $category_ids).")";
        			$categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount($where);
        			break;
        		case "Pets for adoption":
        			$where .= " AND p.to_adopt = 1";
        			$categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount($where);
        			break;
        		case "Complete adoption market":
        			// where is recreated here, not concatenated
        			$where = "p.to_adopt = 1";
        			$categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount($where);
        			break;
        		case "Complete animal shelter":
        			// get animal shelter category id
        			$cat = new Petolio_Model_PoUsersCategories();
        			$cat = reset($cat->getMapper()->fetchList("name = 'animal shelter'"));
        			$category_id = $cat->getId() ? $cat->getId() : '0';
        			// where is recreated here, not concatenated
        			$where = "p.to_adopt = 1 AND u.category_id = ".$category_id;
        			$categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount($where);
        			break;
        		case "Individual selection":
        			$distribution_data = new Petolio_Model_PoContentDistributionData();
        			$pet_ids = array(0); // put 0 in case that it's nothing selected
        			foreach ($distribution_data->fetchList("content_distribution_id = ".$content_distributions->getId()) as $data) {
        				array_push($pet_ids, $data->getDataId());
        			}
        			$where .= " AND p.id IN (".implode(",", $pet_ids).")";
        			$categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount($where);
        			break;
        		default:
        			// all categories of the user's pets
        			$categories = $attribute_sets->getMapper()->getDbTable()->getAttributeSetsWithPetCount($where);
        			break;
        	}
        	$this->view->categories = $categories;

        } else {
        	// show pets directly
        	$pets = new Petolio_Model_PoPets();

        	// get page
	    	$page = $this->getRequest()->getParam('page');
	    	$page = $page ? intval($page) : 0;

	    	// do sorting 1
	    	$this->view->order = $this->getRequest()->getParam('order');
	    	$this->view->dir = $this->getRequest()->getParam('dir') == 'asc' ? 'asc' : 'desc';
	    	$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

	    	// do sorting 2
	    	if ($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
	    	elseif ($this->view->order == 'description') $sort = "d5.value {$this->view->dir}";
	    	elseif ($this->view->order == 'address') {
				if ($this->translate->getLocale() == 'en')
					$sort = "user_address {$this->view->dir}, user_location {$this->view->dir}, user_zipcode {$this->view->dir}, user_country_id {$this->view->dir}";
				else
					$sort = "user_zipcode {$this->view->dir}, user_address {$this->view->dir}, user_location {$this->view->dir}, user_country_id {$this->view->dir}";
	    	}
	    	else $sort = "id {$this->view->dir}";

        	$where = "1=1";
        	if($this->getRequest()->getParam('species')) {
        		$where .= " AND a.attribute_set_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->getRequest()->getParam('species'));
        		$this->view->species = $this->getRequest()->getParam('species');
        	}
        	switch ($distribution_attributes['data']->getAttributeEntity()->getValue()) {
        		case "Select categories":
        			$distribution_data = new Petolio_Model_PoContentDistributionData();
        			$category_ids = array(0); // put 0 in case that it's nothing selected
        			foreach ($distribution_data->fetchList("content_distribution_id = ".$content_distributions->getId()) as $data) {
        				array_push($category_ids, $data->getDataId());
        			}
        			$where .= " AND a.user_id = ".$content_distributions->getUserId()." AND a.attribute_set_id IN (".implode(",", $category_ids).")";
        			$paginator = $pets->getPets('paginator', $where, $sort, false, false, true);
        			break;
        		case "Pets for adoption":
        			$where .= " AND a.user_id = ".$content_distributions->getUserId()." AND a.to_adopt = 1";
        			$paginator = $pets->getPets('paginator', $where, $sort, false, false, true);
        			break;
        		case "Complete adoption market":
        			$where .= " AND a.to_adopt = 1";
        			$paginator = $pets->getPets('paginator', $where, $sort, false, false, true);
        			break;
        		case "Complete animal shelter":
        			// get animal shelter category id
        			$cat = new Petolio_Model_PoUsersCategories();
        			$cat = reset($cat->getMapper()->fetchList("name = 'animal shelter'"));
        			$category_id = $cat->getId() ? $cat->getId() : '0';
        			// where is recreated here, not concatenated
        			$where .= " AND x.category_id = ".$category_id." AND a.to_adopt = 1";
        			$paginator = $pets->getPets('paginator', $where, $sort, false, false, true);
        			break;
        		case "Individual selection":
        			$distribution_data = new Petolio_Model_PoContentDistributionData();
        			$pet_ids = array(0); // put 0 in case that it's nothing selected
        			foreach ($distribution_data->fetchList("content_distribution_id = ".$content_distributions->getId()) as $data) {
        				array_push($pet_ids, $data->getDataId());
        			}
        			$where .= " AND a.user_id = ".$content_distributions->getUserId()." AND a.id IN (".implode(",", $pet_ids).")";
        			$paginator = $pets->getPets('paginator', $where, $sort, false, false, true);
        			break;

        		default:
        			// all of the user's pets
        			$where .= " AND a.user_id = ".$content_distributions->getUserId();
        			$paginator = $pets->getPets('paginator', $where, $sort, false, false, true);
        			break;
        	}

        	$paginator->setItemCountPerPage(20);
	    	$paginator->setCurrentPageNumber($page);

	    	if($distribution_attributes['design']->getAttributeEntity()->getValue() == 'List') {
	   			$this->view->country_list = array();
				$countries = new Petolio_Model_PoCountries();
				foreach($countries->fetchAll() as $country) {
					$this->view->country_list[$country->getId()] = $country->getName();
				}
	    	}

	    	// output pets
	    	$this->view->pets = $pets->formatPets($paginator);
        }

		// ---------------------------------------------------------------------------------------
		// it's JSONP Time!
		// ---------------------------------------------------------------------------------------
		if($jsonp) {
			header('Content-type: application/json');

			$html = "<h1>{$this->view->distribution_attributes['name']->getAttributeEntity()->getValue()}</h1>" .
				"<br />";

			// main menu
			if($this->view->distribution_attributes['withmainmenu']->getAttributeEntity()->getValue() == 'Yes' && isset($this->view->categories)) {
				$html .= "<table style='margin: 0px auto; width: 450px;'>" .
					"<tr>";

				$i = 0;
				foreach($this->view->categories as $category){ $i++;
					if($i % 2) $html .= "</tr><tr>";
					$html .= "<td style=\"padding: 5px;\">";
					$html .= "<a data-petolio='true' href=\"".$this->view->url(array('controller'=>'index', 'action'=>'index', 'url'=> $this->view->content_distributions->getUrl(), 'species'=>$category['id']), 'distribution', true)."\" class=\"category\">";
					$html .= Petolio_Service_Util::Tr($category['name']);
					$html .= "</a>";
					$html .= "<span class=\"count\">({$category['pet_count']})</span></td>";
				}

				$html .= "</tr></table>";

			// pet list
			} else {
				$html .= $this->view->paginationControl($this->view->pets, 'Elastic', 'index/navigation-controls.phtml', array("pos" => "top", "jsonp" => true));

				if($this->view->distribution_attributes['design']->getAttributeEntity()->getValue() == 'Grid') {
					$html .= "<div class='gallery'>";

					foreach($this->view->pets as $pet) {
						$html .= $this->view->partial('pet-template.phtml', array(
							'translate' => $this->translate,
							'pet' => $pet,
							'jsonp' => true
						));
					}

					$html .= "</div>";
					$html .= "<div class='clear'></div>";
				} else {
					$url = array('controller' => 'index', 'action' => 'index', 'url' => $this->view->content_distributions->getUrl());
					if($this->view->species)
						$url['species'] = $this->view->species;

					$html .= "<table cellspacing='0' cellpadding='5' class='grid'>" .
						"<col width='115' /><col width='125' /><col /><col width='200' />" .
						"<tr>" .
							"<th></th>" .
							"<th><a data-petolio='true' href='{$this->view->url(array_merge($url, array('order' => 'name', 'dir' => $this->view->rdir)))}'>" . $this->translate->_("Pet Info") . ($this->view->order == 'name' ? "&nbsp;<img src='" . PO_BASE_URL . "images/order/{$this->view->dir}.png' />" : "") . "</a></th>" .
							"<th><a data-petolio='true' href='{$this->view->url(array_merge($url, array('order' => 'description', 'dir' => $this->view->rdir)))}'>" . $this->translate->_("Description") . ($this->view->order == 'description' ? "&nbsp;<img src='" . PO_BASE_URL . "images/order/{$this->view->dir}.png' />" : "") . "</a></th>" .
							"<th><a data-petolio='true' href='{$this->view->url(array_merge($url, array('order' => 'address', 'dir' => $this->view->rdir)))}'>" . $this->translate->_("Address") . ($this->view->order == 'address' ? "&nbsp;<img src='" . PO_BASE_URL . "images/order/{$this->view->dir}.png' />" : "") . "</a></th>" .
						"</tr>";

					$privacy_cache = array ();
					foreach($this->view->pets as $pet) {
						// picture control
						$image = "/images/small_no-pet.jpg";
						if($pet["picture"] && strlen($pet["picture"]) > 0)
							$image = PO_BASE_URL . "images/userfiles/pets/{$pet["id"]}/gallery/small_{$pet["picture"]}";

						$html .= "<tr>" .
							"<td align='right'><a data-petolio='true' href='{$this->view->url(array('controller'=>'index', 'action'=>'view', 'pet' => $pet["id"]), 'distribution', true)}'><img src='{$image}' style='display: block; padding: 3px; border: 1px solid #B3B3B3; background: white;' alt='". $this->translate->_("Pet Picture") ."' /></a></td>" .
							"<td>" .
								"<a data-petolio='true' href='{$this->view->url(array('controller'=>'index', 'action'=>'view', 'pet' => $pet["id"]), 'distribution', true)}'>{$pet["name"]}</a>" .
								"<br />" .
								$this->view->Tr($pet["breed"]) .
								"<br />" .
								($pet["gender"] ? $this->view->Tr($pet["gender"]) . "<br />" : '') .
								($pet["dateofbirth"] ? Petolio_Service_Util::formatTime(strtotime($pet["dateofbirth"]), true) : '' ) .
							"</td>" .
							"<td valign='top'>" . Petolio_Service_Parse::do_limit(strip_tags($pet["description"]), 220, true, true) . "</td>" .
							"<td valign='top'>";

								if(array_key_exists($pet["user_id"], $privacy_cache))
									$user_data = $privacy_cache[$pet["user_id"]];
								else {
									$user_data = $this->view->PrivacyFilter(array(
										"id" => $pet["user_id"],
										"name" => $pet["user_name"],
										"address" => $pet["user_address"],
										"zipcode" => $pet["user_zipcode"],
										"location" => $pet["user_location"],
										"country_id" => $pet["user_country_id"],
										"category_id" => $pet["user_category_id"]
									));

									$privacy_cache[$pet["user_id"]] = $user_data;
								}

								$address = '';
								if($this->translate->getLocale() == 'en') {
									$address .= $user_data["address"].' '.$user_data["location"].' '.$user_data["zipcode"];
									if(strlen($user_data["address"]) > 0 || strlen($user_data["location"]) > 0)
										if(strlen(@$this->view->country_list[$user_data["country_id"]]) > 0)
											$address .= ', ';

									$address .= @$this->view->country_list[$user_data["country_id"]];
								} else {
									$address .= $user_data["zipcode"].' '.$user_data["address"];
									if(strlen($user_data["zipcode"]) > 0 || strlen($user_data["address"]) > 0)
										if(strlen($user_data["location"]) > 0)
											$address .= ', ';

									$address .= $user_data["location"];
									if(strlen($user_data["location"]) > 0 || strlen($user_data["zipcode"]) > 0 || strlen($user_data["address"]) > 0)
										if(strlen(@$this->view->country_list[$user_data["country_id"]]) > 0)
											$address .= ', ';

									$address .= @$this->view->country_list[$user_data["country_id"]];
								} $address = trim($address);

								if(strlen($address))
									$html .= $address . '<br /><br />';

								$html .= "<input data-url='{$this->view->url(array('controller'=>'index', 'action'=>'view', 'pet' => $pet["id"]), 'distribution', true)}' type='button' value='" . $this->translate->_("Detail >") . "' id='submit' name='view' style='float: right;' /><div class='clear'></div>";

						$html .= "</td></tr>";
					}

					$html .= "</table>";
				}

				$html .= $this->view->paginationControl($this->view->pets, 'Elastic', 'index/navigation-controls.phtml', array("pos" => "bot", "jsonp" => true));

				if($this->view->distribution_attributes['withmainmenu']->getAttributeEntity()->getValue() == 'Yes') {
					$html .= "<div class='cl tenpx'></div>" .
						"<div class='left'><input data-url='{$this->view->url(array('controller'=>'index', 'action'=>'index', 'url'=>$this->view->content_distributions->getUrl()), 'distribution', true)}' type='button' value='" . $this->translate->_("< Back to startpage"). "' id='submit' name='prev' style='margin: 0px;'><div class='clear'></div></div'" .
						"<div class='clear'></div>";
				}

				$html .= "<div class='cl'></div>";
			}

			$html .= "<div class='inner-footer'>". sprintf($this->translate->_("This service is provided by %s"), "<a href=\"http://www.petolio.com\" target=\"_blank\" style=\"margin: 0px; padding: 0px;\">petolio.com</a>") ."</div>";

			die('Frontend.page(' . json_encode(array(
				'success' => true,
				'html' => $html
			)) . ');');
		}
    }

    public function viewAction() {
    	$jsonp = false;
    	if(isset($_GET['jsonp']) && $_GET['jsonp'] = 1)
			$jsonp = true;

    	// load pet
    	$pets = new Petolio_Model_PoPets();
    	$result = $pets->fetchList("id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->getRequest()->getParam('pet'), Zend_Db::BIGINT_TYPE)." AND deleted = '0'");
    	if(!(is_array($result) && count($result) > 0))
    		die('gtfo noob');

    	$pet = reset($result);

    	// if flagged, load reasons
    	$this->view->flagged = array();
    	if($pet->getFlagged() == 1) {
    		$reasons = new Petolio_Model_PoFlagReasons();
    		$results = $this->db->flag->getMapper()->fetchList("scope = 'po_pets' AND entry_id = '{$pet->getId()}'");
    		foreach($results as $line) {
    			$reasons->find($line->getReasonId());
    			$this->view->flagged[] = Petolio_Service_Util::Tr($reasons->getValue());
    		}
    	}

    	// send to template
    	$this->view->pet = $pet;

    	// load species
    	$this->view->species = array();
    	$sets = new Petolio_Model_PoAttributeSets();
    	foreach($sets->getMapper()->getDbTable()->getAttributeSets('po_pets') as $type)
    		$this->view->species[$type['id']] = Petolio_Service_Util::Tr($type['name']);

    	// get the flag form
    	$this->view->flag = new Petolio_Form_Flag();

    	// get pet attributes
    	$attributes = new Petolio_Model_PoAttributes();
    	$this->view->attributes = reset($attributes->getMapper()->getDbTable()->loadAttributeValues($pet, true));

    	// get pet pictures
    	$pictures = array();
    	$folders = new Petolio_Model_PoFolders();
    	$files = new Petolio_Model_PoFiles();
    	$gallery = $folders->getMapper()->getDbTable()->findFolders(array('name' => 'gallery', 'petId' => $pet->getId()));
    	if(isset($gallery)) $pictures = $files->fetchList("folder_id = '{$gallery->getId()}'", "date_created ASC", 14);
    	if(isset($pictures) && count($pictures) > 0) {
    		$this->view->gallery = array();
    		foreach($pictures as $pic) {
    			$this->view->gallery[$pic->getId()] = $pic->getFile();
    		}
    	}

    	// get pet videos
    	$videos = array();
    	$media = $folders->getMapper()->getDbTable()->findFolders(array('name' => 'videos', 'petId' => $pet->getId()));
    	if(isset($media)) $videos = $files->fetchList("folder_id = '{$media->getId()}'", "id ASC", 14);
    	if(isset($videos) && count($videos) > 0) {
    		// youtube wrapper
    		$youtube = Petolio_Service_YouTube::factory('Master');
    		$youtube->CFG = array(
    			'username' => $this->config["youtube"]["username"],
    			'password' => $this->config["youtube"]["password"],
    			'app' => $this->config["youtube"]["app"],
    			'key' => $this->config["youtube"]["key"]
    		);

    		// needed upfront
    		$ds = DIRECTORY_SEPARATOR;
    		$upload_dir = APPLICATION_PATH . "{$ds}..{$ds}data{$ds}userfiles{$ds}pets{$ds}{$pet->getId()}{$ds}videos{$ds}";

    		// iterate over videos for cached entries
    		foreach($videos as $idx => $one) {
    			// get the cached entry
    			$entry = $youtube->getVideoEntryCache(pathinfo($one->getFile(), PATHINFO_FILENAME), $upload_dir);

    			// error? skip and remove from list
    			if(!is_object($entry)) {
    				unset($videos[$idx]);
    				continue;
    			}

    			// set cached entry
    			$one->setMapper($entry);
    		}

    		// output videos
    		$this->view->videos = $videos;
    	}
    	
    	// get pet audios
    	$aaudios = array();
    	$audios = $folders->getMapper()->getDbTable()->findFolders(array('name' => 'audios', 'petId' => $pet->getId()));
    	if(isset($audios)) $aaudios = $files->fetchList("folder_id = '{$audios->getId()}'", "id ASC", 14);
    	if(isset($aaudios) && count($aaudios) > 0) {
    		$this->view->audios = array();
    		foreach($aaudios as $aud) {
    			$this->view->audios[$aud->getId()] = $aud->getDescription();
    		}
    	}
    	 
    	// find owner
    	$user = new Petolio_Model_PoUsers();
    	$user->find($pet->getUserId());
    	$this->view->owner = $user;

    	// load pet's emergency contacts
    	$db = new Petolio_Model_DbTable_PoAttributeSets();
    	$select = $db->select()
    		->where("scope = 'po_services'")
    		->where("active = 1");
    	
    	$service_types = array();
    	foreach($db->fetchAll($select) as $line)
    		$service_types[$line['id']] = Petolio_Service_Util::title_case(Petolio_Service_Util::Tr($line['name']));
    	$this->view->service_types = $service_types;
    	
    	$ds = new Petolio_Model_PoEmergency();
    	$this->view->pet_emergency_contacts = $ds->fetchList("scope = 'po_pets' AND entity_id = '{$pet->getId()}'", "id ASC");
    	 
		// ---------------------------------------------------------------------------------------
		// it's JSONP Time!
		// ---------------------------------------------------------------------------------------
		if($jsonp) {
			header('Content-type: application/json');

			if($this->view->flagged) {
				$html = "<h1>" . $this->translate->_("Pet:") . $this->view->attributes['name']->getAttributeEntity()->getValue() . "</h1>" .
					"<br />" .
					"<div class='c_error'>" .
					"<div><b>" . $this->translate->_("Cannot display this pet because it was flagged by the community.") . "</b></div>" .
					"<ul>";
						foreach(array_unique($this->view->flagged) as $item)
							$html .= "<li>{$item}</li>";
						$html .= "</ul>" .
					"</div>";
			} else {
				// picture control
				$image = PO_BASE_URL . "images/small_no-pet.jpg";
				if (count($this->view->gallery) > 0)
					$image = PO_BASE_URL . "images/userfiles/pets/{$this->view->pet->getId()}/gallery/small_" . reset($this->view->gallery);

				$html = "<div>" .
						"<div class='left'><img src='{$image}' style='display: block; padding: 3px; border: 1px solid #B3B3B3; background: white;' alt='" . $this->translate->_("Pet Picture") . "' /></div>" .
						"<div class='left' style='padding: 20px 0px 0px 10px;'><h1>{$this->view->attributes['name']->getAttributeEntity()->getValue()}</h1></div>" .
						"<div class='clear'></div>" .
					"</div>" .
					"<br />" .
					"<table cellspacing='0' cellpadding='5' border='0' class='list'>" .
						"<tr>" .
							"<th>" . $this->translate->_("Owner") . ":</th>" .
					 		"<td><a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->view->pet->getUserId()), 'default', true)}' target='_blank'>{$this->view->owner}</a></td>" .
					 	"</tr>" .
					"</table>" .
					"<div class='tenpx'></div>" .

					"<div class='left'><input data-url='previous' type='button' value='" . $this->translate->_("< Back to List") . "' id='submit' name='prev' style='margin: 0px;'><div class='clear'></div></div>" .
					"<div class='clear'></div>" .

					"<br /><br />" .
					"<br /><br />" .

					"<div class='green_box'>" .
						"<div class='title'>" .
							"<span data-id='info' class='active'>" . $this->translate->_("Pet Details") . "</span> " .
							"<span data-id='pictures'>" . $this->translate->_("Pictures") . "</span> " .
							"<span data-id='videos'>" . $this->translate->_("Videos") . "</span>" .
						"</div>" .
						"<div id='info'>" .
							"<div>" .
								"<table cellspacing='0' cellpadding='5' border='0' class='list'>" .
								"<tr><th>" . $this->translate->_('Species') . "</th><td>{$this->view->species[$this->view->pet->getAttributeSetId()]}</td>";

									$tr_end = 1; $description = array();
							    	foreach ($this->view->attributes as $attr) {
										$src = is_array($attr->getAttributeEntity()) ? reset($attr->getAttributeEntity()) : $attr->getAttributeEntity();
										$val = $src->getValue();
										if(strpos($attr->getCode(), '_description') !== false) {
											$description = array($this->view->Tr($attr->getLabel()), $val);
											continue;
										}

										if (isset($val) && strlen($val) > 0) {
											if ($tr_end == 2) {
												$html .= "</tr><tr>";
												$tr_end = 0;
											}

											$tr_end++;

											$html .= "<th>";
							        		$html .= $this->view->Tr($attr->getLabel());
							        		$html .= "</th><td>";

							        		$html .= $val;

							        		// no description for the price fields
							        		if ($attr->getDescription() && strlen($attr->getDescription()) > 0 && !($attr->getCurrencyId() && intval($attr->getCurrencyId()) > 0))
							        			$html .= "&nbsp;".$attr->getDescription();

							        		$html .= "</td>";
										}

							        	// show the latin name if it's any
							        	if ($attr->getAttributeInputType()->getType() == 'select')
							        		if ($src->getLatin() && strlen($src->getLatin()) > 0)
							        			$html .= "<tr><th>".$this->translate->_('Scientific name').":</th><td>{$src->getLatin()}</td></tr>";
									}

									if ($tr_end == 1)
										$html .= "<th></th><td></td>";
									$html .= "</tr>";

								$html .= "</table>" .
							"</div>";

							if(strlen($description[1]) > 0) {
								$html .= "<div class='tenpx'></div>" .
									"<div style='font-size: 12px; line-height: 18px;'>" .
									"<h3>{$description[0]}</h3>" .
									"<div class='fivepx'></div>" .
									$description[1] .
								"</div>";
							}

							$html .= "<div class='clear'></div>" .
						"</div>" .
						"<div id='pictures' style='display: none;'>";
							if(count($this->view->gallery) > 0) {
							    $html .= "<div class='pictures' style='width: 700px;'>";
									foreach($this->view->gallery as $idx => $pic) {
										$url = $this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $this->view->pet->getId()), 'default', true).'#img/'.$idx;
										$html .= "<div class='pic'>" .
											"<span onclick='window.open(\"{$url}\");' class='img' rel='{$idx}' style='background: #000 url(\"". PO_BASE_URL ."images/userfiles/pets/{$this->view->pet->getId()}/gallery/small_{$pic}\") center center no-repeat;'></span>" .
										"</div>";
									}

									$html .= "<div class='clear'></div>" .
								"</div>" .
							    "<div class='clear'></div>";
							} else
								$html .= "<b class='red bigger'>" . $this->translate->_('Sorry, nothing here pal :(') . "</b>";

							$html .= "<div class='clear'></div>" .
						"</div>" .
						"<div id='videos' style='display: none;'>";
							if(count($this->view->videos) > 0) {
								$html .= "<div class='pictures' style='width: 700px;'>";
									foreach($this->view->videos as $video) {
										// get video entity
										$entity = $video->getMapper();

										// get video thumbnail
										$thumbs = $entity->getVideoThumbnails();
										$thumbnail = $thumbs[1]['url'];

										// get video duration
										$duration = date("i:s", $entity->getVideoDuration());

										$url = $this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => $this->view->pet->getId()), 'default', true).'#vid/'.$video->getId();
										$html .= "<div class='pic'>" .
											"<span onclick='window.open(\"{$url}\");' class='vid' rel='{$video->getId()}' style='background: #000 url(\"{$thumbnail}\") center center no-repeat;'></span>" .
											"<span class='duration'>{$duration}</span>" .
										"</div>";
									}

									$html .= "<div class='clear'></div>" .
								"</div>" .
							    "<div class='clear'></div>";
							} else
								$html .= "<b class='red bigger'>" . $this->translate->_('Sorry, nothing here pal :(') . "</b>";

							$html .= "<div class='clear'></div>" .
						"</div>" .
					"</div>";
			}

			$html .= "<hr />" .
				"<div class='left'><input data-url='previous' type='button' value='" . $this->translate->_("< Back to List") . "' id='submit' name='prev' style='margin: 0px;'><div class='clear'></div></div>" .
				"<div class='clear'></div>";

			$html .= "<div class='inner-footer'>". sprintf($this->translate->_("This service is provided by %s"), "<a href=\"http://www.petolio.com\" target=\"_blank\" style=\"margin: 0px; padding: 0px;\">petolio.com</a>") ."</div>";

			die('Frontend.page(' . json_encode(array(
				'success' => true,
				'html' => $html
			)) . ');');
		}
    }
}
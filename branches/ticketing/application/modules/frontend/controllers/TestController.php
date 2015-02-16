<?php

class TestController extends Zend_Controller_Action
{
	private $auth = null;
	private $request = null;
	private $cfg = null;
	private $translate = null;
	private $msg = null;

	private $up = null;
	private $prods = null;
	private $folders = null;

    public function init()
    {
    	$this->auth = Zend_Auth::getInstance();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->msg = new Zend_Session_Namespace("po_messages");

		$this->up = new Zend_Session_Namespace("po_messages_upload");
		$this->prods =new Petolio_Model_PoProducts();
		$this->folders = new Petolio_Model_PoFolders();
    }

    public function indexAction() {
		//$o['offset'] = Petolio_Service_Util::timezoneOffset(@$_COOKIE['user_timezone']);
		//$o['start'] = Petolio_Service_Util::calculateTimezone($e['date_start'], @$_COOKIE['user_timezone'])

		// dump(Petolio_Service_Util::formatDate(time()));
		// dump(date('d-m-y h:i:s', time()));
		// dump(date('d-m-y h:i:s', Petolio_Service_Util::calculateTimezone(time(), @$_COOKIE['user_timezone'])));
		// dump(Petolio_Service_Util::timezoneOffset(@$_COOKIE['user_timezone']));
		// dump(date('d-m-y h:i:s', Petolio_Service_Util::calculateTimezone(time() + Petolio_Service_Util::timezoneOffset(@$_COOKIE['user_timezone']), @$_COOKIE['user_timezone'])));

//     	//--------------------------------------------------------------------------------------------------------------------------------
		// $notify = new Petolio_Model_PoNotifications();
		// $results = $notify->fetchList("author_id = 0", false, 5000);
//
		// foreach($results as $one) {
			// $data = unserialize($one->getData());
			// if(isset($data[2]))
				// $one->setAuthorId($data[2])->save();
		// }

		// $notify->getMapper()->getDbTable()->delete("author_id = 0");

//     	//--------------------------------------------------------------------------------------------------------------------------------
		// $x = 0;
		// while($x < 25) {
			// $x++;
			// \Petolio_Service_AMQPC::sendMessage('forum', array($x));
			// usleep(250000);
		// }

//     	//--------------------------------------------------------------------------------------------------------------------------------

    	/*
		 * Species to forum categories
		 *
		<a href="http://petolio.local/help/index/species/24749" class="tag">Dog</a>			-> 4
		<a href="http://petolio.local/help/index/species/24747" class="tag">Cat</a>			-> 5
		<a href="http://petolio.local/help/index/species/24753" class="tag">Horse</a>		-> 6
		<a href="http://petolio.local/help/index/species/24757" class="tag">Rodent</a>		-> 7
		<a href="http://petolio.local/help/index/species/24746" class="tag">Bird</a>		-> 10
		<a href="http://petolio.local/help/index/species/24755" class="tag">Other</a>		-> 8, 11
		$species = array(
			4 => array(0 => '24749'),
			5 => array(0 => '24747'),
			6 => array(0 => '24753'),
			7 => array(0 => '24757'),
			8 => array(0 => '24755'),
			10 => array(0 => '24746'),
			11 => array(0 => '24755')
		);

		// get the database and models
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();

		// get the forum message parser
		define('PUN', true);
		$flux = new Petolio_Service_FluxBB();
		$GLOBALS['pun_config'] = $flux->getForumConfig();
		$GLOBALS['re_list'] = '%\[list(?:=([1a*]))?+\]((?:[^\[]*+(?:(?!\[list(?:=[1a*])?+\]|\[/list\])\[[^\[]*+)*+|(?R))*)\[/list\]%ie';

		include "../public/forum/include/functions.php";
		include "../public/forum/include/utf8/trim.php";
		include "../public/forum/include/utf8/mbstring/core.php";
		include "../public/forum/include/parser.php";

		// $a = parse_message('Gemäß FEI-Reglement ist nun die Anwendung von LDR bis zu 10 min auf dem Abreiteplatz offiziell gestattet. Die Rollkur ist dagegen bei internationalen Wettbewerben auf dem Abreiteplatz verboten.
//
// Seit 2013 gelten nunmehr die neuen Regeln auf Turnieren und deren Abreitplätzen. Der WDR zieht erste Bilanz: [url]http://www.wdr.de/mediathek/html/regional/rueckschau/2013/01/31/lokalzeit_muensterland.xml?noscript=true&offset=173&autoPlay=true&#flashPlayer[/url]', false);
		// dump($a, 'transformed');


		// get all of the topics from that section
		$stmt = $db->query("SELECT
			t.id, t.subject, t.first_post_id, t.last_post, t.num_views, t.forum_id,
			p.message,
			u.po_user_id

			FROM forum_topics AS t
			LEFT JOIN forum_posts AS p ON t.first_post_id = p.id AND t.id = p.topic_id
			LEFT JOIN forum_users AS u ON p.poster_id = u.id

			WHERE t.forum_id IN (4,5,6,7,10,8,11)
			GROUP BY t.id
			ORDER BY t.last_post DESC
		");

		// go through each topic
		foreach($stmt->fetchall() as $topic) {
			// create the data for po_help
			$po_help = array(
				'user_id' => $topic['po_user_id'],
				'attribute_set_id' => 67,
				'date_created' => date("Y-m-d H:i:s", $topic['last_post']),
				'views' => $topic['num_views']
			);

			// create the attribute data
			$attribute_data = array(
				'help_title' => $topic['subject'],
				'help_species' => $species[$topic['forum_id']],
				'help_description' => parse_message($topic['message'], false)
			);

			// save help question
			$help = new Petolio_Model_PoHelp();
			$attr = new Petolio_Model_PoAttributes();
			$help->setOptions($po_help)->save(true, true);
			$attr->getMapper()->getDbTable()->saveAttributeValues($attribute_data, $help->getId());

			// dump($topic, 'topic');
			// dump($po_help);
			// dump($attribute_data);

			// get topic replies
			$atmt = $db->query("SELECT
				p.message, p.posted,
				u.po_user_id

				FROM forum_posts AS p
				LEFT JOIN forum_users AS u ON p.poster_id = u.id

				WHERE p.topic_id = {$topic['id']} AND p.id != {$topic['first_post_id']}
				GROUP BY p.id
				ORDER BY p.posted ASC
			");

			// go through each post
			foreach($atmt->fetchall() as $post) {
				$answer = array(
					'help_id' => $help->getId(),
					'user_id' => $post['po_user_id'],
					'answer' => parse_message($post['message'], false),
					'date_created' => date("Y-m-d H:i:s", $post['posted']),
				);

				// save the answer
				$answ = new Petolio_Model_PoHelpAnswers();
				$answ->setOptions($answer)->save(true, true);

				// dump($answer, 'answer');
			}
		}
		 */

//     	//--------------------------------------------------------------------------------------------------------------------------------

    	// dump(pathinfo(strtolower('http://www.petolio.com/images/userfiles/dashboard/73bbddc6fabf35b8eada1cac673bdcd6.MP3'), PATHINFO_EXTENSION));

    	// Zend_Registry::get('Zend_Log')->err("Exception -> Message: test");

		// $html = sprintf(
			// $this->translate->_('%1$s added a new medical record entry for the pet %2$s'),
			// "<a href='{$this->view->url(array('controller'=>'accounts', 'action'=>'view', 'user' => $this->auth->getIdentity()->id), 'default', true)}'>{$this->auth->getIdentity()->name}</a>",
			// "<a href='{$this->view->url(array('controller'=>'pets', 'action'=>'view', 'pet' => 10), 'default', true)}'>Doggy</a>"
		// ) . '<br /><br />' .
		// sprintf(
			// $this->translate->_('Click %s to view the medical record.'),
			// "<a href='{$this->view->url(array('controller'=>'pets', 'action'=>'view-medical-record', 'medical-record' => 20), 'default', true)}'>{$this->translate->_('here')}</a>"
		// );
//
    	// // message
	   	// Petolio_Service_Message::send(array(
			// 'subject' => "text subject",
			// 'message_html' => $html,
			// 'from' => 0, // x - user_id, 0 = system
			// 'status' => 2 // 1 - sent, 2 - when from is 0
		// ), array(array( // add recipients, user id, user name and user email
			// 'id' => $this->auth->getIdentity()->id,
			// 'name' => $this->auth->getIdentity()->name,
			// 'email' => $this->auth->getIdentity()->email
		// )));

//     	//--------------------------------------------------------------------------------------------------------------------------------

    	// $s = '5 3 *';
//
    	// // get custom syntax based on linux cron
    	// if($s == '* * *') $a = 1;
		// elseif($s == '* * 1-5') $a = 2;
    	// elseif(preg_match("/\\* \\* [0-9]/i", $s)) $a = 3;
    	// elseif(preg_match("/[0-9] \\* \\*/i", $s)) $a = 4;
		// else $a = 5;
//
		// dump($a);

//     	//--------------------------------------------------------------------------------------------------------------------------------

//    	$parse = Petolio_Service_Parse::_('https://verein.ing-diba.de/umwelt/29559/katzen-hilfe-uelzen-ev');
//		dump($parse);

//    	dump(strftime("%d.%m.%y %I:%M %p", time()));

//     	$c = new Petolio_Service_Cache();
//     	dump($c->PoTranslations());

//     	$c->test = array('1' => 2);

//     	unset($c->test);
//     	dump($c->test);

//     	dump(Petolio_Service_Util::timezoneOffset('Europe/Berlin'));
//     	dump(new DateTime(date('Y-m-d H:i:s', 1343134800)));

//     	$APEserver = 'http://ape.petolio.riffcode.ro:6969/?';
//     	$APEPassword = 'testpasswd';

//     	$cmd = array(array(
//     			'cmd' => 'inlinepush',
//     			'params' =>  array(
//     					'password'  => $APEPassword,
//     					'raw'       => 'postmsg',
//     					'channel'   => '149',
//     					'data'      => 'kicked'
//     			)
//     	));

//     	$data = file_get_contents($APEserver.rawurlencode(json_encode($cmd)));
//     	$data = json_decode($data);

//     	dump($data);


//     	echo sprintf('123 %s %s', 'a', 'a');

//     	//--------------------------------------------------------------------------------------------------------------------------------

//     	$attrs = new Petolio_Model_PoAttributes();
//     	foreach($attrs->fetchList("attribute_input_type_id = 1") as $one) {
//     		$opt = new Petolio_Model_PoAttributeOptions();
//     		$yes = reset($opt->fetchList("attribute_id = {$one->getId()} AND value = 'Yes'"));

//     		$opt = new Petolio_Model_PoAttributeOptions();
//     		$no = reset($opt->fetchList("attribute_id = {$one->getId()} AND value = 'No'"));

//     		if($yes && $no) {
//     			$int = new Petolio_Model_PoAttributeEntityInt();
// 				foreach($int->fetchList("attribute_id = {$one->getId()}") as $ints) {
// 					$opt = new Petolio_Model_PoAttributeOptions();
// 					$opts = $opt->find($ints->getValue());
// 					if($opts->getId()) {
// 						if($opts->getValue() == 'Yes') $value = 1;
// 						elseif($opts->getValue() == 'No') $value = 0;
// 						$ints->setValue($value)->save();
// 					}
// 				}

// 				$yes->deleteRowByPrimaryKey();
// 				$no->deleteRowByPrimaryKey();
//     			$one->setAttributeInputTypeId('11')->save();
//     		}
//     	}

    	//--------------------------------------------------------------------------------------------------------------------------------
//     	$file = new Petolio_Model_PoFiles();
//     	$folder = new Petolio_Model_PoFolders();
//     	$files = $file->fetchList("type = 'image'");

// 		$_files = array();
// 		$_medical = array();
//     	foreach($files as $one) {
// 			$f = $folder->find($one->getFolderId());

// 			if ( !($f->getPetId() && $f->getPetId() > 0) )
// 				continue;

// 			if($f->getParentId() == 0) {
// 				$_files[] = $one;
// 				continue;
// 			}

// 			$breadcrumbs = $folder->getMapper()->getBreadcrumbs($f->getTraceback());
// 			if(count($breadcrumbs) > 1) {
// 				if($breadcrumbs[0]['name'] == 'root' && ($breadcrumbs[1]['name'] == 'medical records' || $breadcrumbs[1]['name'] == 'medical_records')) {
// 					$_medical[] = $one;
// 					continue;
// 				}

// 				$_files[] = $one;
// 				continue;
// 			} else {
// 				$bread = reset($breadcrumbs);
// 				if(!($bread['name'] == 'root' && $f->getName() == 'gallery')) {
// 					$_files[] = $one;
// 					continue;
// 				}
// 			}
//     	}

//     	$ids = array ();
// 		foreach($_files as $file) {
// 			$ids[] = $file->getId();
// 			Zend_Registry::get('Zend_Log')->debug("File with id ".$file->getId()." set to type file.");
// 			$file->setType('file')->save();
// 		}

// 		foreach($_medical as $medical) {
// 			$ids[] = $medical->getId();
// 			Zend_Registry::get('Zend_Log')->debug("File with id ".$medical->getId()." set to type medical.");
// 			$medical->setType('medical')->save();
// 		}
// 		Zend_Registry::get('Zend_Log')->debug(implode(', ', $ids));
		//--------------------------------------------------------------------------------------------------------------------------------

    	// message
//    	Petolio_Service_Message::send(array(
//			'subject' => "text subject",
//			'message_html' => "i<br /><a href='http://www.google.com'>like</a><br />to<br />party!",
//			'message_text' => "i\nlike\nto\nparty!", // optional
//			'from' => 0, // x - user_id, 0 = system
//			'status' => 2 // 1 - sent, 2 - when from is 0
//		), array(array( // add recipients, user id, user name and user email
//			'id' => $this->auth->getIdentity()->id,
//			'name' => $this->auth->getIdentity()->name,
//			'email' => $this->auth->getIdentity()->email
//		)));

    	// load user
//    	$user = new Petolio_Model_PoUsers();
//    	$user->getMapper()->find(26, $user);
//
//    	$all = array_merge($user->getUserFriends(), $user->getUserPartners());
//    	ksort($all); // sort friends / partners
//
//		$result = array();
//		foreach($all as $row)
//			$result[$row->getId()] = array('name' => $row->getName());
//
//    	dump($result);

    	// test cron
//		$next = Petolio_Service_Calendar::getNextRunDate(array('*', '*', '*', '*', '*'))->format('Y-m-d H:i:s');
//		dump($next);

//		$occ = Petolio_Service_Calendar::getOccurences(array('*', '*', '*', '*', '*'), strtotime('2011-09-28'), strtotime('2011-10-05'));
//		dump($occ);

//      if(preg_match("/(0?[1-9]|[12][0-9]|3[01]) \\* \\*/i", $s)) return 4;		// every month
//    	if(preg_match("/(0?[1-9]|[12][0-9]|3[01]) (0?[1-9]|1[012]) \\*/i", $s)) return 5;	// every year

//    	dump(preg_match("/[0-9] \\* \\*/i", "* * *"));

//    	$unix = 123345567;
//		$unix = new Zend_Db_Expr('NULL');
//    	dump(new Zend_Db_Expr("FROM_UNIXTIME({$unix})"));

//    	$new = new DateTime('now');
//    	dump($new->modify("+5 days")->format('Y-m-d H:i:s'));

//    	$m = new Petolio_Model_PoPets();
//    	$r = $m->getPets('paginator');
//
//    	foreach($r as $line)
//			dump($line);

//    	// youtube login info :)
//    	list($username, $password, $devkey) = file("C:\\Users\\Seth\\Desktop\\lol.txt");
//
//    	// youtube wrapper
//		$youtube = Petolio_Service_Youtube::factory('Master');
//		$youtube->CFG = array(
//			'username' => $username,
//			'password' => $password,
//			'app' => 'petolio',
//			'key' => $devkey
//		);
//
//		// create a new video
//		$video = new Zend_Gdata_YouTube_VideoEntry();
//		$video->setVideoTitle(htmlspecialchars('Pet name + random identificator'));
//		$video->setVideoDescription(htmlspecialchars('Get the pet description here'));
//		$video->setVideoCategory('Animals');
//		$video->SetVideoTags('petolio, pet');
//
//		// make video unlisted
//        $unlisted = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', '');
//		$unlisted->setExtensionAttributes(array(
//			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
//			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
//		));
//		$video->setExtensionElements(array($unlisted));
//
//		// get upload form
//		$form = $youtube->getFormUploadToken($video, 'http://gdata.youtube.com/action/GetUploadToken');
//
//		// place to redirect user after upload
//		$nextUrl = 'http://petolio.local/test';
//
//		// build the form
//		$form = '<form action="'. $form['url'] .'?nexturl='. $nextUrl .'" method="post" enctype="multipart/form-data">'.
//	        '<input name="file" type="file"/>'.
//	        '<input name="token" type="hidden" value="'. $form['token'] .'"/>'.
//	        '<input value="Upload Video File" type="submit" />'.
//	        '</form>';
//
//		echo $form;
//
//    	// get the uploaded video state
//		if(isset($_GET['id'])) {
//			$entity = $youtube->getVideoEntry($_GET['id']);
//			$state = $entity->getVideoState();
//			if($state)
//				dump($state, $state->getName());
//
//			else
//				dump('video is live');
//    	}

/*    	$links = "lol <a href='http://www.google.com'>lol</a>";
		$test = preg_replace("/<a.*?href[^=]*=[^'\"]*['\"]([^'\"]+)['\"].*?>([^<]+)<\/a>/", "\\1 (\\2)", $links);
    	dump($test); */

//    	// init form
//		$form = new Petolio_Form_Template();
//		$this->view->form = $form;
//
//		// did we submit form ? if not just return here
//		if(!($this->request->isPost() && $this->request->getPost('submit')))
//		return false;
//
//		// is the form valid ? if not just return here
//		if(!$form->isValid($this->request->getPost()))
//		return false;
//
//		// redirect
//		$data = $form->getValues();
//		return $this->_redirect('test/attribute/id/'. $data['attribute_set']);
//    }
//
//    public function attributeAction() {
//		// init form
//		$form = new Petolio_Form_Template($this->request->getParam('id'));
//		$this->view->form = $form;
//
// 		// did we submit form ? if not just return here
// 		if(!$this->request->isPost())
// 			return false;
//
//		$ds = DIRECTORY_SEPARATOR;
//		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}attributes{$ds}1{$ds}";
//
//    	// prepare upload files
//		$i = 0;
//		$errors = array();
//		$success = array();
//
//		// get addapter
//		$adapter = new Zend_File_Transfer_Adapter_Http();
//		$adapter->setDestination($upload_dir);
//		$adapter->addValidator('IsImage', false);
//
//		// getting the max filesize
//		$config = Zend_Registry::get('config');
//		$size = $config['max_filesize'];
//		$adapter->addValidator('Size', false, $size);
//
//     	// check if files have exceeded the limit
//     	if (!$adapter->isValid()) {
//     		$msg = $adapter->getMessages();
//     		if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
//     			dump("Your file / files exceed the maximum size limit allowed (60MB), nothing was uploaded.");
//     			return false;
//     		}
//     	}
//
//		// upload each file
//		foreach((!is_array($adapter->getFileName()) ? array($adapter->getFileName()) : $adapter->getFileName()) as $file) {
//			$i++; $new_filename = md5(time() . '-' . $i) . '.' . pathinfo($file, PATHINFO_EXTENSION);
//
//			$adapter->clearFilters();
//			$adapter->addFilter('Rename',
//			array('target' => $upload_dir . $new_filename, 'overwrite' => true));
//
//			if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME)))
//				$errors[pathinfo($file, PATHINFO_BASENAME)] = $adapter->getMessages();
//			else
//				$success[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;
//		}
//
//		dump($errors);
//		dump($success);
//
//		// get data
//		$data = $form->getValues();
//		dump($data);

//    	// social test
//    	// ------------------------------------------------------------
//    	// grab the first pet picture that exists
//    	$this->view->pic = false;
//    	$ds = DIRECTORY_SEPARATOR;
//    	$files = new Petolio_Model_PoFiles();
//		$db = $files->getMapper()->getDbTable();
//		foreach($db->fetchAll($db->select()->setIntegrityCheck(false)
//			->from(array('z' => 'po_files'), array('id', 'file'))
//			->where("z.folder_id = y.id")
//			->joinLeft(array('y' => 'po_folders'), "y.name = 'gallery'", array('pet_id'))
//			->order('z.id ASC'))->toArray() as $item) {
//			if(is_file("..{$ds}data{$ds}userfiles{$ds}{$item['pet_id']}{$ds}gallery{$ds}{$item['file']}")) {
//				$this->view->pic = $item;
//				break;
//			}
//		}

//    	$translate = Zend_Registry::get('Zend_Translate');
//		$translate->setLocale(new Zend_Locale('en'));
//    	dump($translate->_("Reminder Alert"));

//    	$from = '11.11.1111';
//    	dump(preg_match('/^(\d\d?)\/(\d\d?)\/(\d\d\d\d)$/', $from));



//    	$file = new Petolio_Model_PoFiles();
//    	$file->find(377);
//
//		// post on dashboard
//		Petolio_Service_Autopost::factory('video', $file,
//			'pet', '19', 'http://petolio.local/pets/view/pet/19', '666'
//		);


		// post on dashboard
//     	$fake = array(
//     		$this->translate->_('%1$s has updated the details for %2$s %3$s %4$s'),
//     		$this->translate->_("pet")
//     	); unset($fake);
// 		Petolio_Service_Autopost::factory('text', 'add', 'pet', '19',
// 			'{translate}' . '%1$s has updated the details for %2$s %3$s %4$s' . '{/translate}',
// 			array(
// 				'<a href="{user_link}" style="font-weight: bold;">{user_name}</a>',
// 				'{user_gender}',
// 				"pet",
// 				"<a href='{$this->view->url(array('controller' => 'pets', 'action' => 'view', 'pet' => 19), 'default', true)}'>666</a>"
// 			)
// 		);

		// remove from dashboard
//		Petolio_Service_Autopost::factory('text', 'del', 'pet', '19', null, null);

//     	$dash = new Petolio_Model_PoDashboard();
//     	$results = $dash->getMapper()->fetchAll();

//     	foreach($results as $one)
//     		$this->view->out .= $one->getData();

    	// youtube wrapper
//     	$youtube = Petolio_Service_YouTube::factory('Master');
//     	$youtube->CFG = array(
//     		'username' => $this->cfg["youtube"]["username"],
//     		'password' => $this->cfg["youtube"]["password"],
//     		'app' => $this->cfg["youtube"]["app"],
//     		'key' => $this->cfg["youtube"]["key"]
//     	);

//     	$entry = $youtube->getVideoEntryCache('5UEAxblyAFw', "../data/userfiles/services/3/");
//     	dump($entry);


//     	$url = 'https://www.verisign.com/';
//     	$ca = 'C:\Wamp\bin\php\php5.3.9\curl.crt';
//     	$ch = curl_init();

//     	// Apply various settings
//     	curl_setopt($ch, CURLOPT_URL, $url);
//     	curl_setopt($ch, CURLOPT_HEADER, 0); // Don�t return the header, just the html
//     	curl_setopt($ch, CURLOPT_CAINFO, $ca); // Set the location of the CA-bundle
//     	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return contents as a string

//     	$result = curl_exec ($ch);
//     	curl_close($ch);
//     	var_dump($result);

//     	exit;

    	// soundcloud wrapper
//     	$soundcloud = Petolio_Service_SoundCloud::factory('Master');
//     	$soundcloud->CFG = array(
//     		'clientid' => $this->cfg["soundcloud"]["clientid"],
//     		'clientsecret' => $this->cfg["soundcloud"]["clientsecret"],
//     		'redirect' => 'http://petolio.local/test',
//     		'sandbox' => $this->cfg["soundcloud"]["sandbox"]
//     	);


//     	$soundcloud->setAccessToken('1337');
// 		echo "<a href='{$soundcloud->getAuthorizeUrl()}'>login</a><br /><br />";

// 		if(isset($_GET['code'])) {
// 	    	try {
// 	    		$soundcloud->accessToken($_GET['code']);
// 	    	} catch (Soundcloud\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
// 	    		exit($e->getMessage());
// 	    	}

// 	    	// get me
// 	    	try {
// 	    		$response = json_decode($soundcloud->get('me'), true);

// 	    		dump($response);

// 	    	} catch (Soundcloud\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
// 	    		exit($e->getMessage());
// 	    	}
// 		}

// 		$myDateTime = new DateTime('2009-03-21 13:14', new DateTimeZone('Europe/Bucharest'));
// 		$myDateTime->setTimezone(new DateTimeZone($_COOKIE['user_timezone']));
// 		echo $myDateTime->format('Y-m-d H:i');
	}

    // public function loadAction()
    // {
    	// $search = strtolower($this->getRequest()->getParam('query'));
//
		// $countries = array();
		// $countriesMap = new Petolio_Model_PoCountriesMapper();
		// foreach($countriesMap->fetchList("name LIKE '%{$search}%'") as $country)
			// $countries[] = array('value' => $country->getId(), 'text' => $country->getName());
//
		// return Petolio_Service_Util::json(array('success' => true, 'results' => $countries));
    // }
}
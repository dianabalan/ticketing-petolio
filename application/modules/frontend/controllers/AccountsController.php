<?php

class AccountsController extends Zend_Controller_Action
{
	private $translate = null;
	private $auth = null;
	private $msg = null;
	private $email = null;
	private $request = null;
	private $cfg = null;

	private $user = null;
	private $pets = null;
	private $serv = null;
	private $files = null;
	private $folders = null;

	private $attr = null;
	private $sets = null;

	private $pfr = null;
	private $pfru = null;

	private $flag = null;
	private $clients = null;

	private $europe = array(48.690832999999998, 9.140554999999949);

	public function preDispatch()
	{
		// set request and auth
		$this->view->request = $this->request;
		$this->view->auth = $this->auth;

		// load $this->user object for the following actions
		$actions = array('view', 'view-info');

		// load $this->user object here and send it to template (if action is view, we load from parameter user, else we load from identity)
		if (in_array($this->getRequest()->getActionName(), $actions)
			//$this->request->getActionName() == 'view' || $this->request->getActionName() == 'view-info'
				|| $this->auth->hasIdentity()) {

			$this->user->getMapper()->find(in_array($this->getRequest()->getActionName(), $actions) ? $this->request->getParam('user') : $this->auth->getIdentity()->id, $this->user);
			$this->view->user = $this->user;
		}

		// search box only for index or if not logged in
		if ($this->request->getActionName() == 'index' || !$this->auth->hasIdentity()) {
			// load genders for searchbox
			$this->view->genders = array('1' => $this->translate->_('Male'), '2' => $this->translate->_('Female'));

			// load types for searchbox
			$this->view->types = array('1' => $this->translate->_('Pet Owner'), '2' => $this->translate->_('Service Provider'));

			// load countries for searchbox
			$this->view->countries = array();
			$countriesMap = new Petolio_Model_PoCountriesMapper();
			foreach($countriesMap->fetchAll() as $country)
				$this->view->countries[$country->getId()] = $country->getName();
		}
	}

	/**
	 * runs after action method
	 * the placeholders must be executed only after the action method, this way they are not executed on every ajax request
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch() {
		// load placeholders
		$this->_helper->placeholders();
	}

	public function init()
	{
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->auth = Zend_Auth::getInstance();
		$this->msg = new Zend_Session_Namespace("po_messages");
		$this->email = new Petolio_Service_Mail();
		$this->request = $this->getRequest();
		$this->cfg = Zend_Registry::get("config");

		$this->user = new Petolio_Model_PoUsers();
		$this->pets = new Petolio_Model_PoPets();
		$this->serv = new Petolio_Model_PoServices();
		$this->files = new Petolio_Model_PoFiles();
		$this->folders = new Petolio_Model_DbTable_PoFolders();

		$this->attr = new Petolio_Model_DbTable_PoAttributes();
		$this->sets = new Petolio_Model_DbTable_PoAttributeSets();

		$this->pfr = new Petolio_Model_PoFieldRights();
		$this->pfru = new Petolio_Model_PoFieldRightUsers();

		$this->flag = new Petolio_Model_PoFlags();
		$this->clients = new Petolio_Model_PoClients();

		// load dashboard models
		$this->db = new stdClass();
		$this->db->dash = new Petolio_Model_PoDashboard();
		$this->db->comments = new Petolio_Model_PoComments();
		$this->db->ratings = new Petolio_Model_PoRatings();
		$this->db->rights = new Petolio_Model_PoDashboardRights();
		$this->db->subscriptions = new Petolio_Model_PoSubscriptions();

		// init dashboard service
		$this->dash = new Petolio_Service_Dashboard($this->request, $this->db);

		// preload ratings and privacy
		$this->view->privacy = $this->dash->privacy;

		// append the dashboard css
		$this->view->headLink()->appendStylesheet(Petolio_Service_Util::autoVersion('/css/dashboard.css'));
	}

	/*
	 * User list
	 */
	public function indexAction() {

		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// see if list or grid
		$this->view->list = $this->request->getParam('list') ? 'list' : 'grid';

		// set filters
		$filters = $this->buildSearchFilter();

		// get page
		$page = $this->request->getParam('page');
		$page = (($this->view->search || $this->view->filtered) ? ($page ? intval($page) : 0) : 0);

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'desc' ? 'desc' : 'asc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'account') $sort = "type {$this->view->dir}";
		elseif($this->view->order == 'gender') $sort = array("gender {$this->view->dir}", "category_id {$this->view->dir}");
		elseif($this->view->order == 'address') $sort = array("zipcode {$this->view->dir}", "location {$this->view->dir}");
		else {
			if($this->view->search || $this->view->filtered) {
				$this->view->order = 'name';
				$sort = "name {$this->view->dir}";
			} else {
				$this->view->order = '';
				$sort = "RAND(".date("Ymd").")";
			}
		}

		$cache = Zend_Registry::get('Zend_Cache');
		$cacheID = Petolio_Service_Util::createCacheID("Accounts_".$filters."_".$sort."_".$page);

		if (false === ($accounts = $cache->load($cacheID))) {
			// load users
			$accounts = $this->user->fetchListToPaginator($filters, $sort);
			$accounts->setItemCountPerPage(($this->view->search || $this->view->filtered) ? $this->cfg["users"]["pagination"]["itemsperpage"] : 10);
			$accounts->setCurrentPageNumber($page);

			// assign user info
			foreach($accounts as &$data)
				$data = $this->_helper->userinfo($data['id']);

			$cache->save($accounts, $cacheID);
		}

		// output users
		$this->view->paginator = $accounts;

		// construct filters
		$range = array();
		foreach($this->user->getMapper()->getDbTable()->getRange() as $one) {
			$prime = ucfirst($this->remove_accents($one['scope']));
			$range[$prime] = $prime;
		}

		// build range
		$filters = array();
		foreach(range('A', 'Z') as $letter) {
			if(in_array($letter, $range)) {
				unset($range[$letter]);
				$filters[$letter] = 1;
			} else
				$filters[$letter] = 0;
		}

		// other
		if(count($range) > 0) $filters['other'] = 1;
		else $filters['other'] = 0;

		// all
		$filters['all'] = 1;

		// output filters
		$this->view->filters = $filters;
	}

	/*
	 * Online list
	*/
	public function onlineAction()
	{
		// set page title
		$this->view->title = $this->translate->_('Online Member List');

		// get page
		$page = $this->request->getParam('page');
		$page = $page ? intval($page) : 0;

		// load users
		$paginator = $this->user->getMapper()->getDbTable()->findWithSession(null, 'paginator');
		$paginator->setItemCountPerPage($this->cfg["users"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// assign user info
		foreach($paginator as &$data)
			$data = $this->_helper->userinfo($data['id']);

		// output users
		$this->view->paginator = $paginator;
	}

	/**
	 * Unaccent the input string string. An example string like `Ã€Ã˜Ä—Ã¿á¾œá½¨Î¶á½…Ð‘ÑŽ`
	 * will be translated to `AOeyIOzoBY`. More complete than :
	 *   strtr( (string)$str,
	 *          "Ã€Ã�Ã‚ÃƒÃ„Ã…Ã Ã¡Ã¢Ã£Ã¤Ã¥Ã’Ã“Ã”Ã•Ã–Ã˜Ã²Ã³Ã´ÃµÃ¶Ã¸ÃˆÃ‰ÃŠÃ‹Ã¨Ã©ÃªÃ«Ã‡Ã§ÃŒÃ�ÃŽÃ�Ã¬Ã­Ã®Ã¯Ã™ÃšÃ›ÃœÃ¹ÃºÃ»Ã¼Ã¿Ã‘Ã±",
	 *          "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn" );
	 *
	 * @param $str input string
	 * @param $utf8 if null, function will detect input string encoding
	 * @author http://www.evaisse.net/2008/php-translit-remove-accent-unaccent-21001
	 * @return string input string without accent
	 */
	private function remove_accents($str, $utf8 = true)
	{
		$str = (string)$str;
		if( is_null($utf8) ) {
			if( !function_exists('mb_detect_encoding') ) {
				$utf8 = (strtolower( mb_detect_encoding($str) )=='utf-8');
			} else {
				$length = strlen($str);
				$utf8 = true;
				for ($i=0; $i < $length; $i++) {
					$c = ord($str[$i]);
					if ($c < 0x80) $n = 0; # 0bbbbbbb
					elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
					elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
					elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
					elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
					elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
					else return false; # Does not match any model
					for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
						if ((++$i == $length)
						|| ((ord($str[$i]) & 0xC0) != 0x80)) {
							$utf8 = false;
							break;
						}

					}
				}
			}
		}

		if(!$utf8)
			$str = utf8_encode($str);

		$transliteration = array(
			'Ä²' => 'I', 'Ã–' => 'O','Å’' => 'O','Ãœ' => 'U','Ã¤' => 'a','Ã¦' => 'a',
			'Ä³' => 'i','Ã¶' => 'o','Å“' => 'o','Ã¼' => 'u','ÃŸ' => 's','Å¿' => 's',
			'Ã€' => 'A','Ã�' => 'A','Ã‚' => 'A','Ãƒ' => 'A','Ã„' => 'A','Ã…' => 'A',
			'Ã†' => 'A','Ä€' => 'A','Ä„' => 'A','Ä‚' => 'A','Ã‡' => 'C','Ä†' => 'C',
			'ÄŒ' => 'C','Äˆ' => 'C','ÄŠ' => 'C','ÄŽ' => 'D','Ä�' => 'D','Ãˆ' => 'E',
			'Ã‰' => 'E','ÃŠ' => 'E','Ã‹' => 'E','Ä’' => 'E','Ä˜' => 'E','Äš' => 'E',
			'Ä”' => 'E','Ä–' => 'E','Äœ' => 'G','Äž' => 'G','Ä ' => 'G','Ä¢' => 'G',
			'Ä¤' => 'H','Ä¦' => 'H','ÃŒ' => 'I','Ã�' => 'I','ÃŽ' => 'I','Ã�' => 'I',
			'Äª' => 'I','Ä¨' => 'I','Ä¬' => 'I','Ä®' => 'I','Ä°' => 'I','Ä´' => 'J',
			'Ä¶' => 'K','Ä½' => 'K','Ä¹' => 'K','Ä»' => 'K','Ä¿' => 'K','Å�' => 'L',
			'Ã‘' => 'N','Åƒ' => 'N','Å‡' => 'N','Å…' => 'N','ÅŠ' => 'N','Ã’' => 'O',
			'Ã“' => 'O','Ã”' => 'O','Ã•' => 'O','Ã˜' => 'O','ÅŒ' => 'O','Å�' => 'O',
			'ÅŽ' => 'O','Å”' => 'R','Å˜' => 'R','Å–' => 'R','Åš' => 'S','Åž' => 'S',
			'Åœ' => 'S','È˜' => 'S','Å ' => 'S','Å¤' => 'T','Å¢' => 'T','Å¦' => 'T',
			'Èš' => 'T','Ã™' => 'U','Ãš' => 'U','Ã›' => 'U','Åª' => 'U','Å®' => 'U',
			'Å°' => 'U','Å¬' => 'U','Å¨' => 'U','Å²' => 'U','Å´' => 'W','Å¶' => 'Y',
			'Å¸' => 'Y','Ã�' => 'Y','Å¹' => 'Z','Å»' => 'Z','Å½' => 'Z','Ã ' => 'a',
			'Ã¡' => 'a','Ã¢' => 'a','Ã£' => 'a','Ä�' => 'a','Ä…' => 'a','Äƒ' => 'a',
			'Ã¥' => 'a','Ã§' => 'c','Ä‡' => 'c','Ä�' => 'c','Ä‰' => 'c','Ä‹' => 'c',
			'Ä�' => 'd','Ä‘' => 'd','Ã¨' => 'e','Ã©' => 'e','Ãª' => 'e','Ã«' => 'e',
			'Ä“' => 'e','Ä™' => 'e','Ä›' => 'e','Ä•' => 'e','Ä—' => 'e','Æ’' => 'f',
			'Ä�' => 'g','ÄŸ' => 'g','Ä¡' => 'g','Ä£' => 'g','Ä¥' => 'h','Ä§' => 'h',
			'Ã¬' => 'i','Ã­' => 'i','Ã®' => 'i','Ã¯' => 'i','Ä«' => 'i','Ä©' => 'i',
			'Ä­' => 'i','Ä¯' => 'i','Ä±' => 'i','Äµ' => 'j','Ä·' => 'k','Ä¸' => 'k',
			'Å‚' => 'l','Ä¾' => 'l','Äº' => 'l','Ä¼' => 'l','Å€' => 'l','Ã±' => 'n',
			'Å„' => 'n','Åˆ' => 'n','Å†' => 'n','Å‰' => 'n','Å‹' => 'n','Ã²' => 'o',
			'Ã³' => 'o','Ã´' => 'o','Ãµ' => 'o','Ã¸' => 'o','Å�' => 'o','Å‘' => 'o',
			'Å�' => 'o','Å•' => 'r','Å™' => 'r','Å—' => 'r','Å›' => 's','Å¡' => 's',
			'Å¥' => 't','Ã¹' => 'u','Ãº' => 'u','Ã»' => 'u','Å«' => 'u','Å¯' => 'u',
			'Å±' => 'u','Å­' => 'u','Å©' => 'u','Å³' => 'u','Åµ' => 'w','Ã¿' => 'y',
			'Ã½' => 'y','Å·' => 'y','Å¼' => 'z','Åº' => 'z','Å¾' => 'z','Î‘' => 'A',
			'Î†' => 'A','á¼ˆ' => 'A','á¼‰' => 'A','á¼Š' => 'A','á¼‹' => 'A','á¼Œ' => 'A',
			'á¼�' => 'A','á¼Ž' => 'A','á¼�' => 'A','á¾ˆ' => 'A','á¾‰' => 'A','á¾Š' => 'A',
			'á¾‹' => 'A','á¾Œ' => 'A','á¾�' => 'A','á¾Ž' => 'A','á¾�' => 'A','á¾¸' => 'A',
			'á¾¹' => 'A','á¾º' => 'A','á¾¼' => 'A','Î’' => 'B','Î“' => 'G','Î”' => 'D',
			'Î•' => 'E','Îˆ' => 'E','á¼˜' => 'E','á¼™' => 'E','á¼š' => 'E','á¼›' => 'E',
			'á¼œ' => 'E','á¼�' => 'E','á¿ˆ' => 'E','Î–' => 'Z','Î—' => 'I','Î‰' => 'I',
			'á¼¨' => 'I','á¼©' => 'I','á¼ª' => 'I','á¼«' => 'I','á¼¬' => 'I','á¼­' => 'I',
			'á¼®' => 'I','á¼¯' => 'I','á¾˜' => 'I','á¾™' => 'I','á¾š' => 'I','á¾›' => 'I',
			'á¾œ' => 'I','á¾�' => 'I','á¾ž' => 'I','á¾Ÿ' => 'I','á¿Š' => 'I','á¿Œ' => 'I',
			'Î˜' => 'T','Î™' => 'I','ÎŠ' => 'I','Îª' => 'I','á¼¸' => 'I','á¼¹' => 'I',
			'á¼º' => 'I','á¼»' => 'I','á¼¼' => 'I','á¼½' => 'I','á¼¾' => 'I','á¼¿' => 'I',
			'á¿˜' => 'I','á¿™' => 'I','á¿š' => 'I','Îš' => 'K','Î›' => 'L','Îœ' => 'M',
			'Î�' => 'N','Îž' => 'K','ÎŸ' => 'O','ÎŒ' => 'O','á½ˆ' => 'O','á½‰' => 'O',
			'á½Š' => 'O','á½‹' => 'O','á½Œ' => 'O','á½�' => 'O','á¿¸' => 'O','Î ' => 'P',
			'Î¡' => 'R','á¿¬' => 'R','Î£' => 'S','Î¤' => 'T','Î¥' => 'Y','ÎŽ' => 'Y',
			'Î«' => 'Y','á½™' => 'Y','á½›' => 'Y','á½�' => 'Y','á½Ÿ' => 'Y','á¿¨' => 'Y',
			'á¿©' => 'Y','á¿ª' => 'Y','Î¦' => 'F','Î§' => 'X','Î¨' => 'P','Î©' => 'O',
			'Î�' => 'O','á½¨' => 'O','á½©' => 'O','á½ª' => 'O','á½«' => 'O','á½¬' => 'O',
			'á½­' => 'O','á½®' => 'O','á½¯' => 'O','á¾¨' => 'O','á¾©' => 'O','á¾ª' => 'O',
			'á¾«' => 'O','á¾¬' => 'O','á¾­' => 'O','á¾®' => 'O','á¾¯' => 'O','á¿º' => 'O',
			'á¿¼' => 'O','Î±' => 'a','Î¬' => 'a','á¼€' => 'a','á¼�' => 'a','á¼‚' => 'a',
			'á¼ƒ' => 'a','á¼„' => 'a','á¼…' => 'a','á¼†' => 'a','á¼‡' => 'a','á¾€' => 'a',
			'á¾�' => 'a','á¾‚' => 'a','á¾ƒ' => 'a','á¾„' => 'a','á¾…' => 'a','á¾†' => 'a',
			'á¾‡' => 'a','á½°' => 'a','á¾°' => 'a','á¾±' => 'a','á¾²' => 'a','á¾³' => 'a',
			'á¾´' => 'a','á¾¶' => 'a','á¾·' => 'a','Î²' => 'b','Î³' => 'g','Î´' => 'd',
			'Îµ' => 'e','Î­' => 'e','á¼�' => 'e','á¼‘' => 'e','á¼’' => 'e','á¼“' => 'e',
			'á¼”' => 'e','á¼•' => 'e','á½²' => 'e','Î¶' => 'z','Î·' => 'i','Î®' => 'i',
			'á¼ ' => 'i','á¼¡' => 'i','á¼¢' => 'i','á¼£' => 'i','á¼¤' => 'i','á¼¥' => 'i',
			'á¼¦' => 'i','á¼§' => 'i','á¾�' => 'i','á¾‘' => 'i','á¾’' => 'i','á¾“' => 'i',
			'á¾”' => 'i','á¾•' => 'i','á¾–' => 'i','á¾—' => 'i','á½´' => 'i','á¿‚' => 'i',
			'á¿ƒ' => 'i','á¿„' => 'i','á¿†' => 'i','á¿‡' => 'i','Î¸' => 't','Î¹' => 'i',
			'Î¯' => 'i','ÏŠ' => 'i','Î�' => 'i','á¼°' => 'i','á¼±' => 'i','á¼²' => 'i',
			'á¼³' => 'i','á¼´' => 'i','á¼µ' => 'i','á¼¶' => 'i','á¼·' => 'i','á½¶' => 'i',
			'á¿�' => 'i','á¿‘' => 'i','á¿’' => 'i','á¿–' => 'i','á¿—' => 'i','Îº' => 'k',
			'Î»' => 'l','Î¼' => 'm','Î½' => 'n','Î¾' => 'k','Î¿' => 'o','ÏŒ' => 'o',
			'á½€' => 'o','á½�' => 'o','á½‚' => 'o','á½ƒ' => 'o','á½„' => 'o','á½…' => 'o',
			'á½¸' => 'o','Ï€' => 'p','Ï�' => 'r','á¿¤' => 'r','á¿¥' => 'r','Ïƒ' => 's',
			'Ï‚' => 's','Ï„' => 't','Ï…' => 'y','Ï�' => 'y','Ï‹' => 'y','Î°' => 'y',
			'á½�' => 'y','á½‘' => 'y','á½’' => 'y','á½“' => 'y','á½”' => 'y','á½•' => 'y',
			'á½–' => 'y','á½—' => 'y','á½º' => 'y','á¿ ' => 'y','á¿¡' => 'y','á¿¢' => 'y',
			'á¿¦' => 'y','á¿§' => 'y','Ï†' => 'f','Ï‡' => 'x','Ïˆ' => 'p','Ï‰' => 'o',
			'ÏŽ' => 'o','á½ ' => 'o','á½¡' => 'o','á½¢' => 'o','á½£' => 'o','á½¤' => 'o',
			'á½¥' => 'o','á½¦' => 'o','á½§' => 'o','á¾ ' => 'o','á¾¡' => 'o','á¾¢' => 'o',
			'á¾£' => 'o','á¾¤' => 'o','á¾¥' => 'o','á¾¦' => 'o','á¾§' => 'o','á½¼' => 'o',
			'á¿²' => 'o','á¿³' => 'o','á¿´' => 'o','á¿¶' => 'o','á¿·' => 'o','Ð�' => 'A',
			'Ð‘' => 'B','Ð’' => 'V','Ð“' => 'G','Ð”' => 'D','Ð•' => 'E','Ð�' => 'E',
			'Ð–' => 'Z','Ð—' => 'Z','Ð˜' => 'I','Ð™' => 'I','Ðš' => 'K','Ð›' => 'L',
			'Ðœ' => 'M','Ð�' => 'N','Ðž' => 'O','ÐŸ' => 'P','Ð ' => 'R','Ð¡' => 'S',
			'Ð¢' => 'T','Ð£' => 'U','Ð¤' => 'F','Ð¥' => 'K','Ð¦' => 'T','Ð§' => 'C',
			'Ð¨' => 'S','Ð©' => 'S','Ð«' => 'Y','Ð­' => 'E','Ð®' => 'Y','Ð¯' => 'Y',
			'Ð°' => 'A','Ð±' => 'B','Ð²' => 'V','Ð³' => 'G','Ð´' => 'D','Ðµ' => 'E',
			'Ñ‘' => 'E','Ð¶' => 'Z','Ð·' => 'Z','Ð¸' => 'I','Ð¹' => 'I','Ðº' => 'K',
			'Ð»' => 'L','Ð¼' => 'M','Ð½' => 'N','Ð¾' => 'O','Ð¿' => 'P','Ñ€' => 'R',
			'Ñ�' => 'S','Ñ‚' => 'T','Ñƒ' => 'U','Ñ„' => 'F','Ñ…' => 'K','Ñ†' => 'T',
			'Ñ‡' => 'C','Ñˆ' => 'S','Ñ‰' => 'S','Ñ‹' => 'Y','Ñ�' => 'E','ÑŽ' => 'Y',
			'Ñ�' => 'Y','Ã°' => 'd','Ã�' => 'D','Ã¾' => 't','Ãž' => 'T','áƒ�' => 'a',
			'áƒ‘' => 'b','áƒ’' => 'g','áƒ“' => 'd','áƒ”' => 'e','áƒ•' => 'v','áƒ–' => 'z',
			'áƒ—' => 't','áƒ˜' => 'i','áƒ™' => 'k','áƒš' => 'l','áƒ›' => 'm','áƒœ' => 'n',
			'áƒ�' => 'o','áƒž' => 'p','áƒŸ' => 'z','áƒ ' => 'r','áƒ¡' => 's','áƒ¢' => 't',
			'áƒ£' => 'u','áƒ¤' => 'p','áƒ¥' => 'k','áƒ¦' => 'g','áƒ§' => 'q','áƒ¨' => 's',
			'áƒ©' => 'c','áƒª' => 't','áƒ«' => 'd','áƒ¬' => 't','áƒ­' => 'c','áƒ®' => 'k',
			'áƒ¯' => 'j','áƒ°' => 'h'
		);

		return str_replace( array_keys( $transliteration ), array_values( $transliteration ), $str);
	}

	/*
	 * Build user search filter
	 */
	private function buildSearchFilter() {
		$search = array();
		$filter = array('active = 1 and is_banned != 1');
		$this->view->title = $this->translate->_('Member List');
		$this->view->search = false;
		$this->view->filtered = false;

		if (strlen($this->request->getParam('filter'))) {
			if($this->request->getParam('filter') == 'all') {
				$filter[] = "1 = 1";
				$this->view->filtered = $this->translate->_("Showing All Members");
			} elseif($this->request->getParam('filter') == 'other') {
				$filter[] = "name not regexp '^[[:alpha:]]'";
				$this->view->filtered = $this->translate->_("Filtered by non alphabetic characters");
			} else {
				$filter[] = "name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote(strtolower($this->request->getParam('filter'))."%");
				$this->view->filtered = sprintf($this->translate->_("Filtered by the letter %s"), ucfirst($this->request->getParam('filter')));
			}
		}

		if (strlen($this->request->getParam('keyword'))) {
			$filter[] = "name LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('keyword'))."%");
			$search[] = $this->request->getParam('keyword');
		}

		if (strlen($this->request->getParam('country'))) {
			$filter[] = "country_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('country'), Zend_Db::BIGINT_TYPE);
			$search[] = $this->view->countries[$this->request->getParam('country')];
		}

		if (strlen($this->request->getParam('zipcode'))) {
			$filter[] = "zipcode LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->request->getParam('zipcode')."%");
			$search[] = $this->request->getParam('zipcode');
		}

		if (strlen($this->request->getParam('address'))) {
			$filter[] = "address LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".$this->request->getParam('address')."%");
			$search[] = $this->request->getParam('address');
		}

		if (strlen($this->request->getParam('location'))) {
			$filter[] = "location LIKE ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote("%".strtolower($this->request->getParam('location'))."%");
			$search[] = $this->request->getParam('location');
		}

		if (strlen($this->request->getParam('gender'))) {
			$filter[] = "gender = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('gender'), Zend_Db::INT_TYPE);
			$search[] = $this->view->genders[$this->request->getParam('gender')];
		}

		if (strlen($this->request->getParam('type'))) {
			$filter[] = "type = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->request->getParam('type'), Zend_Db::INT_TYPE);
			$search[] = $this->view->types[$this->request->getParam('type')];
		}

		if(count($search) > 0) {
			$this->view->title = $this->translate->_('Results, Search:') . ' ' . implode(', ', $search);
			$this->view->search = true;
		} else {
			// if there is no search criteria then we show some random users with avatar
			$filter[] = "avatar IS NOT NULL";
		}

		return implode(' AND ', $filter);
	}

	/**
	 * View user
	 */
	public function viewAction()
	{
		// no user id ? STOP LOOKING AT ME!!!
		if(!$this->user->getId())
			return $this->_helper->redirector('index', 'site');

		// is admin
		$this->view->admin = ($this->auth->hasIdentity() && $this->user->getId() == $this->auth->getIdentity()->id);

		// load profile
		$this->view->data = $this->_helper->userinfo($this->user->getId());
		if(isset($this->view->data['micro']))
			$this->view->microsite = $this->view->data['micro'];

		// active or banned
		if(!($this->view->data['active'] == 1 && $this->view->data['is_banned'] != 1)) {
			$this->msg->messages[] = $this->translate->_("That account is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		// friend_zoned
		$this->view->friend_zoned = false;
		if($this->auth->hasIdentity())
			$this->view->friend_zoned = in_array($this->auth->getIdentity()->id, $this->loadFriendsAndPartners());

		$this->loadUserPets($this->user->getId(), $this->request->getParam('pets-page'));
		$this->loadUserGalleries($this->user->getId());
		if ($this->user->getType() == 2) {
			$this->loadUserServices($this->user->getId(), $this->request->getParam('page'));
			$this->loadUserProducts($this->user->getId());
			// init map
			$this->initMap();
		}

		// load types, colors, countries and is service
		$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// get your upcoming events
		list($this->view->your_events, $this->view->your_events_json) = $this->loadUserEvents($this->request->getParam('your-event-page'));

		// load dashboard
		$this->loadDashboard();
	}

	/**
	 * Load friends and partners
	 * @param string $what - friends / partners
	 *
	 * @return array of friends or partners
	 */
	private function loadFriendsAndPartners($what = 'friends')
	{
		// load user's friends and partners
		$all = $what == 'friends' ? $this->user->getUserFriends() : $this->user->getUserPartners();

		// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[] = $row->getId();

		// return
		return $result;
	}

	/**
	 * loads the user's dashboard
	 */
	private function loadDashboard() {
		list($results, $more) = $this->dash->load($this->user->getId());

		$this->view->profile_user = json_encode($this->user->toArray());

		// output results
		$this->view->results = $results;
		$this->view->more = $more;
	}

	/**
	 * loads the user's public/special events
	 *
	 * @param int $page
	 */
	private function loadUserEvents($page = 0, $more = 21) {
		// format event in calendar template
		$the_start = new DateTime('now');
		$the_end = clone $the_start;
		$the_end->add(new DateInterval('P'. ($more * intval($this->cfg['events']['days'])) .'D'));
		$in = array();
		$calendar = new Petolio_Model_PoCalendar();
		foreach($calendar->getMapper()->browseYourEvents($this->user->getId(), $the_start, $the_end) as $line) {
			$array = Petolio_Service_Calendar::format($line);
			$array['astatus'] = $line['astatus'];
			$array['atype'] = $line['atype'];

			// are we logged in ?
			if($this->auth->getIdentity()) {
				// if the we're looking at our profile, allow the accept / decline buttons
				if($this->auth->getIdentity()->id == $this->user->getId()) {
					$array['invited'] = $line['atype'] === '0' && $line['astatus'] === '0' ? true : false;
					$array['accepted'] = $line['atype'] === '0' && $line['astatus'] === '1' ? true : false;

				// if someone else is looking at our profile, display the join event button (if not owner)
				} else {
					if($this->auth->getIdentity()->id != $array['user_id'])
						$array['requested'] = true;
				}
			}

			$in[] = $array;
		}

		// if another user is looking at my profile apply filters
		foreach($in as $k => $one) {
			if($this->auth->hasIdentity() && $this->auth->getIdentity()->id == $this->user->getId()) {}
			else {
				// delete private events (show only special events)
				if($one['type'] != 3) unset($in[$k]);

				// delete pending events
				if(isset($one['astatus']) && $one['astatus'] == 0) unset($in[$k]);
			}
		}

		// master repeats
		$results = Petolio_Service_Calendar::masterRepeats($in);

		// filter out events that have expired (remember to look out for all day events as well as continuous events)
		$now = clone $the_start;
		foreach($results as $idx => $line) {
			$start = new DateTime(date('Y-m-d H:i:s', $line['start']));
			$end = $line['end'] ? new DateTime(date('Y-m-d H:i:s', $line['end'])) : null;

			if($line['allDay'])
				$now->setTime(0, 0, 0);

			// if start is bigger than 7 days, unset
			if($start > $the_end)
				unset($results[$idx]);

			// unset if the event passed but check if the event is still running
			if($start < $now) {
				if($end) {
					if($end < $now)
						unset($results[$idx]);
				} else
					unset($results[$idx]);
			}

			// earlier we set the time to 00:00, and we reset it for the next event
			if($line['allDay'])
				$now = new DateTime('now');
		}

		// do sorting
		$this->view->event_order = $this->request->getParam('event_order');
		$this->view->event_dir = $this->request->getParam('event_dir') == 'desc' ? 'desc' : 'asc';
		$this->view->event_rdir = $this->view->event_dir == 'asc' ? 'desc' : 'asc';

		// add sorting value
		foreach($results as $idx => $line) {
			if($this->view->event_order == 'name') $sort = $line['title'];
			elseif($this->view->event_order == 'type') $sort = $line['type'];
			elseif($this->view->event_order == 'owner') $sort = $line['user_name'];
			else {
				$this->view->event_order = 'date';
				$sort = $line['start'];
			}

			$results[$idx] = array_merge($line, array('sort' => $sort));
		}

		// perform sort
		Petolio_Service_Util::array_sort($results, array("sort" => $this->view->event_dir == 'asc' ? true : false));

		// pagination
		$result = Zend_Paginator::factory($results);
		$result->setItemCountPerPage(5);
		$result->setCurrentPageNumber($page);

		// prep for json encode
		$out = array();
		foreach($result as $line)
			$out[] = $line;

		// return json and object
		return array($result, json_encode($out));
	}

	/*
	 * Load User's Services
	 */
	private function loadUserServices($id, $page) {
		// get page
		$page = $page ? intval($page) : 0;

		// do sorting 1
		$this->view->order = $this->request->getParam('order');
		$this->view->dir = $this->request->getParam('dir') == 'asc' ? 'asc' : 'desc';
		$this->view->rdir = $this->view->dir == 'asc' ? 'desc' : 'asc';

		// do sorting 2
		if($this->view->order == 'name') $sort = "d1.value {$this->view->dir}";
		elseif($this->view->order == 'type') $sort = "type {$this->view->dir}";
		else {
			$this->view->order = 'date';
			$sort = "a.date_created {$this->view->dir}";
		}

		// get services
		$paginator = $this->serv->getServices('paginator', "a.user_id = {$id}", $sort);
		$paginator->setItemCountPerPage($this->cfg["shared_services"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output services
		$this->view->yourServices = $this->serv->formatServices($paginator);
	}

	/*
	 * Load User's Pets
	 */
	private function loadUserPets($id, $page) {
		// get page
		$page = $page ? intval($page) : 0;

		// get pets
		$paginator = $this->pets->getPets('paginator', "a.user_id = {$id} AND deleted = '0'", "id DESC");
		$paginator->setItemCountPerPage($this->cfg["shared_pets"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber($page);

		// output pets
		$this->view->yourPets = $this->pets->formatPets($paginator);
	}

	/*
	 * Load User's Galleries
	 */
	private function loadUserGalleries($id) {
    	$filter = array(
    		"deleted = 0",
    		"a.owner_id = {$id}"
    	);
		// get galleries
		$galleries = new Petolio_Model_PoGalleries();
		$paginator = $galleries->fetchListToPaginator($filter, "date_created DESC");
		$paginator->setItemCountPerPage($this->cfg["shared_pets"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber(0);

		// go through each item to add picture
		$files = new Petolio_Model_PoFiles();
		foreach($paginator as &$item) {
			// take the first picture
			$picture = !is_null($item['folder_id']) ? $files->fetchList("folder_id = {$item['folder_id']} AND type = 'image'", "date_created ASC") : array();
			$item['picture'] = !count($picture) > 0 ? null : reset($picture)->getFile();
			$item['pictures_count'] = count($picture);
		}

		// output galleries
		$this->view->yourGalleries = $paginator;
	}

	/*
	 * Load User's Products
	 */
	private function loadUserProducts($id) {
    	$filter = "a.archived = 0 AND a.user_id = {$id}";

		// get products
		$products = new Petolio_Model_PoProducts();
		$paginator = $products->getProducts('paginator', $filter, "date_created DESC");
		$paginator->setItemCountPerPage($this->cfg["shared_pets"]["pagination"]["itemsperpage"]);
		$paginator->setCurrentPageNumber(0);

		// output products
		$this->view->yourProducts = $products->formatProducts($paginator);
	}

	/*
	 * Activate user's account action
	 */
	public function activateAction()
	{
		// already logged in ?
		if ($this->auth->hasIdentity())
			return $this->_helper->redirector('index', 'site');

		// find user by hash
		$result = $this->user->getMapper()->findByField('SHA1(CONCAT(password, id))', $this->request->getParam('hash'), $this->user);
		if (is_array($result) && count($result) > 0) {
			$user = reset($result);
			/*
			 * add user  as member of Petolio Service: Upgrade Service
			 */
			if ( $user->getActive() != 1 ) {
				$service = new Petolio_Model_PoServices();
				$service->find(570); // service id is 570 <HARDCODED>
				if ( $service->getId() ) { // service was found
					$link = new Petolio_Model_PoServiceMembersUsers();
					$link->setUserId($user->getId());
					$link->setServiceId($service->getId());
					$link->setStatus(1); // accepted link
					$link->save();
				}
			}

			// activate
			$user->setActive(1)->save();

			// msg
			$this->msg->messages[] = $this->translate->_("Your account was activated with success.");
		} else $this->msg->messages[] = $this->translate->_("User does not exists.");

		// redirect
		return $this->_helper->redirector('index', 'site');
	}

	/*
	 * Forgot password action
	 */
	public function forgotAction()
	{
		// already logged in ?
		if ($this->auth->hasIdentity())
			return $this->_helper->redirector('index', 'site');

		// init form
		$form = new Petolio_Form_Forgot();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// find user
		$data = $form->getValues();
		$result = $this->user->getMapper()->findByField('email', $data['email'], $this->user);
		if (is_array($result) && count($result) > 0) {
			// save timestamp (link active for 7 days)
			$user = reset($result);
			$user->setDateForgot(time())->save();

			// send the email
			$this->email->setRecipient($user->getEmail());
			$this->email->setTemplate('users/recover');
			$this->email->activationLink = PO_BASE_URL . 'accounts/recover/hash/' . sha1($user->getPassword() . $user->getId());
			$this->email->name = $user->getName();
			$this->email->base_url = PO_BASE_URL;
			$this->email->send();

			// msg
			$this->msg->messages[] = $this->translate->_("We have sent you an e-mail with instructions on how to reset your password.");
		} else $this->msg->messages[] = $this->translate->_("User does not exists.");

		// redirect when done
		return $this->_helper->redirector('index', 'site');
	}

	/*
	 * Recover password action
	 */
	public function recoverAction()
	{
		// already logged in ?
		if ($this->auth->hasIdentity())
			return $this->_helper->redirector('index', 'site');

		// find user
		$result = $this->user->getMapper()->findByField('SHA1(CONCAT(password, id))', $this->getRequest()->getParam('hash'), $this->user);
		if (!(is_array($result) && count($result) > 0)) {
			$this->msg->messages[] = $this->translate->_("User does not exists.");
			return $this->_helper->redirector('index', 'site');
		}

		// tried to access link before 7 days ?
		$user = reset($result);
		if(!($user->getDateForgot() > time() - 604800)) {
			$this->msg->messages[] = $this->translate->_("Sorry, that link has expired.");
			return $this->_helper->redirector('index', 'site');
		}

		// init form
		$form = new Petolio_Form_Recover();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// save new pass
		$data = $form->getValues();
		$user->setPassword(sha1($data["password"]));
		$user->setDateForgot('0');
		$user->save(true, true);

		// message & redirect
		$this->msg->messages[] = $this->translate->_("You have successfully changed your password.");
		return $this->_helper->redirector('index', 'site');
	}

	/*
	 * My Profile view action
	 */
	public function profileAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// load profile
		$this->view->data = $this->_helper->userinfo($this->user->getId());

		// is admin
		$this->view->admin = true;

		// active or banned
		if(!($this->view->data['active'] == 1 && $this->view->data['is_banned'] != 1)) {
			$this->msg->messages[] = $this->translate->_("Your account is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}

		$this->loadUserPets($this->user->getId(), $this->request->getParam('pets-page'));
		$this->loadUserGalleries($this->user->getId());
		if ($this->user->getType() == 2) {
			$this->loadUserServices($this->user->getId(), $this->request->getParam('page'));
			$this->loadUserProducts($this->user->getId());
			// init map
			$this->initMap();
		}

		// load types, colors, countries and is service
		$this->view->c_types = json_encode(Petolio_Service_Calendar::getTypes());
		$this->view->c_colors = json_encode(Petolio_Service_Calendar::getColors());
		$this->view->c_countries = json_encode(Petolio_Service_Calendar::getCountries());
		$this->view->c_species =  json_encode(Petolio_Service_Calendar::getSpecies());
		$this->view->c_mods =  json_encode(Petolio_Service_Calendar::getMods());
		$this->view->c_users = json_encode(Petolio_Service_Calendar::getUsers());
		$this->view->c_pets = json_encode(Petolio_Service_Calendar::getPets());
		$this->view->c_error = $this->auth->hasIdentity() ? $this->auth->getIdentity()->type : 0;

		// get your upcoming events
		list($this->view->your_events, $this->view->your_events_json) = $this->loadUserEvents($this->request->getParam('your-event-page'));

		// load dashboard
		$this->loadDashboard();
	}

	/*
	 * init map
	 */
	private function initMap() {
		$filter = array();

		if ($this->user && $this->user->getId() > 0) {
			$filter[] = "a.user_id = ".Zend_Db_Table_Abstract::getDefaultAdapter()->quote($this->user->getId());
		}

		// build filter
		$filter[] = "a.gps_latitude IS NOT NULL";
		$filter[] = "a.gps_longitude IS NOT NULL";
		$filters = implode(' AND ', $filter);

		$sort = "RAND(".date("Ymd").")";

		$cache = Zend_Registry::get('Zend_Cache');
		$cacheID = Petolio_Service_Util::createCacheID("Accounts_Services_".$filters."_".$sort."_".$page);

		if (false === ($services = $cache->load($cacheID))) {
			$po_services = new Petolio_Model_PoServices();
			$services = $po_services->getServices('array', $filters, $sort, 100, false);

			$cache->save($services, $cacheID);
		}

		// return services
		if($services && count($services) > 0) {
			$this->view->show_map = true;
			$this->view->coords = $this->europe;
			$this->getRequest()->setParam("user", $this->user->getId());
		} else {
			$this->view->show_map = false;
		}
	}

	/*
	 * My Profile edit action
	 */
	public function editAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// no user ?
		if (is_null($this->user->getId())) {
			$this->msg->messages[] = $this->translate->_("User does not exists.");
			return $this->_helper->redirector('index', 'site');
		}

		// init form
		$form = new Petolio_Form_Profile($this->user->getType(), $this->auth->getIdentity()->id);
		$form->populate($this->user->getMapper()->toArray($this->user));
		$this->view->form = $form;
		$this->view->user_type = $this->user->getType();

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// find user
		$data = $form->getValues();

		// format data
		foreach($data as $idx => &$line) {
			if(is_array($line) && $idx == 'date_of_birth') {
				if(empty($line['year']) && empty($line['month']) && empty($line['day'])) $line = NULL;
				else $line = "{$line['year']}-{$line['month']}-{$line['day']}";
			} else {
				if(!(strlen($line) > 0)) $line = NULL;
			}
		}

		// save data
		$this->user->setOptions($data);
		$this->user->setDateModified(date('Y-m-d H:i:s'));
		$this->user->save(false, false);

		// update forum data
		$flux = new Petolio_Service_FluxBB();
		$flux->updateUser(array('username' => $data['name'], 'email' => $data['email']), $this->user->getId());

		// check if email changed
		if($data['email'] != $this->auth->getIdentity()->email) {
			// change user to active 0
			$this->user->setActive('0')->save();

			// send confirmation mail
			$this->email->setRecipient($data["email"]);
			$this->email->setTemplate('users/reactivate');
			$this->email->activationLink = PO_BASE_URL . 'accounts/activate/hash/' . sha1($this->user->getPassword() . $this->user->getId());
			$this->email->name = $data["name"];
			$this->email->base_url = PO_BASE_URL;
			$this->email->send();

			// logout
			$this->msg->messages[] = $this->translate->_("You have succesfully saved your profile, however you have changed your email address.");
			$this->msg->messages[] = $this->translate->_("An email was sent to your new email address. Please click on the attached link to reactivate your account.");
			return $this->logoutAction(false);
		}

		// msg + redirect
		$this->msg->messages[] = $this->translate->_("Profile information updated successfully.");
		if ( (is_null($this->user->getCover()) || strlen($this->user->getCover()) < 1)
				&& $this->user->getType() == 2 ) { // redirect new service providers to the cover page
			return $this->_helper->redirector('cover', 'accounts');
		}
		return $this->_helper->redirector('profile', 'accounts');
	}

	/*
	 * My Avatar action
	*/
	public function pictureAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$this->auth->getIdentity()->id}{$ds}";

		// create form
		$form = new Petolio_Form_Avatar();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the users id directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				$this->msg->messages[] = $this->translate->_("There was an critical error regarding the creation of the user's folder on disk.");
				return $this->_redirect('accounts/picture');
			}
		}

		// get adapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('IsImage', false);

		// getting the max filesize
		$config = Zend_Registry::get('config');
		$size = $config['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if (!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->msg->messages[] = sprintf($this->translate->_("Your picture exceed the maximum size limit allowed (%s)"), $this->cfg['phpSettings']['upload_max_filesize']);
				return $this->_redirect('accounts/picture');
			}
		}

		// no file ?
		if(!$adapter->getFileName()) {
			$this->msg->messages[] = $this->translate->_("Please select a picture file to upload.");
			return $this->_redirect('accounts/picture');
		}

		// pre-process file
		$file = $adapter->getFileName();
		$new_filename = md5(time()) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);
		$adapter->clearFilters();
		$adapter->addFilter('Rename', array('target' => $upload_dir . $new_filename, 'overwrite' => true));

		// error on upload ?
		if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) {
			$this->msg->messages[] = reset($adapter->getMessages());
			return $this->_redirect('accounts/picture');
		}

		// process uploaded picture
		$pic = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;

		// delete previous picture
		if(!is_null($this->user->getAvatar())) {
			@unlink($upload_dir . $this->user->getAvatar());
			@unlink($upload_dir . 'thumb_' . $this->user->getAvatar());
		}

		$props = @getimagesize($pic);

		// make thumbnail
		list($w, $h) = explode('x', $this->cfg["thumbnail"]["account"]["small"]);
		Petolio_Service_Image::output($pic, pathinfo($pic, PATHINFO_DIRNAME) . $ds . 'thumb_' . pathinfo($pic, PATHINFO_BASENAME), array(
			'type'   => IMAGETYPE_JPEG,
			'width'   => $w,
			'height'  => $h,
			'method'  => THUMBNAIL_METHOD_SCALE_MIN,
			'valign'  => THUMBNAIL_ALIGN_TOP,
			'halign'  => THUMBNAIL_ALIGN_CENTER
			));

		// make big
		list($w, $h) = explode('x', $this->cfg["thumbnail"]["account"]["big"]);
		if($props[0] > $w || $props[1] > $h) {
			Petolio_Service_Image::output($pic, $pic, array(
				'type'   => IMAGETYPE_JPEG,
				'width'   => $w,
				'height'  => $h,
				'method'  => THUMBNAIL_METHOD_SCALE_MIN,
				'valign'  => THUMBNAIL_ALIGN_TOP,
				'halign'  => THUMBNAIL_ALIGN_CENTER
				));
		}

		// save avatar
		$this->user->setAvatar(pathinfo($pic, PATHINFO_BASENAME))->save();

		// redirect
		$this->msg->messages[] = $this->translate->_("Your profile picture has been uploaded successfully.");
		return $this->_redirect('accounts/profile');
	}

	/*
	 * My Cover action
	*/
	public function coverAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// needed upfront
		$ds = DIRECTORY_SEPARATOR;
		$upload_dir = "..{$ds}data{$ds}userfiles{$ds}avatars{$ds}{$this->auth->getIdentity()->id}{$ds}";

		// create form
		$form = new Petolio_Form_Cover();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!$this->request->isPost())
			return false;

		// create the users id directory
		if (!file_exists($upload_dir)) {
			if (!mkdir($upload_dir)) {
				$this->msg->messages[] = $this->translate->_("There was an critical error regarding the creation of the user's folder on disk.");
				return $this->_redirect('accounts/cover');
			}
		}

		// get adapter
		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($upload_dir);
		$adapter->addValidator('IsImage', false);

		// getting the max filesize
		$config = Zend_Registry::get('config');
		$size = $config['max_filesize'];
		$adapter->addValidator('Size', false, $size);

		// check if files have exceeded the limit
		if (!$adapter->isValid()) {
			$msg = $adapter->getMessages();
			if(isset($msg['fileUploadErrorIniSize']) && $msg['fileUploadErrorIniSize'] == "File '' exceeds the defined ini size") {
				$this->msg->messages[] = sprintf($this->translate->_("Your picture exceed the maximum size limit allowed (%s)"), $this->cfg['phpSettings']['upload_max_filesize']);
				return $this->_redirect('accounts/cover');
			}
		}

		// no file ?
		if(!$adapter->getFileName()) {
			if (intval($this->getRequest()->getParam("selected_cover", "0")) > 0) {
				// save selected cover
				$this->user->setCover($this->getRequest()->getParam("selected_cover"))->save();

				// redirect
				$this->msg->messages[] = $this->translate->_("Your cover picture has been updated successfully.");
				return $this->_redirect('accounts/profile');
			} else {
				$this->msg->messages[] = $this->translate->_("Please select a template or upload a picture file.");
				return $this->_redirect('accounts/cover');
			}
		}

		// pre-process file
		$file = $adapter->getFileName();
		$new_filename = md5(time()) . '.' . pathinfo(strtolower($file), PATHINFO_EXTENSION);
		$adapter->clearFilters();
		$adapter->addFilter('Rename', array('target' => $upload_dir . $new_filename, 'overwrite' => true));

		// error on upload ?
		if(!$adapter->receive(pathinfo($file, PATHINFO_BASENAME))) {
			$this->msg->messages[] = reset($adapter->getMessages());
			return $this->_redirect('accounts/cover');
		}

		// process uploaded picture
		$pic = pathinfo($file, PATHINFO_DIRNAME) . $ds . $new_filename;

		// delete previous picture
		if(!is_null($this->user->getCover())) {
			@unlink($upload_dir . $this->user->getCover());
		}

		// resize picture
		$props = @getimagesize($pic);
		list($w, $h) = explode('x', $this->cfg["thumbnail"]["account"]["cover"]);
		if($props[0] > $w || $props[1] > $h) {
			Petolio_Service_Image::output($pic, $pic, array(
			'type'   => IMAGETYPE_JPEG,
			'width'   => $w,
			'height'  => $h,
			'method'  => THUMBNAIL_METHOD_SCALE_MAX
			));
		}

		// save cover
		$this->user->setCover(pathinfo($pic, PATHINFO_BASENAME))->save();

		// redirect
		$this->msg->messages[] = $this->translate->_("Your cover picture has been uploaded successfully.");
		return $this->_redirect('accounts/profile');
	}

	/*
	 * Change password action
	 */
	public function passwordAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// no user ?
		if (is_null($this->user->getId())) {
			$this->msg->messages[] = $this->translate->_("User does not exists.");
			return $this->_helper->redirector('index', 'site');
		}

		// init form
		$form = new Petolio_Form_Password();
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// save new password
		$data = $form->getValues();
		$this->user->setPassword(sha1($data["password"]));
		$this->user->save(true, true);

		// logout
		$this->msg->messages[] = $this->translate->_("You have succesfully changed your password. Please login with your new password.");
		return $this->logoutAction(false);
	}

	/*
	 * Change password action
	 */
	public function emailAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// no user ?
		if (is_null($this->user->getId())) {
			$this->msg->messages[] = $this->translate->_("User does not exists.");
			return $this->_helper->redirector('index', 'site');
		}

		// init form
		$form = new Petolio_Form_Email();
		$form->populate($this->user->getMapper()->toArray($this->user));
		$this->view->form = $form;

		// did we submit form ? if not just return here
		if(!($this->request->isPost() && $this->request->getPost('submit')))
			return false;

		// is the form valid ? if not just return here
		if(!$form->isValid($this->request->getPost()))
			return false;

		// save new email settings
		$data = $form->getValues();
		$this->user->setOptions($data)->save(true, true);

		// logout
		$this->msg->messages[] = $this->translate->_("You have succesfully changed your email notifications.");
		return $this->_redirect('accounts/profile');
	}

	/**
	 * Logout action
	 * @param bool $_w_msg - With message ?
	 */
	public function logoutAction($_w_msg = true)
	{
		// not logged in ?
		if (!$this->auth->hasIdentity())
			return $this->_helper->redirector('index', 'site');

		// unset the session_id
		$po_users = new Petolio_Model_PoUsers();
		$po_users->find($this->auth->getIdentity()->id);
		$po_users->setSessionId(new Zend_Db_Expr('NULL'));
		$po_users->save(false);

		// clear instance
		Zend_Auth::getInstance()->clearIdentity();

		// clear APE cookie
		$matrix_a = array('/', '/chat/view/id');
		$matrix_b = array($_SERVER['SERVER_NAME'], '.' . $_SERVER['SERVER_NAME']);
		foreach($matrix_a as $one)
			foreach($matrix_b as $two)
				setcookie('APE_Cookie', '', time() - 86400, $one, $two);

		// msg
		if($_w_msg === true)
			$this->msg->messages[] = $this->translate->_("You have succesfully logged out.");

		// forum logout
		$flux = new Petolio_Service_FluxBB();
		$flux->logout();

		// redirec when done
		return $this->_helper->redirector('index', 'site');
	}

	/*
	 * Get permissions
	 */
	public function getPermsAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity())
			return Petolio_Service_Util::json(array('success' => false));

		// find privacy settings for all the fields in request
		$result = array();
		foreach($_REQUEST['fields'] as $field)
			$result[$field] = $this->pfr->getMapper()->findPrivacySetting($field, $this->auth->getIdentity()->id);

		// return json with settings
		return Petolio_Service_Util::json(array('success' => true, 'settings' => $result));
	}

	/*
	 * Set permissions
	 */
	public function setPermsAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity())
			return Petolio_Service_Util::json(array('success' => false));

		// save and return json
		$this->pfr->getMapper()->setPrivacySetting($_REQUEST['field'], $_REQUEST['value'], $this->auth->getIdentity()->id, $this->pfru);
		return Petolio_Service_Util::json(array('success' => true));
	}

	/*
	 * Get friends and parteners for custom permission
	 */
	public function getCustomUsersAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity())
			return Petolio_Service_Util::json(array('success' => false));

		// load user's friends and partners
		$this->user->find($this->auth->getIdentity()->id, $this->user);
		$all = array_merge($this->user->getUserFriends(), $this->user->getUserPartners());
		ksort($all); // sort friends / partners

		// filter out what we dont need
		$result = array();
		foreach($all as $row)
			$result[$row->getId()] = array('name' => $row->getName());

		// return friends + partners
		return Petolio_Service_Util::json(array('success' => true, 'users' => $result));
	}

	/*
	 * Get saved users for custom permission
	 */
	public function getCustomPermsAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity())
			return Petolio_Service_Util::json(array('success' => false));

		// user list
		$out = array();
		$result = $this->pfr->getMapper()->findCustomUsers($_REQUEST['field'], $_REQUEST['value'], $this->auth->getIdentity()->id, $this->pfru);
		if($result)
			foreach($result as $one)
				$out[] = $one->getUserId();

		// return user list
		return Petolio_Service_Util::json(array('success' => true, 'users' => $out));
	}

	/*
	 * Save selected users for custom permission
	 */
	public function setCustomPermsAction()
	{
		// not logged in ?
		if (!$this->auth->hasIdentity())
			return Petolio_Service_Util::json(array('success' => false));

		// save setting & save users
		$id = $this->pfr->getMapper()->setPrivacySetting($_REQUEST['field'], $_REQUEST['value'], $this->auth->getIdentity()->id, $this->pfru);
		$this->pfru->getMapper()->setCustomUsers($id, $_REQUEST['users']);

		// return json
		return Petolio_Service_Util::json(array('success' => true));
	}

	/**
	 * View user info details
	 */
	public function viewInfoAction() {
		// no user id ? STOP LOOKING AT ME!!!
		if(!$this->user->getId())
			return $this->_helper->redirector('index', 'site');

		// is admin
		$this->view->admin = ($this->auth->hasIdentity() && $this->user->getId() == $this->auth->getIdentity()->id);

		// load profile
		$this->view->data = $this->_helper->userinfo($this->user->getId());

		// active or banned
		if(!($this->view->data['active'] == 1 && $this->view->data['is_banned'] != 1)) {
			$this->msg->messages[] = $this->translate->_("That account is inactive or has been banned.");
			return $this->_helper->redirector('index', 'site');
		}
	}

	public function welcomeAction() {
		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// get example folder
		$folder = new Petolio_Model_PoFolders();
		$folder = reset($folder->fetchList("name = 'petolio-sp-page-examples8725'"));
		if ($folder) {
			// get example files
			$files = new Petolio_Model_PoFiles();
			$this->view->example_files = $files->fetchList("folder_id = {$folder->getId()}", "date_created DESC");
		}

	}

	public function deactivateAction() {

		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		$content = $this->translate->_('Are you sure you want to deactivate your account?');

		// Redirect to logout
		$redirect_url = $this->_helper->url('deactivate-submit', 'accounts');

		while(substr($redirect_url, 0, 1) == '/') {
			$redirect_url = substr($redirect_url, 1, strlen($redirect_url) - 1);
		}

		return Petolio_Service_Util::json(array('success' => true, 'content' => $content, 'redirect_url' => $redirect_url));

	}

	public function deactivateSubmitAction() {

		// not logged in ?
		if (!$this->auth->hasIdentity()) {
			Petolio_Service_Util::saveRequest();
			$this->msg->messages[] = $this->translate->_("Please log in or sign up to access this page.");
			return $this->_helper->redirector('index', 'site');
		}

		// set the user as inactive
		$this->user->setActive(0);
		$this->user->save();

		// redirect to logout
		$redirect_url = $this->_helper->url('logout', 'accounts');
		return $this->_helper->redirector('logout', 'accounts');

	}

}

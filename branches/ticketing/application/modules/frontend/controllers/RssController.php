<?php

class RssController extends Zend_Controller_Action
{
	private $translate = null;
	private $db = null;

	public function init() {
		// load custom objects
		$this->translate = Zend_Registry::get('Zend_Translate');
		$this->db = new stdClass();

		// load models
		$this->db->rss = new Petolio_Model_PoRss();
	}

	/**
	 * Index
	 */
	public function indexAction() {
		// output correct header
		header("Content-Type: application/xml; charset=utf-8");

		// get rss items
		$paginator = $this->db->rss->fetchListToPaginator(array(), "date_created DESC");
		$paginator->setItemCountPerPage(50);
		$paginator->setCurrentPageNumber(0);

		// output xml
		$out = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
		$out .= '<rss version="2.0">'."\n";
		$out .= '	<channel>'."\n";
		$out .= '		<title><![CDATA['. $this->translate->_("Petolio RSS Channel") .']]></title>'."\n";
		$out .= '		<link>'. htmlentities(PO_BASE_URL) .'</link>'."\n";
		$out .= '		<description><![CDATA['. $this->translate->_("A portal for Pet Owners and Pet Service Providers") .']]></description>'."\n";
		$out .= '		<language>'. $this->translate->getLocale() .'</language>'."\n";

		// go through each news item
		foreach($paginator as $one) {
			$objDate = new DateTime($one['date_created']);

			$out .= '		<item>'."\n";
			$out .= '			<title>'. Petolio_Service_Util::escape($one["title"]) .'</title>'."\n";
			$out .= '			<link>'. htmlentities($one["link"]) .'</link>'."\n";
			$out .= '			<guid>'. htmlentities($one["link"]) .'</guid>'."\n";
			$out .= '			<author><![CDATA['. Petolio_Service_Util::escape($one["author"]) .']]></author>'."\n";
			$out .= '			<description><![CDATA['. Petolio_Service_Util::escape($one["description"]) .']]></description>'."\n";
			$out .= '			<pubDate>'. $objDate->format(DateTime::RSS) .'</pubDate>'."\n";
			$out .= '		</item>'."\n";
		}

		// finish the job
		$out .= '	</channel>'."\n";
		$out .= '</rss>';

		// output the rss
		die($out);
	}
}
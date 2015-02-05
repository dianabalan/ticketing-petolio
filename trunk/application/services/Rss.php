<?php

class Petolio_Service_Rss {
	/**
	 * Parse URL to check if contents are XML
	 * @param $url - The url to check
	 *
	 * @return false or XML object
	 */
	public static function parse($url = false) {
		// get contents
	    $content = @file_get_contents($url);

	    try {
			$rss = new SimpleXmlElement($content);
		} catch(Exception $e){
			Zend_Registry::get('Zend_Log')->debug("Couldn't parse xml from url ".$url.": " . $e->getTraceAsString());
			return false;
		}

		// return xml object
		return $rss;
	}

	/**
	 * Sync a source
	 * @param $id - The source id
	 *
	 * @return bool
	 */
	public static function sync($id = 0) {
		// database objects
		$db = new stdClass();

		// load models
		$db->news = new Petolio_Model_PoNews();
		$db->cache = new Petolio_Model_PoNewsCache();

    	// get news item
    	$news = $db->news->find($id);
    	if(!$news->getId())
    		return false;

		// parse xml
    	$xml = self::parse($news->getUrl());
		if(!$xml)
			return false;

		// get root
		$root = $xml->channel;

		// insert or update cache
		foreach($root->item as $item) {
			// no guid? skip
			if(!$item->guid) {
				if ( !$item->link ) {
					continue;
				} else {
					$item->guid = $item->link;
				}
			}
				
			// check db
			$record = reset($db->cache->fetchList("news_id = {$news->getId()} AND guid = '".Petolio_Service_Util::escape($item->guid)."'"));
			
			// insert
			if(!$record) {
				$new = clone $db->cache;
				$new->setOptions(array(
					'news_id' => $news->getId(),
					'title' => $item->title,
					'link' => $item->link,
					'description' => $item->description,
					'pubDate' => $item->pubDate ? strtotime($item->pubDate) : time(),
					'category' => $item->category,
					'author' => $item->author,
					'guid' => $item->guid
				))->save(true, true);

			// update
			} else {
				$record->setOptions(array(
					'title' => Petolio_Service_Util::escape($item->title),
					'link' => Petolio_Service_Util::escape($item->link),
					'description' => Petolio_Service_Util::escape($item->description),
					'category' => Petolio_Service_Util::escape($item->category),
					'author' => Petolio_Service_Util::escape($item->author)
				))->save(true, false); // on update only the changed values must be escaped, otherwise the guid will be escaped twice
			}
		}

		// update sync date
		$news->setDateCached(time())->save();

		// return true
		return true;
	}
}
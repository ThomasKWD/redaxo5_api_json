<?php

/** Provides Redaxo 4.x specific init code.
*	JSON api logic see base class.
*/
class kwd_jsonapi_rex5 extends kwd_jsonapi {

	protected function getRootCategories($ignore_offlines = false, $clang = 1) {
		return rex_category::getRootCategories($ignore_offlines,$clang);
	}

	protected function getRootArticles($ignore_offlines = false, $clang = 1) {
		return rex_article::getRootArticles($ignore_offlines,$clang);
	}

	protected function getCategoryById($id, $clang = 1) {
		return rex_category::get($id, $clang);
	}

	protected function getArticleById($id, $clang = 1) {
		return rex_article::get($id,$clang);
	}

	protected function getArticleContent($article_id,$clang = 1,$ctype = 1) {
		return (new rex_article_content($article_id,$clang))->getArticle($ctype);  // hard coded ctype 1
	}

	protected function getSlicesForCtype($article_id,$clang = 1, $ctype = 1, $revision = self::ALL) {

		$sql = rex_sql::factory();
		$sql->setQuery(
			'SELECT * FROM rex_article_slice WHERE `article_id`='.$article_id
			.($revision === self::ALL ? '' : ' AND `revision`='.intval($revision))
			.' AND `clang_id`='.$clang
			.' AND `ctype_id`='.$ctype
			.' ORDER BY `priority` ASC'
		);

		return $sql->getArray();
	}

	/** returns url with corrected start/ending
	*	- It is not absolutely certain that rex::getServer() always includes the protool ('http:')
	*   - although usually it does
	*	- as well as the trailing slash which we need.
	*/
	function cleanUrl($url) {
		$url = preg_replace('/^.*:\/\//Ui','',$url); // removes any protocol
		$url = 	trim($url," /\t\r\n\0\x0B") .'/'; // remove multiple slashes, but adds 1 if none
		return $url;
	}

	// ??? how works with unset $serverQueryString
	function __construct($serverQueryString = '') {
		parent::__construct(
			rex_request_method(), // must pass lower case string
			rex_server(self::SERVER_REQUEST_SCHEME,'string','http'),
			$this->cleanUrl(rex::getServer()),
			rex_server($serverQueryString ? $serverQueryString  : self::SERVER_QUERY_STRING)
		);
	}
}

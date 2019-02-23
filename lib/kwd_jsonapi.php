<?php

abstract class kwd_jsonapi {

	// ! const can not have acces modifier in PHP < 7.1
	const APIMARKER = 'api'; // ??? must be config-able
	const SERVER_QUERY_STRING = 'QUERY_STRING';
	const SERVER_REQUEST_SCHEME = 'REQUEST_SCHEME';
	// const SERVER_REQUEST_METHOD = 'REQUEST_METHOD';

	const META = 'meta';
	const ERRORS = 'error'; // ! currently singular word
	const HELP = 'help';
	const CATEGORIES = 'categories';
	const ARTICLES = 'articles';

	const INCLUDES = 'includes';
	const CONTENTS = 'contents';
	const METAINFOS = 'metainfos';
	const CTYPE = 'ctype';
	const SLICES = 'slices';
	const OFFLINES = 'offlines';

	const STRUCTURE = 'structure';

	const CATEGORY_ID = 'category_id';
	const ARTICLE_ID = 'article_id';
	const CLANG = 'clang';

	const DEBUG = 'debug';

	protected $clangBase = 1;
	protected $requestMethod = '';
	protected $baseUrl = '';
	protected $apiName = 'kwdapi'; // ??? must be editable by init or by setApiName
	protected $queryRaw = '';
	protected $queryStringHierarchical = ''; // expected to be '' when current request was not hierarchical
	protected $queryString = ''; // maybe already make data structure and don't store url
	protected $queryData = array();

	protected $headers = array(); // indexed array

	protected $debugMode = false;
	protected $debugOutput = array();

	abstract protected function getRootCategories($ignore_offlines = false,$clang = 0);
	abstract protected function getCategoryById($id, $clang = 0);
	abstract protected function getRootArticles($ignore_offlines = false,$clang = 0);
	abstract protected function getArticleById($id,$clang = 0);
	abstract protected function getArticleContent($article_id,$clang = 0,$ctype = 1);

	function __construct($requestMethod = 'get', $requestScheme = 'http', $serverPath = '/', $queryString = '', $newClangBase = 1) {
		$this->clangBase = $newClangBase;
		$this->init($requestMethod, $requestScheme, $serverPath, $queryString);
	}

	protected function debug($fieldName, $value) {
		$this->debugOutput[$fieldName] = $value;
	}

	function setDebugMode($mode = true) {
		$this->debugMode = $mode ? true : false;
	}

	/** adds header to list
	*	- will be written as http header (see function send())
	*	! always replace (does not support replace=false for header(),
	*		??? just pass and save flag if needed)
	*	@param string $headerString must contain valid HTTP header directive
	*/
	public function addHeader($headerString) {
		array_push($this->headers,$headerString);
	}

	/** Inits instance var $baseUrl
	* sub classes should call super->init()
	* - helper function, does NOT modify state of object
	*/
	protected function buildBaseUrl($requestScheme,$serverPath) {
		// rex_server is assumed existent in redaxo 4 and 5
		// ! php passes protocol without the ://
		return strtolower(trim($requestScheme)) .'://'.strtolower(trim($serverPath," /\t\r\n\0\x0B")); // remove multiple slashes as well
	}

	/** check valid requestMethod
	* - current code seems useless but considered prepared for more methods allowed later
	* - helper function, does NOT modify state of object
	*/
	protected function buildRequestMethod($requestMethod) {

		$requestMethod = strtolower($requestMethod);

		// add more allowed methos here, maybe use reg exp
		if ($requestMethod !== 'get') $requestMethod = 'get';

		return $requestMethod;
	}

	/** makes a check whether 'articles' found in entry
	*   ! modifies data array
	*/
	protected function checkArticlesRequested($entry,&$data) {
		if ($entry === self::ARTICLES) {
			$data[self::INCLUDES][self::ARTICLES] = 1;
			return true;
		}
		return false;
	}

	/** reset query string
	*	- makes data from $queryString
	*   - auto detects structured (hierarchical) OR parameter string
	*	! modifies object property
	*
	*  ??? u should always set a default value for a parameter like '0' or '1', easier to check later
	*
	*  @param queryString string to be converted
	*   @return string newly set string
	*/
	public function setApiQueryString($q) {

		$data = array();
		$q = strtolower($q);

		$isParametric = false;


		// string must not contain '/' when parameters
		// string must not contain '&' when structured
		if (strstr($q,'&') === false && strpos($q,self::APIMARKER.'=') !== 0) {
			// ??? FIRST make parametrical from hierarchical, THEN it is easier to perform checks over parameters built

			// sometimes a parameter query goes here when only 1 entry like "api=kwdapi"
			if ($q === self::APIMARKER .'='. $this->apiName) $isParametric = true;

			$check = $this->apiName . '=';
			if (substr($q,0,strlen($check)) === $check) {
				$this->queryRaw = $q;
				$q = $this->apiName . '/'.substr($q,strlen($check));
			}

			$this->queryStringHierarchical = trim($q," /\t\r\n\0\x0B"); // ??? code can be removed when buildResponse works with queryData array

			// ! self::APIMARKER is now the key of the api field element (without '=')

			// - multiple slashes are now eleminiated by ignoring empty entries
			$r = $this->splitTrimmed($q);

			// ! working from start AND from end

			// first element now must be api=kwdapi (api=<apiName>)
			if(count($r)) $data['api'] = array_shift($r); // first will be 'api='

			// !!! error field will be discarded if no 'api' name is set in $data
			// ??? check if really error because maybe its just not request for me!!!
			else ($data['error'] = 'syntax');

			// finds contents at end
			if (count($r) > 3 && $r[count($r) - 2] === self::CONTENTS && is_numeric($r[count($r) - 1])) {
				// $data[self::INCLUDES][self::CONTENTS] = $r[count($r)-1];
				$data[self::INCLUDES][self::CONTENTS] = 1;
				$data[self::INCLUDES][self::CTYPE] = $r[count($r)-1];
				array_pop($r);
				array_pop($r);
			}
			else if (count($r)){
				$elem = $r[count($r)-1];
				//  ! no loop; this prevents injecting user input in array directly
				if (strstr($elem,self::METAINFOS)) $data[self::INCLUDES][self::METAINFOS] = 1;
				if (strstr($elem,self::CONTENTS)) $data[self::INCLUDES][self::CONTENTS] = 1;
				if (strstr($elem,self::SLICES)) $data[self::INCLUDES][self::SLICES] = 'all';
				array_pop($r);
			}

			if (count($r)) {
				$data[self::CLANG] = $this->clangBase; // ! id always starts at 1 in Redaxo 5.x

				if ($r[0] === self::CATEGORIES) {
					if (isset($r[1])) {
						if (is_numeric($r[1])) {
							$data[self::CATEGORY_ID] = intval($r[1]);
							if (isset($r[2])) {
								if (is_numeric($r[2])) {
									$data[self::CLANG] = intval($r[2]);
									// ??? must check for 'articles' in $r[3]
									$data['info'] = 'clang set';
									if (isset($r[3])) $this->checkArticlesRequested($r[3],$data);
								}
								else $this->checkArticlesRequested($r[2],$data);
							}
						}
						else {
							$this->checkArticlesRequested($r[1],$data);
							$data[self::CATEGORY_ID] = 0;
						}
					}
					else $data[self::CATEGORY_ID] = 0;
				}

				if ($r[0] === self::ARTICLES) {
					// ??? how to combine code with above block
					if (isset($r[1])) {
						if (is_numeric($r[1])) {
							$data[self::ARTICLE_ID] = intval($r[1]);
							if (isset($r[2]) && is_numeric($r[2])) {
								$data[self::CLANG] = intval($r[2]);
							}
						}
					}
					else {
						$data[self::ARTICLE_ID] = 0; // should lead to handle all articles OR error
					}
				}

				if ($r[0] === self::HELP) {
					$data[self::HELP] = 1;
				}
			}
			// empty (==entry point)
			else {
				// TODO: must be adjustable by var otherwise not running in redaxo 4
				$data[self::CLANG] = $this->clangBase; // ! id always starts at 1 in Redaxo 5.x
				$data[self::CATEGORY_ID] = 0;
			}

			// also provide parameter string although not really needed
			// - can better split up functions + remain consistent when looking at config with getConfiguration()
			// ! uses ampersand '&amp;' by default
			$this->queryString = http_build_query($data,'','&');
		}
		else {
			$isParametric = true;

			$this->queryRaw = $q;
			// important for later format of links
			$this->queryStringHierarchical = '';

			// ??? shorten up foll. lines!
			$q = str_replace('&amp;','&',$q);

			$this->queryString = $q;
			parse_str($q,$data);
			if ($data === false) {
				$data = array();
			}
			else {

				// add root categories to entry point
				if(count($data) === 1) {
					$data[self::CATEGORY_ID] = 0;
				}
				else {
					// we want to split up includes and move to parent query array
					if (isset($data[self::INCLUDES])) {
						$temp = explode(',',$data[self::INCLUDES]);
						foreach($temp as $i) {
							$data[$i] = 1; // make assoc sub array
						}
					}

					// TODO: must add articles to includes auto when requested article_id

					// ! fields directly coming from param (not includes) overwrite includes

					// - contents=0 removed
					// - ctype created from contents
					if (isset($data[self::CONTENTS]) && intval($data[self::CONTENTS])) {
						$data[self::CTYPE] = $data[self::CONTENTS];
					}
					else unset($data[self::CONTENTS]);

					if (isset($data[self::CTYPE])) {
						$data[self::CONTENTS] = 1;
						$data[self::CTYPE] = intval($data[self::CTYPE]);
					}

				}

				// always a clang
				if (!isset($data[self::CLANG]))	$data[self::CLANG] = $this->clangBase;
			}
		}

		// discard array when api entry not found as key
		// ??? or insert error ...
		if (array_key_exists(self::APIMARKER, $data) && $data[self::APIMARKER] === $this->apiName) {
			$this->queryData = $data;
		}
		else {
			$this->queryData = array();
		}
	}

	protected function init ($requestMethod = 'get', $requestScheme = 'http', $serverPath = '/', $queryString = '') {
		$this->requestMethod = $this->buildRequestMethod($requestMethod);
		$this->baseUrl = $this->buildBaseUrl($requestScheme,$serverPath);
		$this->setApiQueryString($queryString);
	}

	/** reads current configuration.
	*	Returns all relevant object properties as associated array
	* - getter; does NOT modify state of object
	*/
	public function getConfiguration() {

		return array(
			'requestMethod' => $this->requestMethod,
			'baseUrl' => $this->baseUrl,
			'apiName' => $this->apiName, // ??? edit to be editable
			'queryString' => $this->queryString,
			'queryStringHierarchical' => $this->queryStringHierarchical,
			'queryData' => $this->queryData
		);
	}

	public function getHeaders() {
		// ! PHP always returns copy of array
		return $this->headers;
	}

	public function setHeaders($headersArray) {
		// ! PHP always assigns copy of array
		return $this->headers = $headersArray;
	}

	// ??? process metainfos, ctypes
	protected function getCategoryFields($cat, $clang_id = 1, $includes = array()) {
		$id = $cat->getId();
		// $entry['id'] = $id;
		// $entry['name'] = $cat->getName();
		// $entry['createdate'] = $cat->getCreateDate();
		// $entry['updatedate'] = $cat->getUpDateDate();

		$entry = $this->getMetaInfos($cat,null);

		$a = array_merge(array(
			self::CATEGORY_ID => $id,
			self::CLANG => $clang_id
		),$includes);


		$entry['link'] = $this->apiLink($a);

		// return array
		return $entry;
	}

	/** returns true if a string starts with certain string
	*	@return boolean true: found, else false
	*/
	protected function startsWith($haystack, $needle)
	{
	     $length = strlen($needle);
	     return (substr($haystack, 0, $length) === $needle);
	}

	/** ! returns all fields available, not only meta info
	*	- fortunately this method is equal tin redaxo versions 4|5
	*	??? should filter depending on being article|category
	*	??? redaxo access should go into avstract wrapper like 'getCategoryById()'
	*	@return array associateive array of found meta data, first element contains length of array - 1
	*/
	protected function getMetaInfos($cat,$art) {

		$res = array();

		if ($cat) $artOrCat = $cat;
		else if ($art) $artOrCat = $art;

		if ($artOrCat) {
			if (count($artOrCat->getClassVars())) {
				foreach($artOrCat->getClassVars() as $m) {
					$flagMetaInfo = false;
					$field = $m;
					if ($cat) {
						// ! order of checks matters
						if ($m == 'name') $field = '';
						if ($this->startsWith($m,'art_')) $field = '';
						// ??? problem when requesting an article via category
						if ($m == 'catname') $field = 'name';
						else if ($this->startsWith($m,'cat_')) $flagMetaInfo = true;
						else if ($this->startsWith($m,'cat')) $field = substr($m,3);
					}
					if ($art) {
						if ($m == 'catprior') $field = '';
						else if ($this->startsWith($m,'cat_')) $field = '';
						else if ($this->startsWith($m,'art_')) $flagMetaInfo = true;
					}
					// remove this line when offlines possible:
					// if ($m === 'status') $field = '';

					if ($field) {
						$temp = $artOrCat->getValue($m);
						if (is_numeric($temp)) $temp = floatval($temp);
						// if ($temp == intval($temp)) $temp = intval($temp);
						// else if (is_float($temp)) $temp = floatval($temp);
						// later, if offline possible:
						// if ($m === 'status') $temp = $temp === '1' ? $temp = 'online' : 'offline';
						if ($flagMetaInfo) $res['metainfos'][$field] = $temp;
						else $res[$field] = $temp;
					}

				}
			}
			// ??? consider write error:
			else $res['info'] = '[no metadata]';
		}
		else {
			$res = [];
		}

		return $res;
	}

	// get data from OOarticle object
	protected function addArticle($art, $content, $ctype_id) {
		// order matters:
		// $res['id'] = $art->getId();
		// $res['name'] = $art->getName();
		// $res['is_start_article'] = $art->isStartArticle() ? true : false;
		// $res['createdate'] = $art->getCreateDate();
		// $res['updatedate'] = $art->getUpDateDate();
		// $this->addArticleValue($res,$art,'onlinefrom','art_online_from'); // ! changes $res; checks validity inside,
		// $this->addArticleValue($res,$art,'onlineto','art_online_to'); // ! changes $res; checks validity inside,

		$res = $this->getMetaInfos(null,$art);

		if ($content) $res['body'] = $this->getArticleContent($art->getId(), $art->getClang(), $ctype_id);

		$res['link'] = $this->apiLink(array(
			self::ARTICLE_ID => $art->getId(),
			self::CLANG => $art->getClang(),
			self::CONTENTS => $content,
			self::CTYPE => $ctype_id
		));

		return $res;
	}

	protected function addAllArticlesOfCategory($cat, $content = false, $ctype_id) {
		$ret = [];

		foreach($cat->getArticles(true) as $art) {
			$ret[] = $this->addArticle($art,$content, $ctype_id);
		}

		return $ret;
	}

	// ! if parametrical $queryString must not contain leading '&'
	// ! $query should not contain 'api => kwdapi'
	protected function apiLink($query = array()) {
		if ($this->queryStringHierarchical) {
			// ??? how combine logic with '$this->setApiQueryString'
			$ret = $this->baseUrl.'/'.$this->apiName;
			foreach($query as $k => $v) {
				$ret .= ($k === self::HELP) ? '/'.self::HELP : '';
				$ret .= ($k === self::CATEGORY_ID) ? '/'.self::CATEGORIES.'/'.$v : '';
				$ret .= ($k === self::ARTICLE_ID) ? '/'.self::ARTICLES.'/'.$v : '';
				// if ($k === self::INCLUDES) {
				// 	$ret .= '/'.$k.'='.implode(',',$v);
				// }
			}
		}
		// ??? use predefined arg_separator.output of php
		else {
			$ret =
			$this->baseUrl
			. '/index.php?api='.$this->apiName
			.(count($query) ?  ini_get('arg_separator.output').http_build_query($query) : '');
		}

		return $ret;
	}

	//  --- API DATA GENERATION

	// always returns array,
	// - filter without callback does what i need: remove empty elements
	// - array_values needed to "re-index" from 0
	// - trim with slashes: (not neede here) trim($api," /\t\r\n\0\x0B"); // remove multiple slashes as well
	protected function splitTrimmed($string) {
		return array_values(array_filter(explode('/',trim($string))));
	}

	protected function generateSyntaxError() {

		$response = [];

		// articles/categories not found
		$this->addHeader('HTTP/1.1 400 Bad Request');
		$response['request']= $this->queryRaw;
		$response['error']['message'] = 'Syntax error or unknown request component';
		$response['error'][self::HELP]['info'] = 'See links for entry point or help.';
		$response['error'][self::HELP]['links'][] = $this->apiLink();
		$response['error'][self::HELP]['links'][] = $this->apiLink(array(self::HELP => 1));

		$this->debug('query',$this->getConfiguration());

		return $response;
	}

	protected function generateResourceNotFound() {
		$response;

		$this->addHeader("HTTP/1.1 404 Not Found");

		$response['request'] = $this->queryRaw; // ??? this line exists 3 times, how to make "DRY"?
		$response['error']['message'] = 'Resource for this request not found. Probably you passed an id that does not exist.';
		$response['error'][self::HELP]['info'] = 'Start with /api or /api/'.self::CATEGORIES;
		$response['error'][self::HELP]['links'][] = $this->apiLink();
		$response['error'][self::HELP]['links'][] = $this->apiLink(array(self::CATEGORY_ID => 0));
		$response['error'][self::HELP]['links'][] = $this->apiLink(array(self::HELP => 1));
		return $response;
	}

	// ??? check if cool to have request passed
	public function buildResponse($newQueryString = '') {

		if ($newQueryString) {
			$this->setApiQueryString($newQueryString);
		}

		$response = array();
		$query = $this->queryData;

		// the substr AND strlen construct is assumed to be more efficient than reg exp
		// - just avoid reg exp when possible
		if (count($query)) {

			// ??? make static helper: checks isset AND value of field of array // not working because then already reference to it needed
			if (isset($query[self::DEBUG]) && $query[self::DEBUG]) {
				$this->setDebugMode();
			}

			// $api = $this->queryStringHierarchical; // ! as long as parameters not working

			// !only allow GET
			if ($this->requestMethod !== 'get') {
				$this->addHeader('HTTP/1.1 403 Forbidden');
				$response['error']['message'] = 'You can only GET data.';
			}
			else {

				$this->addHeader('Access-Control-Allow-Origin: *');

				// used to make api links clickable
				$host = $this->baseUrl; // ???: check id SERVER var correct in all cases!

				// if (strstr($api,'//')) {
				// 	$response = generateSyntaxError();
				// }
				// else {

				// request string as array:
				// $request = $this->splitTrimmed($api);

				// first remove entries which may come from leading/trailing slashes "/":

				$response['request'] = $this->queryRaw; // sensible???
				$this->debug('query',$this->getConfiguration());

				if (isset($query[self::CLANG])) $clang_id = $query[self::CLANG]; // should always be preset
				else $clang_id = $this->clangBase;

				$showArticlesOfCategory = false;
				$content = false;
				$selectedCtype = 1;

				// CONTENTS, and CTYPE preprocessed by $this->setApiQueryString
				if (isset($query[self::CONTENTS])) {
					$content = true;
				}
				if (isset($query[self::CTYPE]))	$selectedCtype = intval($query[self::CTYPE]);

				// includes are: contents, ctype, metainfos, slices, articles
				$includesForLink = array();
				if (isset($query[self::CONTENTS])) $includesForLink[self::CONTENTS] = $query[self::CONTENTS];
				if (isset($query[self::CTYPE])) $includesForLink[self::CTYPE] = $query[self::CTYPE];
				if (isset($query[self::ARTICLES])) $includesForLink[self::ARTICLES] = $query[self::ARTICLES];
				if (isset($query[self::SLICES])) $includesForLink[self::SLICES] = $query[self::SLICES];

				// help section
				// -----------------------

				if (isset($query[self::HELP])) {
					$response['info'] = 'You will get hierarchical "categories". A selected category will contain a list of its immediate sub categories and data of its related "articles" when requested. Please note: only categories or articles defined as "online" are shown. See the "examples" section of this response!';
					$response['examples'] = array(
						array(
							'info' => 'Entry point, currently also provides "root categories"', 'link' => $this->apiLink()
						),
						array(
							'info' => 'Root "categories"',
							'link' => $this->apiLink(array(self::CATEGORY_ID => 0))
						),
						// ! currently disabled
						// array('info' => 'Alternative entry point because no id specified', 'link' => $this->apiLink(self::ARTICLES)),
						array(
							'info' => 'A category selected by its ID (always contains immediate sub categories).',
							'link' => $this->apiLink(array(self::CATEGORY_ID => 3))
						),
						array(
							'info' => 'A category selected by its ID and its language (clang).',
							'link' => $this->apiLink(array(self::CATEGORY_ID => 3, self::CLANG => 1))
						),
						array(
							'info' => 'A category with list of "articles" (also articles in sub categories).',
							'link' => $this->apiLink(array(
								self::CATEGORY_ID => 3,
								self::CLANG => 1,
								self::ARTICLES => 1
							))
						),
						array(
							'info' => 'A category with articles and those bodies (compiled "article content").',
							'link' => $this->apiLink(array(
								self::CATEGORY_ID => 3,
								self::CLANG => 1,
								self::ARTICLES => 1,
								self::CONTENTS => 1
							))
								//self::CATEGORIES.'/3/0/articles/contents')
						),
						array(
							'info' => 'A single article by id with its rendered contents',
							// 'link' => $this->apiLink(self::ARTICLES.'/3/0/articles/contents')
							'link' => $this->apiLink(array(
								self::ARTICLE_ID => 2,
								self::CLANG => 1,
								self::CONTENTS => 1
							))
						)
					);
					$response['external']['info'] = 'Understand the basic concepts of "categories" and "articles":';
					$response['external']['links'][] = 'https://redaxo.org';
					$response['external']['links'][] = 'https://redaxo.org/doku/master/system';

				}

				// categories
				// -----------------------

				else if (isset($query[self::CATEGORY_ID])) {
					// check articles from cat like categories/<id>/articles

					// reject categories + contents without article
					if (
						!isset($query[self::ARTICLES])
						&& isset($query[self::CONTENTS])
					) {
						$content = false;
						$response = $this->generateSyntaxError();
						$response['error']['message'] = 'Semantic error. You cannot request "contents" without requesting "articles".';
					}
					else {

						// include articles of all returned categories
						if (isset($query[self::ARTICLES])) $showArticlesOfCategory = true;

						$this->debug(self::CONTENTS,$content);
						// set start cat id
						$startCat = $query[self::CATEGORY_ID];
						$kids = null;


						// ! we assume rqeuesting cat id == 0 means rootCategories!!
						$cat = $this->getCategoryById($startCat,$clang_id);

						if ($cat) {
							$kids = $cat->getChildren(true);

							// ??? yet another sub func: for cat data

							$response = array_merge($response,$this->getCategoryFields($cat,$clang_id,$includesForLink));

							// $response['id'] = $cat->getId();
							// $response['name'] = $cat->getName();
							// $response['createdate'] = $cat->getCreateDate();
							// $response['updatedate'] = $cat->getUpDateDate();
						}
						else if (!$startCat) {
							$kids = $this->getRootCategories(true,$clang_id);

							$response['info'] = 'You can use the ids or links in the list of root "categories".';
							$response[self::HELP]['info'] = 'Check out the help section too!';
							$response[self::HELP]['links'][] = $this->apiLink(array(self::HELP => 1));
						}
						else {
							$response = $this->generateResourceNotFound();
							$this->debug('info','getCategoryById '.$startCat. ' failed');
						}

						if ($kids && count($kids)) {
							foreach($kids as $k) {
								$catResponse = $this->getCategoryFields($k,$clang_id,$includesForLink);

								if ($showArticlesOfCategory) {
									$catResponse[self::ARTICLES] = $this->addAllArticlesOfCategory($k,$content,$selectedCtype);
								}
								$response[self::CATEGORIES][] = $catResponse;
							}
						}
						else if (!$startCat){
							// IDEA: check if better to have an empty array "categories[]" to indicate there usually are some
							$response['warning'] = 'Currently no root "categories" online.';
						}


						// my own content
						// ??? sub function
						// ??? must be loop for all in cat!!!!!!
						if ($showArticlesOfCategory) {
							if ($cat)  {
								$response[self::ARTICLES] = $this->addAllArticlesOfCategory($cat,$content,$selectedCtype);
							}
							else if (!$startCat) {
								$artRes = [];
								$arts = $this->getRootArticles(true,$clang_id);
								foreach($arts as $art) {
									$artRes[] = $this->addArticle($art,$content,$selectedCtype); // TODO: wrong usage of $content
								}
								// - could insert if to prevent empty array
								// ! can be empty array because *all* root articles could be offline (not depending on cat)
								$response[self::ARTICLES] = $artRes;
							}
						}
					}
				}

				// article without category to refer to
				// ------------------------------------

				else if (isset($query[self::ARTICLE_ID])) {

					// certain article
					if (intval($query[self::ARTICLE_ID])) {

						$art = $this->getArticleById(intval($query[self::ARTICLE_ID]),$clang_id);
						// $art == null when id not found
						if ($art) {
							$response = array_merge($response, $this->addArticle($art,$content,$selectedCtype));
						}
						else {
							// not found
							$response = $this->generateResourceNotFound();
						}
					}
					// ! commented out because clang always allowed
					// else if (intval($query[self::CLANG])) {
					// 	// ! bad request
					// 	$response = $this->generateSyntaxError();
					// }
					else {
						// planned:
						// $response = $this->generateForbiddenError($api);
						$this->addHeader('HTTP/1.1 403 Forbidden');
						$response[self::ERRORS]['message'] = 'Currently you can not request all articles without specifying an id';
						$response[self::HELP]['info'] = 'You can travers categories starting by the entry point to find articles.';
						$response[self::HELP]['links'][] = $this->apiLink();
					}
				}

				else {
					$response = $this->generateSyntaxError();
					// // articles/categories not found
					// $this->addHeader('HTTP/1.1 400 Bad Request');
					// $response['error']['message'] = 'Syntax error or unknown request component';
					// $response['error'][self::HELP]['info'] = 'See links for entry point or help.';
					// $response['error'][self::HELP]['links'][] = $this->apiLink('');
					// $response['error'][self::HELP]['links'][] = $this->apiLink(self::HELP);
				}
			}
		}

		$this->addHeader('Content-Type: application/json; charset=UTF-8',false);

		if ($this->debugMode) {
			$response[self::DEBUG] = $this->debugOutput;
		}

		// ! we don't exit if response could not been build
		// ! usually this allows to show normal start page of Redaxo project.
		// ??? include headers in return value?
		if (count($response)) return json_encode($response);
		return '';
	}

	public function sendHeaders() {
		foreach($this->getHeaders() as $h) {
			// ! only supports replace = true
			header($h);
		}
	}

	public function send($responseString) {
		if ($responseString) {
			// send headers!!
			ob_end_clean();
			$this->sendHeaders();
			echo $responseString;
			return true;
		}

		return false;
	}
}

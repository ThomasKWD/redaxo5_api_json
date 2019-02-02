<?php

class rex_api_readcategories extends rex_api_function {

	protected $published = true;

	public function execute()
    {
        // echo '<p>mach mal was sinnvolles</p>';
		$response = '';
		$kwdApi = new kwd_jsonapi_rex5(); // ??? add parameter from config as server string *index*
		$kwdApi->setApiQueryString('/api/');
		$response = $kwdApi->buildResponse(); // returns immediately when no valid API request found
		if ($response) {
			$kwdApi->sendHeaders(); // ! headers not send in redaxo 5
			return $response;
		}
		else echo 'bild ok';

		$res['body'] =  rex_article_content::getArticleById(1);  // hard coded ctype 1
		echo json_encode($res);

		exit();
    }

	function __construct() {
		parent::__construct();
	}
}

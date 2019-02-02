<?php

use PHPUnit\Framework\TestCase;

// require_once('../classes/kwd_jsonapi.php');
// require_once('../classes/kwd_jsonapi_rex4.php');

class KwdJsonApiRex4TestCase extends TestCase {

	const apiPath = 'http://localhost/tk/kwd_website/api/';

	public function getJson($query) {
		$curl = curl_init(self::apiPath.$query);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		return json_decode(curl_exec($curl));
	}

	// /api/
    public function testConnectionToRealRedaxoInstallation() {
		$json = $this->getJson(''); // you omit the root ('.../api/')
		$this->assertSame('api/',$json->request,'must contain request string for verification'); // ??? change to api json concept
		$this->assertSame(self::apiPath.'help',$json->help->links[0],'must provide link to help section'); // ??? change to api json concept

		// id, clang, createdate etc. should be there
		$firstCat = $json->categories[0];
		$this->assertSame(1,$firstCat->id,'correct id');
		$this->assertSame('Start',$firstCat->name,'correct name');
		$this->assertSame(0,$firstCat->clang,'correct content language');
		$this->assertGreaterThanOrEqual(1280159912,$firstCat->createdate,'create date must at least be 1280159912 (Mon Jul 26 2010 17:58:32 GMT+0200)');

    }

	//
	// public function testInitAfterConstruct() {
	//
	// }

	// - test: init with no api string then run getReponse
}

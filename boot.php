<?php

/** @var rex_addon $this */

// Diese Datei ist keine Pflichdatei mehr.

// Daten wie Autor, Version, Subpages etc. sollten wenn möglich in der package.yml notiert werden.
// Sie können aber auch weiterhin hier gesetzt werden:
// $this->setProperty('author', 'Friends Of REDAXO');

// Die Datei sollte keine veränderbare Konfigurationen mehr enthalten, um die Updatefähigkeit zu erhalten.
// Stattdessen sollte dafür die rex_config verwendet werden (siehe install.php)

// Klassen und lang-Dateien müssen hier nicht mehr eingebunden werden, sie werden nun automatisch gefunden.

// Addonrechte (permissions) registieren
if (rex::isBackend() && is_object(rex::getUser())) {
    rex_perm::register('redaxo5_api_json[]');
    // rex_perm::register('redaxo5_api_json[config]');
}

// Assets werden bei der Installation des Addons in den assets-Ordner kopiert und stehen damit
// öffentlich zur Verfügung. Sie müssen dann allerdings noch eingebunden werden:

// Assets im Backend einbinden
// if (rex::isBackend() && rex::getUser()) {
//
//     // Die style.css überall im Backend einbinden
//     // Es wird eine Versionsangabe angehängt, damit nach einem neuen Release des Addons die Datei nicht
//     // aus dem Browsercache verwendet, sondern frisch geladen wird
//     rex_view::addCssFile($this->getAssetsUrl('css/style.css?v=' . $this->getVersion()));
//
//     // Die script.js nur auf der Unterseite »config« des Addons einbinden
//     if (rex_be_controller::getCurrentPagePart(2) == 'config') {
//         rex_view::addJsFile($this->getAssetsUrl('js/script.js?v=' . $this->getVersion()));
//     }
// }


// - uses OUTPUT_FILTER because user will expect this (esp. for project wide replacemets)
function kwd_startJsonApi_output($params) {
	// print_r($params);
	// exit();
	$response = '';
	$kwdApi = new kwd_jsonapi_rex5(); // ??? add parameter from config as server string *index*
	$response = $kwdApi->buildResponse(); // returns immediately when no valid API request found
	if ($response) {
		$kwdApi->sendHeaders(); // ! headers not send in redaxo 5
		echo $response;
		exit();
		// return $response;
	}
	// return $params['subject'];
}

// - could be switched "on" by configuration
function kwd_startJsonApi_fast() {
	$kwdApi = new kwd_jsonapi_rex5(); // ??? add parameter from config as server string *index*

	// returns false, if repsonse empty, true when something in it
	if ($kwdApi->send($kwdApi->buildResponse())) // ! send contains ob_end and echo
		exit();
}

function kwd_test() {
	$r = new rex_api_readcategories();
	$r->execute();
}

if (!rex::isBackend()) {

	rex_extension::register('OUTPUT_FILTER', 'kwd_startJsonApi_output', rex_extension::LATE);
	// rex_extension::register('PACKAGES_INCLUDED', 'kwd_test', rex_extension::LATE);


	// faster but can not use OUTPUT_FILTER:
	// ! NOT WORKING when /contents requested
	// rex_extension::register('PACKAGES_INCLUDED', 'kwd_startJsonApi_fast');
}

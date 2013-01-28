<?php
use Mouf\Reflection\MoufReflectionProxy;

require_once __DIR__.'/../mouf/Mouf.php';


$client = new Github\Client();
$repositories = $client->api('user')->repositories('thecodingmachine');

var_dump($repositories);
foreach ($repositories as $repository) {
	var_dump($repository['name']);
	//var_dump($repository['branches_url']);
	/*$repo = $client->api('repo')->show('thecodingmachine', $repository['name']);
	var_dump($repo);*/
	$branchesUrl = substr($repository['branches_url'], 0, strpos($repository['branches_url'], '{'));
	
	$branchesJson = performRequest($branchesUrl);
	$branchesArr = json_decode($branchesJson, true);
	foreach ($branchesArr as $branch) {
		var_dump("Branch: ".$branch['name']);
	}
}


function performRequest($url, $post = array()) {
	// preparation de l'envoi
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if($post) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	} else {
		curl_setopt($ch, CURLOPT_POST, false);
	}
		
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //Fixes the HTTP/1.1 417 Expectation Failed Bug

	$response = curl_exec($ch );

	if( curl_error($ch) ) {
		throw new \Exception("An error occured: ".curl_error($ch));
	}
	curl_close( $ch );

	return $response;
}

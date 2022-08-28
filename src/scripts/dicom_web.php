<?php 
/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Proxy sending OHIF Ajax's queries to Orthanc PACS
 * Pass only GET request on the /dicom-web/ API
 * Check Access grant to user using the Dicom_Web_Access Class
 */

use Proxy\Proxy;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Proxy\Filter\RemoveEncodingFilter;
use Laminas\Diactoros\Request;

Session::checkSession(false);
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$permissionDicomWebObject=new Dicom_Web_Access($_SERVER['REQUEST_URI'], $userObject, $_SESSION['role'], $linkpdo);

try {
	$permissionDicomWeb=$permissionDicomWebObject->getDecision();
}catch (Exception $e) {
	header('HTTP/1.0 403 Forbidden');
	exit();
}


if ($permissionDicomWeb) {   
	unset($_GET['page']);
    
	$calledURL=GAELO_ORTHANC_PACS_ADDRESS.':'.GAELO_ORTHANC_PACS_PORT;
    
	$finalURI=str_replace("orthanc/", "", $_SERVER['REQUEST_URI']);
    
	$request=new Request($finalURI, 'GET', 'php://temp', array('Authorization' => "Basic ".base64_encode('dicomWeb:dicomWeb')));
    
	// Create a guzzle client
	$guzzle=new GuzzleHttp\Client();
    
	// Create the proxy instance
	$proxy=new Proxy(new GuzzleAdapter($guzzle));
    
	// Add a response filter that removes the encoding headers.
	$proxy->filter(new RemoveEncodingFilter());
    
	// Forward the request and get the response.
	$response=$proxy -> forward($request) -> filter(function($request, $response, $next) {
		// Manipulate the request object.
		$url = getenv("HOST_URL");
		$port = getenv("HOST_PORT");
		$protocol = getenv("HOST_PROTOCOL");
		$request=$request->withHeader('Forwarded', 'by=localhost;for=localhost;host='.$url.':'.$port.'/orthanc'.';proto='.$protocol);

		$response=$next($request, $response);
        #$response = $response->withHeader('Cross-Origin-Embedder-Policy', 'require-corp');
		#$response = $response->withHeader('Cross-Origin-Resource-Policy', 'same-site');
		#$response = $response->withHeader('Cross-Origin-Opener-Policy', 'same-origin');
		return $response;
	}) -> to($calledURL);
    
	// Output response to the browser.
	(new Narrowspark\HttpEmitter\SapiEmitter)->emit($response);

}else {
	header('HTTP/1.0 403 Forbidden');
}

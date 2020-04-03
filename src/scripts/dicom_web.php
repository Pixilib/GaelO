<?php 
/**
 Copyright (C) 2018 KANOUN Salim
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
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Request;

Session::checkSession(false);
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$permissionDicomWebObject=new Dicom_Web_Access($_SERVER['REQUEST_URI'], $userObject, $_SESSION['role'], $linkpdo);
$permissionDicomWeb=$permissionDicomWebObject->getDecision();

if($permissionDicomWeb){   
    unset($_GET['page']);
    
    $calledURL=GAELO_ORTHANC_PACS_ADDRESS.':'.GAELO_ORTHANC_PACS_PORT;
    
    // Create a PSR7 request based on the current browser request.
    $request = ServerRequestFactory::fromGlobals();
    
    $finalURI=str_replace("orthanc/", "", $_SERVER['REQUEST_URI']);
    
    $request = new Request($finalURI, 'GET', 'php://temp', array('Authorization' => "Basic " . base64_encode('dicomWeb:dicomWeb')) );
    
    // Create a guzzle client
    $guzzle = new GuzzleHttp\Client();
    
    // Create the proxy instance
    $proxy = new Proxy(new GuzzleAdapter($guzzle));
    
    // Add a response filter that removes the encoding headers.
    $proxy->filter(new RemoveEncodingFilter());
    
    // Forward the request and get the response.
    $response = $proxy -> forward($request) -> filter(function ($request, $response, $next) {
        // Manipulate the request object.
        $serverName = $_SERVER['SERVER_NAME'];
        $port =  $_SERVER['SERVER_PORT'];
        $protocol = @$_SERVER['HTTPS'] == true ? 'https' : 'http';
        //error_log('by=localhost:8080;for=localhost:8080;host='.$serverName.':'.$port.';proto='.$protocol);
        //Set Fowarded Message to update orthanc Host server
        $request = $request->withHeader('Forwarded', 'by=localhost:8080;for=localhost:8080;host='.$serverName.':'.$port.';proto='.$protocol);

        $response = $next($request, $response);
        
		return $response;
	}) -> to($calledURL);
    
    // Output response to the browser.
    (new Narrowspark\HttpEmitter\SapiEmitter)->emit($response);

}else{
    header('HTTP/1.0 403 Forbidden');
}

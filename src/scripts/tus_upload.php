<?php 

//use Symfony\Component\HttpFoundation\Request;

//SK reste a implementer securite
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/upload/temp');

\TusPhp\Config::set($_SERVER['DOCUMENT_ROOT'].'/data/_config/tus_server.php');
$server = new \TusPhp\Tus\Server(); //Either redis, file or apcu. Leave empty for file based cache.
$server->setUploadDir(UPLOADS_DIR);
//Overring the 'file' default root path by 'tus'
//error_log(print_r($_SERVER, true));
$server->setApiPath('/tus');
//$server->getRequest()->getRequest()->setTrustedProxies(['127.0.0.1', 'REMOTE_ADDR'], Request::HEADER_X_FORWARDED_ALL);


$response = $server->serve();

$response->send();

exit(0); // Exit from current PHP process.

?>
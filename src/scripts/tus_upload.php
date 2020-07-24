<?php 
//SK reste a implementer securite
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/upload/temp');

\TusPhp\Config::set($_SERVER['DOCUMENT_ROOT'].'/data/_config/tus_server.php');
$server = new \TusPhp\Tus\Server(); //Either redis, file or apcu. Leave empty for file based cache.
$server->setUploadDir(UPLOADS_DIR);
//Overring the 'file' default root path by 'tus'
$server->setApiPath('/tus');

$response = $server->serve();

$response->send();

exit(0); // Exit from current PHP process.

?>
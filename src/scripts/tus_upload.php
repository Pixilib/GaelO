<?php 

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/upload');
error_log(UPLOADS_DIR);

\TusPhp\Config::set($_SERVER['DOCUMENT_ROOT'].'/data/_config/tus_server.php');
$server   = new \TusPhp\Tus\Server(); //Either redis, file or apcu. Leave empty for file based cache.
$server->setUploadDir(UPLOADS_DIR);
$response = $server->serve();

$response->send();

exit(0); // Exit from current PHP process.

?>
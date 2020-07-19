<?php 
//SK reste a implementer securite
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/upload/temp');

\TusPhp\Config::set($_SERVER['DOCUMENT_ROOT'].'/data/_config/tus_server.php');
$server = new \TusPhp\Tus\Server(); //Either redis, file or apcu. Leave empty for file based cache.
$server->setUploadDir(UPLOADS_DIR);
$server->setApiPath('/tus');

$server->event()->addListener('tus-server.upload.complete', function (\TusPhp\Events\TusEvent $event)  use ($server) {
    $filesDetails = $event->getFile()->details();
    $tusFile = $event->getFile();
    $uploadMetadata = $filesDetails['metadata'];
    $uploadedZipPath = $tusFile->getFilePath();

    //Defining upziping folder
    $desinationUnzipPath = $_SERVER['DOCUMENT_ROOT'].'/data/upload/temp/'.$uploadMetadata['timeStamp'].'_'.$uploadMetadata['idVisit'];
    if (!is_dir($desinationUnzipPath)) {
            mkdir($desinationUnzipPath, 0755);
    }
    //Unzip uploaded file
    $zip=new ZipArchive;
    $zip->open($uploadedZipPath);
    $zip->extractTo($desinationUnzipPath);
    $zip->close();

    //Remove unziped file from TUS
    $server->getCache()->delete($tusFile->getKey());
    unlink($uploadedZipPath);

});

$response = $server->serve();

$response->send();

exit(0); // Exit from current PHP process.

?>
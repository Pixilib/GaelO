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

use Ifsnop\Mysqldump as IMysqldump;

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if ($_SESSION['admin']) {
    
    $fileSql=tempnam(ini_get('upload_tmp_dir'), 'TMPDB_');
   
    try {
        if(DATABASE_SSL){
            $dump = new IMysqldump\Mysqldump('mysql:host='.DATABASE_HOST.';dbname='.DATABASE_NAME.'', ''.DATABASE_USERNAME.'', ''.DATABASE_PASSWORD.'', array(), Session::getSSLPDOArrayOptions() );
        }else{
            $dump = new IMysqldump\Mysqldump('mysql:host='.DATABASE_HOST.';dbname='.DATABASE_NAME.'', ''.DATABASE_USERNAME.'', ''.DATABASE_PASSWORD.'');   
        }
        
        $dump->start($fileSql);
    } catch (Exception $e) {
        echo 'mysqldump-php error: ' . $e->getMessage();
    }
    
    $date =Date('Ymd_his');
    
    $zip = new ZipArchive;
    $tempZip = tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFile($fileSql, "export_database_$date.sql");
    
    //Export the specific Php form/Object study Visit Files from Server
    $specificPhpPath = realpath($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'form');
    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($specificPhpPath),
        RecursiveIteratorIterator::LEAVES_ONLY
        );
    
    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($specificPhpPath) + 1);
            
            // Add current file to archive
            $zip->addFile($filePath, 'form/'.$relativePath);
        }
    }
    
    
    //Export the log files
    $logPhpPath = realpath($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'logs');
    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($logPhpPath),
        RecursiveIteratorIterator::LEAVES_ONLY
        );
    
    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($logPhpPath) + 1);
            
            // Add current file to archive
            $zip->addFile($filePath, 'logs/'.$relativePath);
        }
    }
    
    
    //Export the documentation files
    $documentationPath = realpath($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'documentation');
    if(is_dir($documentationPath)){
        // Create recursive directory iterator
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($documentationPath),
            RecursiveIteratorIterator::LEAVES_ONLY
            );
        
        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($documentationPath) + 1);
                
                // Add current file to archive
                $zip->addFile($filePath, 'upload/documentation/'.$relativePath);
            }
        }
    }
    
    
    //Export the full list of series in JSON
    $seriesFullJson=json_encode(Global_Data::getAllSeriesOrthancID($linkpdo), JSON_PRETTY_PRINT);
    $seriesJsonFile = tempnam(ini_get('upload_tmp_dir'), 'TMPSeriesJson_');
    $seriesJsonHandler= fopen($seriesJsonFile, 'w');
    fwrite($seriesJsonHandler, $seriesFullJson);
    fclose($seriesJsonHandler);
    $zip->addFile($seriesJsonFile, 'seriesOrthancId.json');
    
    $zip->close();
    
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="export_database_'.$date.'.zip"');
    readfile($tempZip);
    unlink($tempZip);
    unlink($fileSql);
    
}else{
    require 'includes/no_access.php';
}
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
 * Access Data from FTP Source
 */

use phpseclib\Net\SFTP;

class FTP_Reader
{

    public $ftpUsername;
    public $ftpPassword;
    public $ftpHost;
    public $ftpPort;
    public $ftpIsSftp;
    public $ftpFolder;
    public $ftpFileName;
    public $lastUpdateLimit;
    private $linkpdo;

    public function __construct($linkpdo)
    {
        $this->linkpdo=$linkpdo;
        $this->ftpFileName = null;
        $this->lastUpdateLimit = null;
        $this->ftpFolder = '/';
    }

    /**
     * 
     */
    public function setFTPCredential(String $host, String $username, String $password, int $port = 21, bool $ftpIsSftp = false)
    {
        $this->ftpHost = $host;
        $this->ftpUsername = $username;
        $this->ftpPassword = $password;
        $this->ftpPort = $port;
        $this->ftpIsSftp = $ftpIsSftp;
    }

    public function setFolder(String $folder)
    {
        $this->ftpFolder = $folder;
    }

    public function setSearchedFile(String $fileName)
    {
        $this->ftpFileName = $fileName;
    }

    /**
     * Number of minutes the last update of files should be inferior
     */
    public function setLastUpdateTimingLimit(int $lastUpdateLimit)
    {
        $this->lastUpdateLimit = $lastUpdateLimit;
    }

    private function selectWantedFiles(array $fileArray)
    {
        if ($this->ftpFileName != null) {
            $isAvailable = $this->isSearchedFileAvailable($fileArray);
            if ($isAvailable) {
                return array($this->ftpFileName);
            } else {
                throw new Exception('Target File Not Found');
            }
        } else {
            return $fileArray;
        }
    }

    private function isSearchedFileAvailable(array $fileArray)
    {
        return in_array($this->ftpFileName, $fileArray) ? true : false;
    }

    private function isLastUpdateInTimingLimit(int $mtimStamp)
    {
        //$lastUpdateTime=DateTime::createFromFormat('U', $mtim);
        $dateNow = new DateTime();

        print($dateNow->getTimestamp());
        print($mtimStamp);

        if (($this->lastUpdateLimit != null) &&
            ($dateNow->getTimestamp() - $mtimStamp) > ($this->lastUpdateLimit * 60)
        ) {
            throw new Exception('File last update too old');
        }
        return true;
    }

    /**
     * Download the FTP folder taget and return an array of temporary dowloaded files
     */
    public function getFilesFromFTP()
    {

        error_log('starting FTP connexion');

        if ($this->ftpIsSftp) {

            $filesArray = $this->getSftp();
        } else {
            $filesArray = $this->getFtp();
            //$ftpConnect = ftp_ssl_connect ($this->ftpHost, $this->ftpPort) or die("Can't Connect to Server ".$this->ftpHost);

        }

        return $filesArray;
    }

    private function connectAndGoToFolder() {

        if ($this->ftpIsSftp) {

            $sftp = new SFTP($this->ftpHost);
            if (!$sftp->login($this->ftpUsername, $this->ftpPassword)) {
                throw new Exception('Login SFTP failed');
            }
    
            if (!$sftp->chdir($this->ftpFolder)) {
                throw new Exception("Can't reach SFTP Path Target");
            };

            return $sftp;

        } else {

            if (!$ftpConnect = ftp_connect($this->ftpHost, $this->ftpPort)) {
                throw new Exception('Cant Connect to Server' . $this->ftpHost);
            }
    
            if (!ftp_login($ftpConnect, $this->ftpUsername, $this->ftpPassword)) {
                ftp_close($ftpConnect);
                throw new Exception('Login failed');
            };
    
            //Move to the target folder
            if (!ftp_chdir($ftpConnect, $this->ftpFolder)) {
                ftp_close($ftpConnect);
                throw new Exception("Can't reach FTP Path Target");
            }

            return $ftpConnect;

        }

    }

    public function writeExportDataFile(String $fileName, String $file){

        if ($this->ftpIsSftp) {

            $sftp = $this->connectAndGoToFolder();

            $result = $sftp->put($fileName, $file, SFTP::SOURCE_LOCAL_FILE);

            return $result;

        } else {      

            $ftp = $this->connectAndGoToFolder();

            $result = ftp_fput($ftp, $fileName, fopen($file, 'r'));

            return $result;
        }


    }

    private function getSftp()
    {
        $sftp = $this->connectAndGoToFolder();

        $fileArray = $sftp->nlist();

        //Remove . and ..
        unset($fileArray[0]);
        unset($fileArray[1]);

        $selectedFiles = $this->selectWantedFiles($fileArray);

        $resultFileArray = [];

        foreach ($selectedFiles as $fileInFtp) {
            $fileState = $sftp->stat($fileInFtp);
            $this->isLastUpdateInTimingLimit($fileState['mtime']);
            print_r($fileState);
            $temp = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileInFtp, 'w');
            //Retrieve File from SFTP
            $sftp->get($fileInFtp, $temp);
            fclose($temp);
            //Store resulting file in array
            $resultFileArray[] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileInFtp;
        }

        return $resultFileArray;
    }

    private function getFtp()
    {
        $ftpConnect = $this->connectAndGoToFolder();

        // Get files in the ftp folder
        $fileArray = ftp_nlist($ftpConnect, ".");

        $selectedFiles = $this->selectWantedFiles($fileArray);

        $resultFileArray = [];

        foreach ($selectedFiles as $fileInFtp) {
            $temp = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileInFtp, 'w');
            ftp_fget($ftpConnect, $temp, $fileInFtp);
            fclose($temp);
            //Store resulting file in array
            $resultFileArray[] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileInFtp;
        }

        ftp_close($ftpConnect);

        return $resultFileArray;
    }

    /**
     * Transform Text file with # separator to an associative array
     */
    public static function parseLysarcTxt(String $txt)
    {
        //Erase last empty new line
        $txt=rtrim($txt);
        //Divided text in row by splitting new line
        $lines = explode("\n", $txt);

        $titles = [];
        $results = [];

        for ($i = 0; $i < sizeOf($lines); $i++) {
            //remove return new line and last #
            $lines[$i]=rtrim($lines[$i]);
            $lines[$i]=rtrim($lines[$i], '#');
            //split string in columns
            $columns = explode("#", $lines[$i]);
            if ($i == 0) {
                //First line is column definition
                $titles = $columns;
            } else {
                $patient = [];
                //For each column associate data into an associative array
                for ($j = 0; $j < sizeof($columns); $j++) {
                    $patient[$titles[$j]] = $columns[$j];
                }
                $results[] = $patient;
            }
        }

        return $results;
    }


    function sendFailedReadFTP($exceptionMessage){
        try{
            $email = new Send_Email($this->linkpdo);
            $email->setMessage("FTP Import Has failed <br> Reason : ".$exceptionMessage);
            $email->setSubject('Auto Import Failed');
            $email->addAminEmails();
            $answer=$email->sendEmail();
        }catch(Exception $e){
            echo('sendEmailException');
            echo($e->getMessage());
        }

    
    }


    /**
     * Return files from a local folder
     * @param string $folder
     * @return string[]
     */
    /*
    public static function getFilesFromFolder(string $folder)
    {

        $scanned_directory = array_diff(scandir($folder), array('..', '.'));

        $resultFileArray = [];
        foreach ($scanned_directory as $file) {

            $resultFileArray[] = $folder . DIRECTORY_SEPARATOR . $file;
        }

        return $resultFileArray;
    }
    */
}

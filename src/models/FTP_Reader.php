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
Class FTP_Reader {

    public $ftpUsername;
    public $ftpPassword;
    public $ftpHost;
    public $ftpFolder;
    public $ftpPort;
    public $ftpIsSecure;

	public function __construct () {
	    
    }

    /**
     * 
     */
    public function setFTPCredential (String $host, String $username, String $password, String $folder='/', int $port=21, bool $isSecure=false) {

        $this->ftpHost=$host;
        $this->ftpUsername=$username;
        $this->ftpPassword=$password;
        $this->ftpFolder=$folder;
        $this->ftpPort=$port;
        $this->ftpIsSecure=$isSecure;

    }

    /**
     * Download the FTP folder taget and return an array of temporary dowloaded files
     */
    function getFilesFromFTP () {
        global $ftpSource;
        $ftpSource=true;
        
        if($this->ftpIsSecure){
            $ftpConnect=ftp_ssl_connect ($this->ftpHost, $this->ftpPort) or die("Can't Connect to Server "+$this->ftpHost);
            
        }else{
            $ftpConnect = ftp_connect($this->ftpHost, $this->ftpPort) or die("Can't Connect to Server "+$this->ftpHost); 
        }
        
        if(ftp_login($ftpConnect, $this->ftpUsername, $this->ftpPassword)){
            
            //Move to the target folder
            if(!ftp_chdir($ftpConnect, $this->ftpFolder)){
                die("Can't reach FTP Path Target");
            }
            // Get files in the ftp folder
            $fileContents = ftp_nlist($ftpConnect, ".");
            
            $resultFileArray=[];
            
            foreach ($fileContents as $fileInFtp){
                $temp = fopen(sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileInFtp, 'w');
                ftp_fget($ftpConnect, $temp, $fileInFtp);
                fclose($temp);
                //Store resulting file in array
                $resultFileArray[]=sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileInFtp;
            }
            
            
            ftp_close($ftpConnect);
            return $resultFileArray;
            
        }else{
    
            ftp_close($ftpConnect);
            die( 'Bad FTP credentials');
        }
    }

    /**
     * Transform Text file with # separator to an associative array
     */
    public function parseLysarcTxt(String $txt){

        //Divided text in row by splitting new line
        $lines = explode("\n", $txt);

        $titles=[];
        $results=[];

        for ($i=0 ; $i<sizeOf($lines); $i++) {
            $columns = explode("#", $lines[$i]);
            if($i==0){
                $titles=$columns;
            }else{
                for ($j=0 ; $j< sizeof($columns); $j++){
                    $results[][$titles[$j]]=$columns[$j];
                }
            }

        }

        error_log($results);
        return $results;

    }


    /**
     * Return files from a local folder
     * @param string $folder
     * @return string[]
     */
    public static function getFilesFromFolder(string $folder){
    
    $scanned_directory = array_diff(scandir($folder), array('..', '.'));
    
    $resultFileArray=[];
    
    foreach ($scanned_directory as $file){
        
        $resultFileArray[]=$folder.DIRECTORY_SEPARATOR.$file;
        
    }
    
    return $resultFileArray;
    
}

}
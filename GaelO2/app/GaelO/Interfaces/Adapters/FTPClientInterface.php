<?php

namespace App\GaelO\Interfaces\Adapters;

use phpDocumentor\Reflection\Types\Void_;

interface FTPClientInterface
{

    /**
     * Setter for FTP Connexion parameter
     */
    public function setFTPServer(string $host, int $port, ?string $username, ?string $password, bool $isSftp, bool $isSSL) : void;

    /**
     * Get storage path in the project
     */
    public function getFileContent(string $fullPath, ?int $maxAge) : string;

    /**
     * Write file to a folder in destination in FTP
     */
    public function writeFileContent(string $content, string $destination) : bool;
}

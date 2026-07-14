<?php

namespace App\GaelO\Interfaces\Adapters;

interface WebdavClientInterface
{

    /**
     * Setter for Webdav Connexion parameter
     */
    public function setWebdavServer(string $host, ?string $username, ?string $password): void;

    /**
     * Get storage path in the project
     */
    public function getFileContent(string $fullPath, ?int $maxAge = null): string;

    /**
     * Write file to a folder in destination in FTP
     */
    public function writeFileContent(string $content, string $destination): bool;

    /* Write from stream input */
    public function writeStreamContent($stream, string $destinationPath): bool;
}

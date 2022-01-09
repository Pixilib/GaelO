<?php

namespace App\GaelO\Adapters;

use DateTime;
use Exception;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class FtpClientAdapter implements FTPClientInterface
{

    private FilesystemInterface $filesystem;

    public function setFTPServer(string $host, int $port, ?string $username, ?string $password, bool $isSftp, bool $isSSL): void
    {
        if (!$isSftp) {

            $this->filesystem = new Filesystem(new FtpAdapter([
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'ssl' => $isSSL,
                'timeout' => 30
            ]));
        } else {

            $this->filesystem = new Filesystem(new SftpAdapter([
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'timeout' => 30,
            ]));
        }
    }

    public function getFileContent(string $fullPath, ?int $maxAgeSeconds): string
    {
        if (!$this->filesystem->has($fullPath)) throw new Exception('FTP File Not Found');

        $lastUpdateTimeStamp = $this->filesystem->getTimestamp($fullPath);
        $dateNow = new DateTime();

        if ($maxAgeSeconds && ($dateNow->getTimestamp() - $lastUpdateTimeStamp) > $maxAgeSeconds) throw new Exception('FTP Last update over limits');

        $content = $this->filesystem->read($fullPath);

        return $content;
    }

    public function writeFileContent(string $content, string $destinationPath): bool
    {
        $success = $this->filesystem->put($destinationPath, $content);
        if (!$success) throw new Exception('FTP Write Error');
        return $success;
    }
}

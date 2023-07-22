<?php

namespace App\GaelO\Adapters;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use DateTime;
use Exception;

class FtpClientAdapter implements FTPClientInterface
{

    private Filesystem $filesystem;

    public function setFTPServer(string $host, int $port, ?string $username, ?string $password, bool $isSftp, bool $isSSL): void
    {
        if (!$isSftp) {
            $this->filesystem = new FileSystem(
                new FtpAdapter(
                    // Connection options
                    FtpConnectionOptions::fromArray([
                        'host' => $host, // required
                        'root' => '/', // required
                        'username' => $username, // required
                        'password' => $password, // required
                        'port' => $port,
                        'ssl' => $isSSL,
                        'timeout' => 30,
                    ])
                )
            );
        } else {

            $this->filesystem = new Filesystem(
                new SftpAdapter(
                    new SftpConnectionProvider(
                        $host,
                        $username,
                        $password,
                        null,
                        null,
                        $port,
                        false,
                        30
                    ),
                    '/'
                )
            );
        }
    }

    public function getFileContent(string $fullPath, ?int $maxAgeSeconds): string
    {
        if (!$this->filesystem->has($fullPath)) {
            throw new GaelOException('FTP File Not Found');
        }

        $lastUpdateTimeStamp = $this->filesystem->lastModified($fullPath);
        $dateNow = new DateTime();

        if ($maxAgeSeconds && ($dateNow->getTimestamp() - $lastUpdateTimeStamp) > $maxAgeSeconds) {
            throw new GaelOException('FTP Last update over limits');
        }

        $content = $this->filesystem->read($fullPath);

        return $content;
    }

    public function writeFileContent(string $content, string $destinationPath): bool
    {
        try {
            $this->filesystem->write($destinationPath, $content);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}

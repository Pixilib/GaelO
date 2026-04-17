<?php

namespace App\GaelO\Adapters;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\WebdavClientInterface;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;
use DateTime;
use Exception;

enum AuthEnum: int
{
    case BASIC = Client::AUTH_BASIC;
    case DIGEST = Client::AUTH_DIGEST;
}

class WebdavClientAdapter implements WebdavClientInterface
{
    private Filesystem $filesystem;

    public function setWebdavServer(string $host, ?string $username, ?string $password, ?AuthEnum $authType = AuthEnum::BASIC): void
    {
        $baseUri = rtrim($host, '/') . '/';
        $client = new Client([
            'baseUri' => $baseUri,
            'userName' => $username,
            'password' => $password,
            'authType' => $authType->value
        ]);

        $adapter = new WebDAVAdapter($client);
        $this->filesystem = new Filesystem($adapter);
    }

    public function getFileContent(string $fullPath, ?int $maxAgeSeconds = null): string
    {
        if (!$this->filesystem->fileExists($fullPath)) {
            throw new GaelOException('Webdav File Not Found');
        }
        $lastUpdateTimeStamp = $this->filesystem->lastModified($fullPath);
        $dateNow = new DateTime();

        if ($maxAgeSeconds && ($dateNow->getTimestamp() - $lastUpdateTimeStamp) > $maxAgeSeconds) {
            throw new GaelOException('Webdav Last update over limits');
        }

        return $this->filesystem->read($fullPath);
    }

    public function writeStreamContent($stream, string $destinationPath): bool
    {
        try {
            // Vérification de sécurité sur la ressource
            if (!is_resource($stream)) {
                Log::error("WebDAV: La source fournie n'est pas un flux valide.");
                return false;
            }

            // On s'assure d'être au début du fichier local avant l'envoi
            rewind($stream);

            // Utilisation de writeStream de Flysystem
            $this->filesystem->writeStream($destinationPath, $stream);

            return true;
        } catch (Exception $e) {
            // C'est ici que l'erreur "rewind" est capturée
            Log::error("WebDAV writeStream error: " . $e->getMessage());
            return false;
        } finally {
            // On ferme le stream systématiquement ici pour libérer le fichier temporaire
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
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
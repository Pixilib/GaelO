<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\Psr7ResponseAdapter;
use App\GaelO\Exceptions\GaelOException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;

class DicomWebService extends HttpClientAdapter
{
    public function getStoreDicomRequest(string $filename): Request
    {
        $boundary = '0f3cf5c0-70e0-41ef-baef-c6f9f65ec3e1';

        $fileContents = fopen($filename, 'rb');

        $body = "--{$boundary}\r\n"
            . "Content-Type: application/dicom\r\n"
            . "\r\n";

        $bodyStream = Utils::streamFor($body);
        $fileStream = Utils::streamFor($fileContents);
        $closing = Utils::streamFor("\r\n--{$boundary}--\r\n");

        $multipartStream = new \GuzzleHttp\Psr7\AppendStream([
            $bodyStream,
            $fileStream,
            $closing,
        ]);

        $headers = [
            'Content-Type' => "multipart/related; type=\"application/dicom\"; boundary={$boundary}",
        ];

        if ($this->login !== '' && $this->password !== '') {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->login . ':' . $this->password);
        }

        if ($this->authorizationToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->authorizationToken;
        }

        return new Request('POST', $this->address . '/studies', $headers, $multipartStream);
    }


    public function sendStudyInstancesConcurrentlyToDicomWeb(OrthancService $orthancservice, array $seriesOrthancID, int $concurrency): array
    {
        $responseArray = [];
        $hasError = false;

        $instanceIds = $orthancservice->describeResources('instances', $seriesOrthancID);
        $instanceDetails = [];

        for ($i = 0; $i < sizeof($instanceIds); $i++) {
            $instanceDetails[] = [
                'index' => $i,
                'instanceId' => $instanceIds[$i]['ID'],
                'path' => null
            ];
        }

        $requestsGenerator = function (&$instanceDetails) use ($orthancservice) {
            foreach ($instanceDetails as &$instanceDetail) {
                $orthancInstanceId = $instanceDetail['instanceId'];
                $instancePath = $orthancservice->getInstance($orthancInstanceId);
                $instanceDetail['path'] = $instancePath;
                yield $this->getStoreDicomRequest($instancePath);
            }
        };

        $pool = new Pool($this->client, $requestsGenerator($instanceDetails), [
            'concurrency' => $concurrency,
            'fulfilled' => function (Response $response, int $index) use (&$responseArray, $instanceDetails) {
                $instanceDetail = array_find($instanceDetails, function ($instanceDetail) use ($index) {
                    return $instanceDetail['index'] === $index;
                });
                if (!$instanceDetail)
                    throw new GaelOException("Original Orthanc sent file not found");
                if (file_exists($instanceDetail['path'])) {
                    unlink($instanceDetail['path']);
                }
                $responseArray[$index] = new Psr7ResponseAdapter($response);
            },
            'rejected' => function (RequestException|ConnectException $exception, int $index) use ($instanceDetails, &$hasError) {
                $instanceDetail = array_find($instanceDetails, function ($instanceDetail) use ($index) {
                    return $instanceDetail['index'] === $index;
                });
                if (!$instanceDetail)
                    throw new GaelOException("Original Orthanc sent file not found");
                if (file_exists($instanceDetail['path'])) {
                    unlink($instanceDetail['path']);
                }
                Log::error($exception);
                $hasError = true;
            },
        ]);

        $pool->promise()->wait();

        if ($hasError) {
            throw new GaelOException("Error while sending to DICOMWeb Service");
        }

        return array_filter($responseArray);
    }
}
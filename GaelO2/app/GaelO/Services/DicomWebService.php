<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\Psr7ResponseAdapter;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;

class DicomWebService extends HttpClientAdapter
{
    private array $headers;

    public function setDicomWebServer(string $url, string $login, string $password, string $token, array $headers): void
    {
        $this->setUrl($url);
        if ($login && $password)
            $this->setBasicAuthentication($login, $password);
        if ($token)
            $this->setAuthorizationToken($token);
        if ($headers)
            $this->headers = $headers;
    }

    public function storeDicom(string $dicomPath)
    {

        $this->uploadFile('POST', '/studies', $dicomPath, "application/dicom");
    }

    public function getStoreDicomRequest(string $filename)
    {
        $fileHandler = fopen($filename, 'rb');
        $options = [
            'auth' => [$this->login, $this->password],
            'multipart' => [
                [
                    'name' => 'dicom',
                    'headers' => [
                        'Content-Type' => "application/dicom",
                        'Transfer-Encoding' => 'encoding'
                    ],
                    'contents' => $fileHandler
                ]
            ]
        ];

        Log::info($filename);
        Log::info(json_encode($options));

        $response = $this->client->request('POST', $this->address . '/studies', $options);
        Log::info($response->getStatusCode());
        return null;
    }

    public function sendInstancesConcrrentlyToDicomWeb($instances, int $concurrency = 5)
    {

        $requestsGenerator = function ($instances) {
            foreach ($instances as $instance) {
                yield $this->getStoreDicomRequest($instance);
            };
        };

        $pool = new Pool($this->client, $requestsGenerator($instances), [
            'concurency' => $concurrency,
            'fulfilled' => function (Response $response, $index) use (&$responseArray, &$instances) {
                unlink($instances[$index]);
                $responseArray[$index] = new Psr7ResponseAdapter($response);
            },
            'rejected' => function (RequestException|ConnectException $exception, $index) use (&$instances) {
                unlink($instances[$index]);
                $reason = "Error sending dicom to orthanc";

                if ($exception instanceof RequestException && $exception->hasResponse()) {
                    $reason = $exception->getResponse()->getStatusCode();
                    Log::error($exception->getResponse()->getBody()->getContents());
                } else {
                    $reason = $exception->getMessage();
                }
                // this is delivered each failed request
                Log::error('DICOM Import Failed in Orthanc Temporary: ' . $reason . ' index: ' . $index);
            },
        ]);
        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
        //Remove empty places of the response array (in case of failed request)
        $responseArray = array_filter($responseArray);
        return $responseArray;
    }
}

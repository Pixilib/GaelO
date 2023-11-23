<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getJsonResponse($body, int $statusCode, string $statusText, bool $emptyArrayAsObject = false)
    {
        if ($body === null) return response()->noContent()->setStatusCode($statusCode, $statusText);
        else {
            if (is_array($body) && sizeof($body) === 0 && $emptyArrayAsObject) {
                $body = (object) [];
            }
            return response()->json($body)->setStatusCode($statusCode, $statusText);
        };
    }
}

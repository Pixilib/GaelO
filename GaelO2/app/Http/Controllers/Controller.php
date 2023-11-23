<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getJsonResponse($body, int $statusCode, string $statusText, bool $forceObject = false)
    {
        if ($body === null) return response()->noContent()->setStatusCode($statusCode, $statusText);
        else return response()->json($body, $statusCode, [], $forceObject ? JSON_FORCE_OBJECT : 0)->setStatusCode($statusCode, $statusText);
    }
}

<?php

namespace App\Exceptions;

use App\GaelO\Exceptions\GaelOAuthorizationException;
use App\GaelO\Exceptions\GaelOException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException && $request->wantsJson()) {
            return response()->json(['message' => 'Not Found!'], 404);
        }

        if ($exception instanceof GaelOAuthorizationException && $request->wantsJson()) {
            return response()->noContent()
            ->setStatusCode(401, 'Unauthorized' );
        }

        if ($exception instanceof GaelOException && $request->wantsJson()) {
            return response()->noContent()
            ->setStatusCode(400, $exception->getMessage() );
        }

        return parent::render($request, $exception);
    }
}

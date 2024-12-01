<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\ExportDatabase\ExportDatabase;
use App\GaelO\UseCases\ExportDatabase\ExportDatabaseRequest;
use App\GaelO\UseCases\ExportDatabase\ExportDatabaseResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportDBController extends Controller
{
    public function exportDB(Request $request, ExportDatabase $exportDatabase, ExportDatabaseRequest $exportDatabaseRequest, ExportDatabaseResponse $exportDatabaseResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        Util::fillObject($requestData, $exportDatabaseRequest);
        $exportDatabaseRequest->currentUserId = $currentUser['id'];

        $exportDatabase->execute($exportDatabaseRequest, $exportDatabaseResponse);
        if ($exportDatabaseResponse->status === 200) {
            return response()->stream(function () use ($exportDatabase): void {
                $exportDatabase->readExport();
            });
        } else {
            return response()->noContent()
                ->setStatusCode($exportDatabaseResponse->status, $exportDatabaseResponse->statusText);
        }
    }
}

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
    public function exportDB(Request $request, ExportDatabase $exportDatabase, ExportDatabaseRequest $exportDatabaseRequest, ExportDatabaseResponse $exportDatabaseResponse) {
        $currentUser = Auth::user();
        $exportDatabaseRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        $exportDatabaseRequest = Util::fillObject($requestData, $exportDatabaseRequest);
        $exportDatabase->execute($exportDatabaseRequest, $exportDatabaseResponse);
        return response()->download($exportDatabaseResponse->zipFile, $exportDatabaseResponse->fileName, array('Content-Type: application/zip','Content-Length: '. filesize($exportDatabaseResponse->zipFile)))->deleteFileAfterSend(true);
    }
}

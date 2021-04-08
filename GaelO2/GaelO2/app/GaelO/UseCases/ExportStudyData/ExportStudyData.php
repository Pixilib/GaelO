<?php

namespace App\GaelO\UseCases\ExportStudyData;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use Exception;
use ZipArchive;

class ExportStudyData{

    public function __construct()
    {

    }

    public function execute(ExportStudyDataRequest $exportStudyDataRequest, ExportStudyDataResponse $exportStudyDataResponse){

        try{

            $this->checkAuthorization($exportStudyDataRequest->currentUserId, $exportStudyDataRequest->studyName);

            $zip=new ZipArchive();
            $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMP_ZIP_EXPORT_');
            $zip->open($tempZip, ZipArchive::OVERWRITE);

            //ICI APPELER L EXPORTER SERVICE SET LE STUDYNAME ET COLLECTER LES FICHIERS DANS LE ZIP
            $date=Date('Ymd_his');
            //$zip->addFile($databaseDumpedFile, "export_database_$date.sql");

            $zip->close();

            $exportStudyDataResponse->status = 200;
            $exportStudyDataResponse->statusText = 'OK';
            $exportStudyDataResponse->zipFile = $tempZip;
            $exportStudyDataResponse->fileName = "export_".$exportStudyDataRequest->studyName."_".$date.".zip";

        }catch(GaelOException $e){

            $exportStudyDataResponse->status = $e->statusCode;
            $exportStudyDataResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        };

    }

    private function checkAuthorization(int $currentUserId, string $studyName){

        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }
}

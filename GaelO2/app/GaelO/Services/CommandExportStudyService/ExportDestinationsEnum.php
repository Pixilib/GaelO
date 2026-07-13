<?php

enum ExportDestinationsEnum: string
{
    case ORTHANCPEER  = "orthanc-peer";
    case FTP          = "ftp";
    case SFTP         = "sftp";
    case DICOMWEB     = "dicom-web";
    case S3           = "s3";
    case AZURESTORAGE = "azure-storage";
    case WEBDAV       = "webdav";
}
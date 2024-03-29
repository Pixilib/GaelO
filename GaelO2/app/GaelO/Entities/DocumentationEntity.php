<?php

namespace App\GaelO\Entities;

class DocumentationEntity
{

    public int $id;
    public string $name;
    public string $version;
    public string $documentDate;
    public string $studyName;
    public bool $investigator;
    public bool $controller;
    public bool $monitor;
    public bool $reviewer;
    public ?string $deletedAt;

    public static function fillFromDBReponseArray(array $array): DocumentationEntity
    {
        $documentationEntity  = new DocumentationEntity();
        $documentationEntity->id = $array['id'];
        $documentationEntity->name = $array['name'];
        $documentationEntity->documentDate = $array['document_date'];
        $documentationEntity->studyName = $array['study_name'];
        $documentationEntity->version = $array['version'];
        $documentationEntity->investigator = $array['investigator'];
        $documentationEntity->controller = $array['controller'];
        $documentationEntity->monitor = $array['monitor'];
        $documentationEntity->reviewer = $array['reviewer'];
        $documentationEntity->deletedAt = $array['deleted_at'];

        return $documentationEntity;
    }
}

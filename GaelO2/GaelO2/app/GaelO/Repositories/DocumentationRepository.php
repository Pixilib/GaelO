<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\Models\Documentation;
use App\GaelO\Util;

class DocumentationRepository implements DocumentationRepositoryInterface
{

    public function __construct(Documentation $documentation)
    {
        $this->documentation = $documentation;
    }

    public function find($id): array
    {
        return $this->documentation->withTrashed()->findOrFail($id)->toArray();
    }

    public function delete($id): void
    {
        $this->documentation->findOrFail($id)->delete();
    }

    public function createDocumentation(
        string $name,
        string $studyName,
        string $version,
        bool $investigator,
        bool $controller,
        bool $monitor,
        bool $reviewer
    ): array {

        $documentation = new Documentation();
        $documentation->name = $name;
        $documentation->document_date = Util::now();
        $documentation->study_name = $studyName;
        $documentation->version = $version;
        $documentation->investigator = $investigator;
        $documentation->controller = $controller;
        $documentation->monitor = $monitor;
        $documentation->reviewer = $reviewer;

        $documentation->save();
        return $documentation->toArray();
    }

    public function getDocumentationsOfStudy(string $studyName, bool $withTrashed = false): array
    {

        $query = $this->documentation->where('study_name', $studyName);
        if ($withTrashed) {
            $query->withTrashed();
        }
        $documentations = $query->get();
        return empty($documentations) ? [] : $documentations->toArray();
    }

    public function getDocumentationOfStudyWithRole(string $studyName, string $role): array
    {
        $documentations = $this->documentation->where([['study_name', $studyName], [strtolower($role), true]])->get();
        return empty($documentations) ? [] : $documentations->toArray();
    }

    public function updateDocumentation(
        int $id,
        string $name,
        string $studyName,
        string $version,
        bool $investigator,
        bool $controller,
        bool $monitor,
        bool $reviewer
    ) {

        $documentation = $this->documentation->findOrFail($id);
        $documentation->name = $name;
        $documentation->document_date = Util::now();
        $documentation->study_name = $studyName;
        $documentation->version = $version;
        $documentation->investigator = $investigator;
        $documentation->controller = $controller;
        $documentation->monitor = $monitor;
        $documentation->reviewer = $reviewer;
        $documentation->save();

    }

    public function updateDocumentationPath(
        int $id,
        string $path
    ) {

        $documentation = $this->documentation->findOrFail($id);
        $documentation->path = $path;
        $documentation->save();

    }

    public function isKnownDocumentation(string $name, string $version): bool
    {
        return empty($this->documentation->where('name', $name)->where('version', $version)->get()->first()) ? false : true;
    }

    public function reactivateDocumentation(int $documentationId): void
    {
        $this->documentation->withTrashed()->findOrFail($documentationId)->restore();
    }
}

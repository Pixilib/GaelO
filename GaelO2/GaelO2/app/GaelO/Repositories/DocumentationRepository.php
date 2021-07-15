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

    private function create(array $data)
    {
        $documentation = new Documentation();
        $model = Util::fillObject($data, $documentation);
        $model->save();
        return $model->toArray();
    }

    public function update($id, array $data): void
    {
        $model = $this->documentation->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id): array
    {
        return $this->documentation->withTrashed()->findOrFail($id)->toArray();
    }

    public function delete($id): void
    {
        $this->documentation->find($id)->delete();
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

        $data = [
            'name' => $name,
            'document_date' => Util::now(),
            'study_name' => $studyName,
            'version' => $version,
            'investigator' => $investigator,
            'controller' => $controller,
            'monitor' => $monitor,
            'reviewer' => $reviewer,
        ];

        return $this->create($data);
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
        $data = [
            'name' => $name,
            'document_date' => Util::now(),
            'study_name' => $studyName,
            'version' => $version,
            'investigator' => $investigator,
            'controller' => $controller,
            'monitor' => $monitor,
            'reviewer' => $reviewer,
        ];

        $this->update($id, $data);
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

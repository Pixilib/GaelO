<?php

namespace App\GaelO\Interfaces;

interface DocumentationRepositoryInterface
{

    public function update($id, array $data) : void;

    public function create(array $data);

    public function find(int $id);

    public function delete($id) : void;

    public function createDocumentation(
        string $name,
        string $documentDate,
        string $studyName,
        string $version,
        bool $investigator,
        bool $controller,
        bool $monitor,
        bool $reviewer
    ): array ;

    public function getDocumentationsOfStudy(string $studyName): array ;

    public function getDocumentationOfStudyWithRole(string $studyName, string $role): array;

    public function updateDocumentation(
        int $id,
        string $name,
        string $documentDate,
        string $studyName,
        string $version,
        bool $investigator,
        bool $controller,
        bool $monitor,
        bool $reviewer
    );
}

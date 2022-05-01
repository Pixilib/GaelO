<?php

namespace App\GaelO\Interfaces\Repositories;

interface DocumentationRepositoryInterface
{

    public function find(int $id, bool $withTrashed);

    public function delete($id): void;

    public function createDocumentation(
        string $name,
        string $studyName,
        string $version,
        bool $investigator,
        bool $controller,
        bool $monitor,
        bool $reviewer
    ): array;

    public function getDocumentationsOfStudy(string $studyName, bool $withTrashed = false): array;

    public function getDocumentationOfStudyWithRole(string $studyName, string $role): array;

    public function updateDocumentation(
        int $id,
        string $name,
        string $studyName,
        string $version,
        bool $investigator,
        bool $controller,
        bool $monitor,
        bool $reviewer
    );

    public function updateDocumentationPath(int $id, string $path);

    public function isKnownDocumentation(string $name, string $version): bool;

    public function reactivateDocumentation(int $documentationId): void;
}

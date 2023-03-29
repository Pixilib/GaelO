<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\Models\Center;

class CenterRepository implements CenterRepositoryInterface
{
    private Center $centerModel;

    public function __construct(Center $center)
    {
        $this->centerModel = $center;
    }

    public function getAll(): array
    {
        $centers = $this->centerModel->get();
        return empty($centers) ? [] : $centers->toArray();
    }

    public function createCenter(int $code, string $name, string $countryCode): void
    {
        $center = new Center();
        $center->code = $code;
        $center->name = $name;
        $center->country_code = $countryCode;
        $center->save();
    }

    public function isExistingCenterName(string $name): bool
    {
        $center = $this->centerModel->where('name', $name)->get();
        return $center->count() > 0  ? true : false;
    }

    public function getCenterByCode(int $code): array
    {
        $center = $this->centerModel->find($code)->toArray();
        return $center;
    }

    public function getCentersFromCodeArray(array $centerCodes): array
    {
        $centers = $this->centerModel->whereIn('code', $centerCodes)->get();
        return $centers !== null  ? $centers->toArray() : [];
    }

    public function isKnownCenter(int $code): bool
    {
        return empty($this->centerModel->find($code)) ? false : true;
    }

    public function updateCenter(int $code, String $name, String $countryCode): void
    {
        $center = $this->centerModel->findOrFail($code);
        $center->name = $name;
        $center->country_code = $countryCode;
        $center->save();
    }

    public function getUsersOfCenter(int $code)
    {
        $users = $this->centerModel->findOrFail($code)->users;
        return $users->toArray();
    }

    public function getPatientsOfCenter(int $code)
    {
        $patients = $this->centerModel->findOrFail($code)->patients;
        return $patients->toArray();
    }

    public function deleteCenter(int $code)
    {
        $this->centerModel->findOrFail($code)->delete();
    }
}

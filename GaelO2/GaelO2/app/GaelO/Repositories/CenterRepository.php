<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\Models\Center;

class CenterRepository implements CenterRepositoryInterface
{

    public function __construct(Center $center)
    {
        $this->center = $center;
    }

    public function getAll(): array
    {
        $centers = $this->center->get();
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
        $center = $this->center->where('name', $name)->get();
        return $center->count() > 0  ? true : false;
    }

    public function getCenterByCode(int $code): array
    {
        $center = $this->center->find($code)->toArray();
        return $center;
    }

    public function getCentersFromCodeArray(array $centerCodes): array
    {
        $centers = $this->center->whereIn('code', $centerCodes)->get();
        return $centers !== null  ? $centers->toArray() : [];
    }

    public function isKnownCenter(int $code): bool
    {
        return empty($this->center->find($code)) ? false : true;
    }

    public function updateCenter(int $code, String $name, String $countryCode): void
    {
        $center = $this->center->findOrFail($code);
        $center->name = $name;
        $center->country_code = $countryCode;
        $center->save();
    }
}

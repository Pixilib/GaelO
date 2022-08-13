<?php

namespace App\GaelO\Repositories;

use App\Models\VisitGroup;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Util;

class VisitGroupRepository implements VisitGroupRepositoryInterface
{

    private VisitGroup $visitGroupModel;

    public function __construct(VisitGroup $visitGroup)
    {
        $this->visitGroupModel = $visitGroup;
    }

    public function find($id)
    {
        return $this->visitGroupModel->findOrFail($id)->toArray();
    }

    public function delete($id): void
    {
        $this->visitGroupModel->findOrFail($id)->delete();
    }

    public function createVisitGroup(String $studyName, String $name, String $modality): void
    {

        $visitGroup = new VisitGroup();
        $visitGroup->name = $name;
        $visitGroup->study_name = $studyName;
        $visitGroup->modality = $modality;
        $visitGroup->save();
    }

    public function hasVisitTypes(int $visitGroupId): bool
    {
        $visitGroup = $this->visitGroupModel->withCount('visitTypes')->findOrFail($visitGroupId);
        return $visitGroup->visit_types_count > 0 ? true : false;
    }

    public function isExistingVisitGroup(String $studyName, String $name): bool
    {
        $visitGroup = $this->visitGroupModel->where([['study_name', '=', $studyName], ['name', '=', $name]])->get();
        return sizeof($visitGroup) > 0;
    }
}

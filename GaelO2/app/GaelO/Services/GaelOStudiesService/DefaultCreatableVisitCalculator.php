<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Constants\Enums\InclusionStatusEnum;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;

class DefaultCreatableVisitCalculator
{

    protected VisitRepositoryInterface $visitRepositoryInterface;
    protected VisitTypeRepositoryInterface $visitTypeRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, VisitTypeRepositoryInterface $visitTypeRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
    }

    public function getAvailableVisitToCreate(array $patientEntity): array
    {

        //If status other than Included or Preincluded (ie : excluded or withdrawn) no visit is expected to be created
        if (!in_array($patientEntity['inclusion_status'], [InclusionStatusEnum::INCLUDED->value, InclusionStatusEnum::PRE_INCLUDED->value])) {
            return [];
        }

        //Get Created Patients Visits
        $createdVisitsArray = $this->visitRepositoryInterface->getPatientsVisits($patientEntity['id']);
        $createdVisitTypeIds = array_map(function ($visit) {
            return $visit['visit_type_id'];
        }, $createdVisitsArray);

        //Get possible visits groups and types from study
        $studyVisitsTypes = $this->visitTypeRepositoryInterface->getVisitTypesOfStudy($patientEntity['study_name']);

        $availableVisitType = [];

        //Loop possible Visit Type and select thoose not created
        foreach ($studyVisitsTypes as $possibleVisitType) {
            $visitTypeId = $possibleVisitType['id'];
            if (!in_array($visitTypeId, $createdVisitTypeIds)) {
                $availableVisitType[] = $possibleVisitType;
            }
        }

        return $availableVisitType;
    }
}

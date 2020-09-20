<?php

namespace App\GaelO\Services;

use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Util;

class TrackerService {

    public function __construct(TrackerRepository $trackerRepository){
        $this->trackerRepository = $trackerRepository;
    }

    public function writeAction(int $userId, string $role, ?string $study, ?int $id_visit, string $actionType, ?array $actionDetails){
        $data = [
            'study_name' => $study,
            'user_id' => $userId,
            'date'=> Util::now(),
            'role' => $role,
            'visit_id'=> $id_visit,
            'action_type' => $actionType,
            'action_details' => json_encode($actionDetails)
        ];

        $this->trackerRepository->create($data);
    }
}

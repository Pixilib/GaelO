<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\Models\Tracker;
use App\GaelO\Util;

class TrackerRepository implements TrackerRepositoryInterface
{
    private Tracker $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function writeAction(int $userId, string $role, ?string $studyName, ?int $id_visit, string $actionType, ?array $actionDetails): void
    {
        $tracker = new Tracker();

        $tracker->study_name = $studyName;
        $tracker->user_id = $userId;
        $tracker->date = Util::now();
        $tracker->role = $role;
        $tracker->visit_id = $id_visit;
        $tracker->action_type = $actionType;
        $tracker->action_details = json_encode($actionDetails);

        $tracker->save();
    }

    public function getTrackerOfRole(string $role): array
    {
        $trackerData = $this->tracker->with('user')->where('role', $role)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfRoleAndStudy(string $studyName, string $role, bool $withUser): array
    {
        if ($withUser) $trackerData = $this->tracker->with('user')->where('study_name', $studyName)->where('role', $role)->get();
        else $trackerData = $this->tracker->where('study_name', $studyName)->where('role', $role)->get();
        return empty($trackerData)  ? [] : $trackerData->toArray();
    }

    public function getTrackerOfVisitId(int $visitId): array
    {
        $trackerData = $this->tracker->with('user')->where('visit_id', $visitId)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfActionInStudy(string $action, string $studyName): array
    {
        $trackerData = $this->tracker->with('user')->where('study_name', $studyName)->where('action_type', $action)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfRoleActionInStudy(string $role, string $action, string $studyName): array
    {
        $trackerData = $this->tracker->with('user', 'visit', 'visit.visitType')
        ->with(['visit.reviewStatus' => function ($query) use ($studyName) {
            $query->where('study_name', $studyName);
        }])->where('study_name', $studyName)->where('role', $role)->where('action_type', $action)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfMessages(): array
    {
        $trackerData = $this->tracker->with('user')->where('action_type', Constants::TRACKER_SEND_MESSAGE)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }
}

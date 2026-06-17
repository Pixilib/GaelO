<?php

namespace App\GaelO\Services\MailService;

use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;

class MailListBuilder
{

    private UserRepositoryInterface $userRepositoryInterface;
    private array $emails = [];

    public function __construct(
        UserRepositoryInterface $userRepositoryInterface
    ) {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function withUserEmail(int $userId): self
    {
        $email = $this->userRepositoryInterface->find($userId)['email'];
        $this->emails[] = $email;
        return $this;
    }

    public function withValidatedUserEmail(int $userId): self
    {
        $user = $this->userRepositoryInterface->find($userId);
        if ($user['email_verified_at'] != null) {
            $this->emails[] = $user['email'];
        }
        return $this;
    }

    public function withAdminsEmails(): self
    {
        $admins = $this->userRepositoryInterface->getAdministrators();
        $adminsEmails = array_map(function ($user) {
            return $user['email'];
        }, $admins);
        $this->emails = [...$adminsEmails, ...$this->emails];
        return $this;
    }

    private function filterNonVerifiedEmailsUsers(array $users)
    {
        $emails = [];
        foreach ($users as $user) {
            if ($user['email_verified_at'] != null) $emails[] = $user['email'];
        }
        return $emails;
    }

    public function withUsersEmailsByRolesInStudy(string $studyName, string $role): self
    {
        $users = $this->userRepositoryInterface->getUsersByRolesInStudy($studyName, $role, filter:true); 
        //Filter user with a verified email (password have been set)
        //$emails = $this->filterNonVerifiedEmailsUsers($users);
        $users = array_column($users, 'email');
        $this->emails = [...$users, ...$this->emails];
        return $this;
    }

    public function withInvestigatorOfCenterInStudy(String $studyName, String $center, ?String $job = null): self
    {
        $users = $this->userRepositoryInterface->getInvestigatorsOfStudyFromCenter($studyName, $center, $job, true); 
        //$emails = $this->filterNonVerifiedEmailsUsers($users);
        $users = array_column($users, 'email');
        $this->emails = [...$users, ...$this->emails];
        return $this;
    }

    public function get(): array
    {
        return $this->emails;
    }
}

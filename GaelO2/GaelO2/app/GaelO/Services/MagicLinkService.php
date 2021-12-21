<?php

namespace App\GaelO\Services;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;

class MagicLinkService
{
    private FrameworkInterface $frameworkInterface;

    private int $userId;
    private string $redirectUrl;

    public function __construct(FrameworkInterface $frameworkInterface)
    {
        $this->frameworkInterface = $frameworkInterface;
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }

    public function setRedirectUrl(string $redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }
    public function generate()
    {
        return $this->frameworkInterface->createMagicLink($this->userId, $this->redirectUrl);
    }


}

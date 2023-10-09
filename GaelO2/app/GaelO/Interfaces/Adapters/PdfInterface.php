<?php

namespace App\GaelO\Interfaces\Adapters;

interface PdfInterface
{
    public function saveViewToPdf(string $view, array $parameters, string $filename): void;
}

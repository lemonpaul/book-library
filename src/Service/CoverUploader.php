<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class CoverUploader
{
    private $targetDirectory;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $cover)
    {
        $coverName = md5(uniqid()).'.'.$cover->guessExtension();

        $cover->move($this->getTargetDirectory(), $coverName);

        return $coverName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}

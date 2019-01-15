<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
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
        $fileSystem = new Filesystem();
        $hash = md5(uniqid());
        $subDirectory = substr($hash, 0, 2).'/'.substr($hash, 2, 2)
                                           .'/'.substr($hash, 4, 2)
                                           .'/'.substr($hash, 6, 2);
        $fileSystem->mkdir($this->getTargetDirectory().'/'.$subDirectory);
        $coverName = substr($hash, 8).'.'.$cover->guessExtension();
        $cover->move($this->getTargetDirectory().'/'.$subDirectory, $coverName);
        return $subDirectory.'/'.$coverName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}

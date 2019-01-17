<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirectory;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file)
    {
        $fileSystem = new Filesystem();
        $hash = md5(uniqid());
        $subDirectory = substr($hash, 0, 2).'/'.substr($hash, 2, 2)
                                           .'/'.substr($hash, 4, 2)
                                           .'/'.substr($hash, 6, 2);
        $fileSystem->mkdir($this->getTargetDirectory().'/'.$subDirectory);
        $fileName = $hash.'.'.$file->guessExtension();
        $file->move($this->getTargetDirectory().'/'.$subDirectory, $fileName);
        return $subDirectory.'/'.$fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}

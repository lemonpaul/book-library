<?php

namespace App\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\Book;

class FileRemover
{
    private $projectDirectory;

    public function __construct($projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof Book) {
            return;
        }
        $cover = $entity->getCover();
        if ($cover) {
            unlink($this->getProjectDirectory().'/public/'.$cover);
        }
        $file = $entity->getFile();
        if ($file) {
            unlink($this->getProjectDirectory().'/public/'.$file);
        }
    }

    public function getProjectDirectory()
    {
        return $this->projectDirectory;
    }
}

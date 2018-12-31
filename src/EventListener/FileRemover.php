<?php

namespace App\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\Book;

class FileRemover
{
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Book) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $cover = $entity->getCover();
        if ($cover) {
            unlink($cover);
        }
        $file = $entity->getFile();
        if ($file) {
            unlink($file);
        }
    }
}

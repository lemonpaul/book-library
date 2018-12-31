<?php

namespace App\Serializer;

use App\Entity\Book;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BookNormalizer implements NormalizerInterface
{
    private $normalizer;

    public function normalize($book, $format = null, array $context=array())
    {
        $data = array();
        $data['id'] = $book->getId();
        $data['title'] = $book->getTitle();
        $data['author'] = $book->getAuthor();
        $data['date'] = $book->getDate()->format('d/m/Y');
        if ($book->getCover()) {
            $data['cover'] = '/uploads/covers/'.$book->getCover();
        }
        if ($book->getDownload()) {
            $data['file'] = '/uploads/files/'.$book->getFile();
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Book;
    }
}

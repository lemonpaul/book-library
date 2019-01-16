<?php

namespace App\Serializer;

use App\Entity\Book;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\Request;

class BookNormalizer implements NormalizerInterface
{
    private $normalizer;

    public function normalize($book, $format = null, array $context = array())
    {
        $data = array();
        $data['id'] = $book->getId();
        $data['title'] = $book->getTitle();
        $data['author'] = $book->getAuthor();
        $data['date'] = $book->getDate()->format('d/m/Y');
        $request = Request::createFromGlobals();
        if ($book->getCover()) {
            $data['cover'] = $request->getSchemeAndHttpHost().'/'.$book->getCover();
        }
        if ($book->getDownload()) {
            $data['file'] = $request->getSchemeAndHttpHost().'/'.$book->getFile();
            $data['file_name'] = $book->getFileName();
        }
        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Book;
    }
}

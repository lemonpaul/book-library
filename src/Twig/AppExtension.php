<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new TwigFunction('cover', array($this, 'renderImage'))
        );
    }

    public function renderImage(string $uri, int $width, int $height)
    {
        $cover = "<img src=\"/uploads/covers/".$uri."\" width=\""
            .$width."\"height=\"".$height."\"/>";
        return $cover;
    }
}
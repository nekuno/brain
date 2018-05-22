<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

class TumblrPhotoProcessor extends TumblrPostProcessor
{
    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $this->hydratePhotoLink($preprocessedLink, $data);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return $this->getPhotoImages($preprocessedLink, $data);
    }
}
<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

class TumblrAudioProcessor extends TumblrPostProcessor
{
    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $this->hydrateAudioLink($preprocessedLink, $data);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return $this->getAudioImages($preprocessedLink, $data);
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        $this->addAudioTags($preprocessedLink, $data);
    }
}
<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

class TumblrLinkProcessor extends TumblrPostProcessor
{
    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $this->hydrateLinkLink($preprocessedLink, $data);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return $this->getLinkImages($preprocessedLink, $data);
    }
}
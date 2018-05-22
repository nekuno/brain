<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

class TumblrVideoProcessor extends TumblrPostProcessor
{
    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $this->hydrateVideoLink($preprocessedLink, $data);
    }
}
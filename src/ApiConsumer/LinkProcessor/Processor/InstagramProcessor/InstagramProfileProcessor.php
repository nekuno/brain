<?php

namespace ApiConsumer\LinkProcessor\Processor\InstagramProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Link\Creator;

class InstagramProfileProcessor extends InstagramProcessor
{
    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();
        $creator = Creator::buildFromLink($link);
        $preprocessedLink->setFirstLink($creator);
    }
}
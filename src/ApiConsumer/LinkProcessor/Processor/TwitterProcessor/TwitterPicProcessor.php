<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;

class TwitterPicProcessor extends AbstractProcessor
{
    public function requestItem(PreprocessedLink $link)
    {
        return array();
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    protected function getItemIdFromParser($url)
    {

    }
}
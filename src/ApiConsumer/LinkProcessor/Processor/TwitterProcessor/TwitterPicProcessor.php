<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;

class TwitterPicProcessor extends AbstractProcessor
{
    public function requestItem(PreprocessedLink $link)
    {
        throw new CannotProcessException($link->getUrl(), 'Twitter pic needs to be scraped');
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    protected function getItemIdFromParser($url)
    {

    }
}
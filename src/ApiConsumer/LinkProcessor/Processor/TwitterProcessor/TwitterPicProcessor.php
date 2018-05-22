<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;

class TwitterPicProcessor extends AbstractTwitterProcessor
{
    public function requestItem(PreprocessedLink $link)
    {
        throw new CannotProcessException($link->getUrl(), 'Twitter pic needs to be scraped');
    }

    protected function getItemIdFromParser($url)
    {

    }
}
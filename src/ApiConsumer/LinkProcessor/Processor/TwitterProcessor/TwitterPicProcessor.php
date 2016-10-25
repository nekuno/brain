<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;

class TwitterPicProcessor extends AbstractProcessor
{

    /**
     * @param $link PreprocessedLink
     * @return array|false Returns the processed link as array or false if the processor can not process the link
     */
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
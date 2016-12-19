<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;

class TwitterIntentProcessor extends AbstractProcessor
{
    protected function getUserId(PreprocessedLink $preprocessedLink)
    {
        return $userId = $preprocessedLink->getResourceItemId() ?
            array('user_id' => $preprocessedLink->getResourceItemId())
            :
            parent::getUserId($preprocessedLink);
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {

    }

    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        // TODO: Implement requestItem() method.
    }

}
<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\User\TokensModel;

class TwitterIntentProcessor extends AbstractTwitterProfileProcessor
{
    protected function getUserId(PreprocessedLink $preprocessedLink)
    {
        return $userId = $preprocessedLink->getResourceItemId() ?
            array('user_id' => $preprocessedLink->getResourceItemId())
            :
            parent::getUserId($preprocessedLink);
    }
}